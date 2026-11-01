<?php
namespace App\Http\Controllers;
use App\Http\Requests\FreightRateRequest;
use App\Models\FreightRate;
use App\Models\Transporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class FreightRateController
{
    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:200'], 'from' => ['nullable', 'string', 'max:100'],
            'to' => ['nullable', 'string', 'max:100'], 'transporter' => ['nullable', 'string', 'max:150'],
            'sort' => ['nullable', 'in:updated,price_asc,price_desc'], 'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
    private function query(array $filters): Builder
    {
        $query = FreightRate::query();
        // Escape LIKE metacharacters so user input is a literal substring.
        $like = fn ($value) => '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($value)).'%';
        if (filled($filters['search'] ?? null)) {
            $term = $like($filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->whereRaw("origin LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("destination LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("transporter LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("remarks LIKE ? ESCAPE '!'", [$term]);
            });
        }
        foreach (['from' => 'origin', 'to' => 'destination'] as $filter => $column) {
            if (filled($filters[$filter] ?? null)) {
                // City filters match from the beginning, with literal user input.
                $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters[$filter])).'%';
                $query->whereRaw("{$column} LIKE ? ESCAPE '!'", [$prefix]);
            }
        }
        if (filled($filters['transporter'] ?? null)) { $query->where('transporter', $filters['transporter']); }
        match ($filters['sort'] ?? 'updated') {
            'price_asc' => $query->orderBy('price'), 'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('updated_at'),
        };
        return $query->orderByDesc('id');
    }
    public function index(Request $request) { return $this->screen($request); }
    public function edit(Request $request, FreightRate $rate) { return $this->screen($request, $rate); }
    private function screen(Request $request, ?FreightRate $editing = null)
    {
        $filters = $this->filters($request);
        $rates = $this->query($filters)->paginate(8)->withQueryString();
        return view('rates.index', [
            'rates' => $rates, 'filters' => $filters, 'editing' => $editing,
            'total' => FreightRate::count(),
            'transporterCount' => Transporter::count(),
            'average' => FreightRate::avg('price') ?? 0,
            'transporters' => Transporter::orderBy('name')->pluck('name'),
        ]);
    }
    public function store(FreightRateRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->safe()->except('version');
            $data['transporter'] = $this->registeredName($data['transporter']);
            FreightRate::create($data);
        });
        return redirect()->route('rates.index')->with('success', 'Freight rate added.');
    }
    public function update(FreightRateRequest $request, FreightRate $rate)
    {
        $changed = DB::transaction(function () use ($request, $rate) {
            $data = $request->safe()->except('version');
            $data['transporter'] = $this->registeredName($data['transporter']);
            $data['version'] = DB::raw('version + 1');
            $data['updated_at'] = now();
            return FreightRate::whereKey($rate->id)->where('version', $request->integer('version'))->update($data);
        });
        if (!$changed) {
            throw ValidationException::withMessages(['conflict' => 'This rate changed elsewhere. Copy your changes, then reopen the rate from the directory before saving again.']);
        }
        return redirect()->route('rates.index')->with('success', 'Freight rate updated.');
    }
    public function destroy(Request $request, FreightRate $rate)
    {
        $validated = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $changed = FreightRate::whereKey($rate->id)->where('version', $validated['version'])->delete();
        if (!$changed) { return redirect()->route('rates.index')->withErrors(['conflict' => 'This rate changed elsewhere. Review the latest details before deleting.']); }
        return redirect()->route('rates.index')->with('success', 'Freight rate deleted.');
    }
    private function registeredName(string $name): string
    {
        // Serialize freight writes with transporter renames/deletes.
        $transporter = Transporter::where('name', $name)->sharedLock()->first();
        if (!$transporter) {
            throw ValidationException::withMessages(['transporter' => 'This transporter changed or was deleted. Select it again from the dropdown.']);
        }
        return $transporter->name;
    }
    public function export(Request $request)
    {
        $query = $this->query($this->filters($request));
        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['From', 'To', 'Price (INR)', 'Transporter', 'Last updated', 'Remarks'], ',', '"', '', "\r\n");
            foreach ($query->cursor() as $rate) {
                $values = [$rate->origin, $rate->destination, $rate->price, $rate->transporter, $rate->updated_at->toIso8601String(), $rate->remarks ?? ''];
                $safe = array_map(fn ($value) => preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $values);
                fputcsv($stream, $safe, ',', '"', '', "\r\n");
            }
            fclose($stream);
        }, 'freight-rates-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
