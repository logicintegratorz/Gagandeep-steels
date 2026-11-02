<?php
/** Restore a legacy MySQL SQL backup into the current Laravel PostgreSQL schema. */
use Illuminate\Support\Facades\DB;
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$backup = $argv[1] ?? '';
if (!is_file($backup)) throw new RuntimeException('Provide an existing MySQL backup .sql file.');
$sql = file_get_contents($backup);

function dumpColumns(string $sql, string $table): array {
    if (!preg_match('/CREATE TABLE `'.preg_quote($table, '/').'` \((.*?)\) ENGINE=/s', $sql, $match)) return [];
    preg_match_all('/^\s*`([^`]+)`/m', $match[1], $columns);
    return $columns[1];
}
function dumpRows(string $sql, string $table): array {
    if (!preg_match('/INSERT INTO `'.preg_quote($table, '/').'` VALUES (.*?);\r?\n/s', $sql, $match)) return [];
    $value = $match[1]; $rows=[]; $row=[]; $token=''; $quoted=false; $escaped=false; $depth=0;
    $push = static function () use (&$row, &$token): void {
        $raw = trim($token); $token='';
        $row[] = strtoupper($raw)==='NULL' ? null : $raw;
    };
    for ($i=0, $len=strlen($value); $i<$len; $i++) {
        $char=$value[$i];
        if ($quoted) {
            if ($escaped) { $token .= match($char) {'n'=>"\n", 'r'=>"\r", '0'=>"\0", default=>$char}; $escaped=false; continue; }
            if ($char==='\\') { $escaped=true; continue; }
            if ($char==="'") { $quoted=false; continue; }
            $token.=$char; continue;
        }
        if ($char==="'") { $quoted=true; continue; }
        if ($char==='(') { if ($depth++===0) { $row=[]; $token=''; } else $token.=$char; continue; }
        if ($char===')') { if (--$depth===0) { $push(); $rows[]=$row; } else $token.=$char; continue; }
        if ($char===',' && $depth===1) { $push(); continue; }
        if ($depth>0) $token.=$char;
    }
    return $rows;
}

$pgTables = collect(DB::select("select tablename from pg_tables where schemaname='public'"))->pluck('tablename')->all();
$skip = ['migrations'];
$tables = array_values(array_diff($pgTables, $skip));
$foreignKeys = DB::select("SELECT conrelid::regclass::text AS child, confrelid::regclass::text AS parent FROM pg_constraint WHERE contype = 'f'");
$parents = array_fill_keys($tables, []);
foreach ($foreignKeys as $foreignKey) {
    $child = str_replace('public.', '', $foreignKey->child); $parent = str_replace('public.', '', $foreignKey->parent);
    if (isset($parents[$child], $parents[$parent])) $parents[$child][] = $parent;
}
$ordered=[]; $pending=$tables;
while ($pending) {
    $ready = array_values(array_filter($pending, fn($table)=>!array_intersect($parents[$table], $pending)));
    if (!$ready) { $ordered = array_merge($ordered, $pending); break; }
    $ordered = array_merge($ordered, $ready); $pending = array_values(array_diff($pending, $ready));
}
if ($tables) DB::statement('TRUNCATE TABLE '.implode(', ', array_map(fn($t)=>'"'.$t.'"', $tables)).' RESTART IDENTITY CASCADE');
    $imported=[];
    foreach ($ordered as $table) {
        if (in_array($table, $skip, true)) continue;
        $sourceColumns=dumpColumns($sql, $table); $sourceRows=dumpRows($sql, $table);
        if (!$sourceColumns || !$sourceRows) continue;
        $targetColumns = DB::getSchemaBuilder()->getColumnListing($table);
        $positions=[];
        foreach ($sourceColumns as $index=>$column) if (in_array($column, $targetColumns, true)) $positions[$column]=$index;
        if (!$positions) continue;
        $records=[];
        foreach ($sourceRows as $sourceRow) {
            $record=[];
            foreach ($positions as $column=>$index) $record[$column]=$sourceRow[$index] ?? null;
            $records[]=$record;
        }
        foreach (array_chunk($records, 200) as $chunk) DB::table($table)->insert($chunk);
        $imported[$table]=count($records);
    }
    foreach ($pgTables as $table) {
        if (!in_array('id', DB::getSchemaBuilder()->getColumnListing($table), true)) continue;
        DB::statement('SELECT setval(pg_get_serial_sequence(\''.$table.'\', \'id\'), COALESCE((SELECT MAX(id) FROM "'.$table.'"), 1), true)');
    }
echo json_encode(['restored'=>$imported], JSON_PRETTY_PRINT).PHP_EOL;
