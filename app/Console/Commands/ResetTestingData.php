<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class ResetTestingData extends Command
{
    protected $signature = 'crm:reset-testing-data';
    protected $description = 'Clear CRM testing data while keeping administrator accounts and their credentials';

    public function handle(): int
    {
        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');
        if (!app()->environment(['local', 'testing']) || $connection !== 'pgsql'
            || !in_array($database, ['gagandeep_steels', 'gagandeep_steels_test'], true)) {
            $this->error('Refusing to reset an unexpected environment or database.');
            return self::FAILURE;
        }

        $adminIds = DB::table('users')->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.is_admin', true)->pluck('users.id')->all();
        if (!$adminIds) {
            $this->error('No administrator account found. No data was changed.');
            return self::FAILURE;
        }

        $preserved = ['migrations', 'users', 'roles'];
        $tables = array_values(array_filter(Schema::getTableListing(), fn ($table) => !in_array(Str::afterLast($table, '.'), $preserved, true)));
        DB::transaction(function () use ($tables, $adminIds) {
            if ($tables) {
                $quoted = collect($tables)->map(fn ($table) => DB::connection()->getQueryGrammar()->wrapTable($table))->implode(', ');
                DB::statement('TRUNCATE TABLE '.$quoted.' RESTART IDENTITY CASCADE');
            }
            DB::table('users')->whereNotIn('id', $adminIds)->delete();
            DB::table('roles')->where('is_admin', false)->delete();

            DB::table('notification_settings')->insert(['id' => 1, 'sound_enabled' => true]);
            DB::table('lead_settings')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
            foreach (['IndiaMART', 'Justdial', 'TradeIndia'] as $provider) {
                DB::table('lead_connections')->insert(['provider' => $provider, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach (['Rolling Mill / Manufacturer', 'Finish Goods Godown', 'MS Melting Units',
                'Cast Iron Foundries', 'Cast Iron Wires', 'Cast Iron Sellers', 'Scrap Suppliers'] as $name) {
                DB::table('job_roles')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        $this->info('CRM testing data cleared. Administrator accounts and login credentials are unchanged.');
        $this->line('Default job roles, connection placeholders and settings were restored.');
        return self::SUCCESS;
    }
}
