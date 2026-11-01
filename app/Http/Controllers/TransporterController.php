<?php
namespace App\Http\Controllers;

use App\Models\Transporter;
use App\Models\FreightRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class TransporterController
{
    public function options()
    {
        return response()->json(Transporter::orderBy('name')->pluck('name'))->header('Cache-Control', 'no-store');
    }

    public function index(Request $request)
    {
        return $this->screen($request);
    }

    public function edit(Request $request, Transporter $transporter)
    {
        return $this->screen($request, $transporter);
    }

    private function screen(Request $request, ?Transporter $editing = null)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $search = trim($filters['search'] ?? '');
        $query = Transporter::query();
        if ($search !== '') {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function ($query) use ($term) {
                foreach (['name', 'contact_person', 'phone', 'gst_number', 'address'] as $column) {
                    $query->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$term]);
                }
            });
        }
        return view('transporters.index', [
            'transporters' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'search' => $search,
            'editing' => $editing,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedDetails($request);
        try {
            Transporter::create($data);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['name' => 'This transporter already exists.']);
        }
        return redirect()->route('transporters.index')->with('success', 'Transporter added. You can now select it on the freight form.');
    }

    public function update(Request $request, Transporter $transporter)
    {
        $data = $this->validatedDetails($request, $transporter);
        $request->validate(['version' => ['required', 'integer', 'min:1']]);
        try {
            DB::transaction(function () use ($request, $transporter, $data) {
                $current = Transporter::whereKey($transporter->id)->lockForUpdate()->firstOrFail();
                $this->checkVersion($request, $current);
                if ($current->name !== $data['name']) {
                    FreightRate::where('transporter', $current->name)->update([
                        'transporter' => $data['name'], 'version' => DB::raw('version + 1'), 'updated_at' => now(),
                    ]);
                }
                $current->fill($data);
                $current->version++;
                $current->save();
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['name' => 'This transporter already exists.']);
        }
        return redirect()->route('transporters.index')->with('success', 'Transporter updated. Linked freight rates use the updated name.');
    }

    public function destroy(Request $request, Transporter $transporter)
    {
        $request->validate(['version' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($request, $transporter) {
            $current = Transporter::whereKey($transporter->id)->lockForUpdate()->firstOrFail();
            $this->checkVersion($request, $current);
            if (FreightRate::where('transporter', $current->name)->exists()) {
                throw ValidationException::withMessages(['transporter' => 'This transporter is used in freight rates. Assign those rates to another transporter before deleting it.']);
            }
            $current->delete();
        });
        return redirect()->route('transporters.index')->with('success', 'Transporter deleted.');
    }

    private function checkVersion(Request $request, Transporter $transporter): void
    {
        if ($transporter->version !== $request->integer('version')) {
            throw ValidationException::withMessages(['conflict' => 'This transporter changed elsewhere. Reopen it from the directory before saving or deleting.']);
        }
    }

    private function validatedDetails(Request $request, ?Transporter $transporter = null): array
    {
        foreach (['name', 'contact_person', 'phone', 'gst_number', 'address'] as $field) {
            if (is_string($request->input($field))) {
                $value = trim($request->input($field));
                if ($field === 'gst_number') $value = strtoupper($value);
                if ($field === 'phone') $value = preg_replace('/[\s()\-]/', '', $value);
                $request->merge([$field => $value === '' ? null : $value]);
            }
        }
        return $request->validate([
            'name' => ['required', 'string', 'max:150', function ($attribute, $value, $fail) use ($transporter) {
                $duplicate = Transporter::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($value)])
                    ->when($transporter, fn ($query) => $query->whereKeyNot($transporter->id))->exists();
                if ($duplicate) $fail('This transporter already exists.');
            }],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'gst_number' => ['nullable', 'string', 'regex:/^[0-9A-Z]{15}$/'],
            'address' => ['required', 'string', 'max:500'],
        ], [
            'phone.regex' => 'Enter a phone number with 7–15 digits, optionally starting with +.',
            'gst_number.regex' => 'Enter a 15-character GST number using letters and digits, or leave it blank.',
        ]);
    }
}
