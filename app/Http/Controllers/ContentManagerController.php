<?php

namespace App\Http\Controllers;

use App\Models\ContentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ContentManagerController
{
    public const TABS = ['Spectro', 'Certificates', 'Product Documents', 'General'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
            'q' => ['nullable', 'string', 'max:150'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $tab = $filters['tab'] ?? self::TABS[0];
        $documents = ContentDocument::query()->with('uploader')->where('tab', $tab)
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['q'])).'%';
                $query->where(fn ($items) => $items->whereRaw("title LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("description LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("attachment_name LIKE ? ESCAPE '!'", [$term]));
            })
            ->when(filled($filters['from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest('id')->paginate(20)->withQueryString();

        return view('content-manager.index', compact('documents', 'filters', 'tab') + ['tabs' => self::TABS]);
    }

    public function store(Request $request)
    {
        $this->admin($request);
        $data = $this->data($request, true);
        $file = $data['attachment'];
        unset($data['attachment']);
        $data += [
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_path' => $file->store('content-documents'),
            'attachment_mime' => $file->getMimeType(),
            'attachment_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ];
        ContentDocument::create($data);
        return redirect()->route('content-manager.index', ['tab' => $data['tab']])->with('success', 'Document saved.');
    }

    public function update(Request $request, ContentDocument $document)
    {
        $this->admin($request);
        $data = $this->data($request, false) + $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $file = $data['attachment'] ?? null;
        unset($data['attachment'], $data['version']);
        DB::transaction(function () use ($request, $document, $data, $file) {
            $current = ContentDocument::lockForUpdate()->findOrFail($document->id);
            if ($current->version !== $request->integer('version')) throw ValidationException::withMessages(['version' => 'This document changed elsewhere. Refresh and try again.']);
            if ($file) {
                $oldPath = $current->attachment_path;
                $data += ['attachment_name' => $file->getClientOriginalName(), 'attachment_path' => $file->store('content-documents'), 'attachment_mime' => $file->getMimeType(), 'attachment_size' => $file->getSize()];
                DB::afterCommit(fn () => Storage::delete($oldPath));
            }
            $current->fill($data); $current->version++; $current->save();
        });
        return redirect()->route('content-manager.index', ['tab' => $data['tab']])->with('success', 'Document updated.');
    }

    public function destroy(Request $request, ContentDocument $document)
    {
        $this->admin($request);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($document, $data) {
            $current = ContentDocument::lockForUpdate()->findOrFail($document->id);
            if ($current->version !== (int) $data['version']) throw ValidationException::withMessages(['version' => 'This document changed elsewhere. Refresh and try again.']);
            $path = $current->attachment_path; $current->delete();
            DB::afterCommit(fn () => Storage::delete($path));
        });
        return back()->with('success', 'Document deleted.');
    }

    public function open(Request $request, ContentDocument $document)
    {
        abort_unless(Storage::exists($document->attachment_path), 404);
        return response()->file(Storage::path($document->attachment_path), [
            'Content-Type' => $document->attachment_mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $document->attachment_name).'"',
        ]);
    }

    public function download(Request $request, ContentDocument $document)
    {
        abort_unless(Storage::exists($document->attachment_path), 404);
        return Storage::download($document->attachment_path, $document->attachment_name);
    }

    private function data(Request $request, bool $attachmentRequired): array
    {
        return $request->validate([
            'tab' => ['required', Rule::in(self::TABS)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'attachment' => [$attachmentRequired ? 'required' : 'nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,webp'],
        ]);
    }

    private function admin(Request $request): void { abort_unless($request->user()->isAdmin(), 403); }
}
