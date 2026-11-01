<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transporters · Gagandeep Steels</title>
    <link rel="icon" href="{{ asset('images/gagandeep-steels-logo.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/feedback.css').'?v='.filemtime(public_path('css/feedback.css')) }}"><script src="{{ asset('js/ui.js').'?v='.filemtime(public_path('js/ui.js')) }}" defer></script><link rel="stylesheet" href="{{ asset('css/compact.css').'?v='.filemtime(public_path('css/compact.css')) }}"></head>
<body class="transporters-page">
<a class="skip-link" href="#main">Skip to content</a>
<x-sidebar/>
<x-screen-time/>
<main class="main" id="main">
    <header class="topbar"><div><button class="icon-button mobile-toggle" id="menu-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="sidebar"><x-icon name="menu"/></button><span>Workspace</span><x-icon name="chevron" size="13"/><strong>Transporters</strong></div>@if(auth()->user()->allows('rates.view'))<a class="text-link" href="{{ route('rates.index') }}">Back to freight rates</a>@endif<x-notification-bell/></header>
    <div class="content">
        <div class="page-heading"><div><div class="eyebrow">FREIGHT MANAGEMENT</div><h1>Transporters<span>.</span></h1><p>Add transport partners once, then select them when creating freight rates.</p></div></div>
        
        
        @if($editing || auth()->user()->allows('transporters.create'))<section class="panel {{ $editing ? 'editing' : '' }}" id="transporter-form" aria-labelledby="transporter-title">
            <div class="panel-heading"><span class="section-icon"><x-icon name="truck"/></span><div><h2 id="transporter-title">{{ $editing ? 'Edit transporter' : 'Add transporter' }}</h2><p>Save contact and business details for your transport partner. * Required fields.</p></div></div>
            <form method="POST" action="{{ $editing ? route('transporters.update', $editing) : route('transporters.store') }}" class="transporter-form" data-save-form>
                @csrf @if($editing) @method('PUT')<input type="hidden" name="version" value="{{ old('version', $editing->version) }}"> @endif
                <label for="name">Transporter name <span>*</span><input id="name" name="name" value="{{ old('name', $editing?->name) }}" placeholder="e.g. Punjab Transport Company" maxlength="150" required @error('name') aria-invalid="true" @enderror></label>
                <label for="contact_person">Contact person <span>*</span><input id="contact_person" name="contact_person" value="{{ old('contact_person', $editing?->contact_person) }}" placeholder="Full name" maxlength="100" autocomplete="name" required @error('contact_person') aria-invalid="true" aria-describedby="contact-person-error" @enderror>@error('contact_person')<small class="field-error" id="contact-person-error">{{ $message }}</small>@enderror</label>
                <label for="phone">Phone number <span>*</span><input id="phone" name="phone" type="tel" value="{{ old('phone', $editing?->phone) }}" placeholder="e.g. +91 98765 43210" maxlength="25" autocomplete="tel" required @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>@error('phone')<small class="field-error" id="phone-error">{{ $message }}</small>@enderror</label>
                <label for="gst_number">GST number <small class="optional">Optional</small><input id="gst_number" name="gst_number" value="{{ old('gst_number', $editing?->gst_number) }}" placeholder="15-character GST number" maxlength="15" autocapitalize="characters" spellcheck="false" @error('gst_number') aria-invalid="true" aria-describedby="gst-error" @enderror>@error('gst_number')<small class="field-error" id="gst-error">{{ $message }}</small>@enderror</label>
                <label for="address" class="address-field">Address <span>*</span><textarea id="address" name="address" rows="3" placeholder="Office address, city, state and PIN code" maxlength="500" autocomplete="street-address" required @error('address') aria-invalid="true" aria-describedby="address-error" @enderror>{{ old('address', $editing?->address) }}</textarea>@error('address')<small class="field-error" id="address-error">{{ $message }}</small>@enderror</label>
                <div class="form-actions"><span class="form-hint">{{ $editing ? 'Changing the name also updates linked freight rates.' : 'Saved transporters are available in the freight dropdown.' }}</span>@if($editing)<a class="button secondary" href="{{ route('transporters.index') }}">Cancel</a>@endif<button type="submit" class="button primary" data-submit><x-icon :name="$editing ? 'check' : 'plus'" size="17"/><span>{{ $editing ? 'Save changes' : 'Add transporter' }}</span></button></div>
            </form>
        </section>
        @endif <section class="panel" aria-labelledby="directory-title">
            <div class="list-heading"><div><h2 id="directory-title">Transporter directory <span class="count">{{ number_format($transporters->total()) }}</span></h2><p>Your registered transport partners.</p></div></div>
            <form method="GET" action="{{ route('transporters.index') }}" class="filters">
                <label class="search-box"><span class="sr-only">Search transporters</span><x-icon name="search" size="17"/><input type="search" name="search" value="{{ $search }}" maxlength="150" placeholder="Search name, contact, phone or GST…"></label>
                <button class="button secondary">Search</button><a class="text-link" href="{{ route('transporters.index') }}">Clear</a>
            </form>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Transporter directory"><table class="transporter-table"><thead><tr><th scope="col">Transporter</th><th scope="col">Contact person</th><th scope="col">Phone</th><th scope="col">GST number</th><th scope="col">Address</th><th scope="col">Actions</th></tr></thead><tbody>
                @forelse($transporters as $transporter)
                    <tr><td><strong>{{ $transporter->name }}</strong><small class="cell-note">Added {{ $transporter->created_at->format('d M Y') }}</small></td><td>{{ $transporter->contact_person ?: '—' }}</td><td class="phone-cell">{{ $transporter->phone ?: '—' }}</td><td class="gst-cell">{{ $transporter->gst_number ?: '—' }}</td><td class="address-cell">{{ $transporter->address ?: '—' }}</td><td><div class="row-actions">@if(auth()->user()->allows('transporters.edit'))<a class="button secondary" href="{{ route('transporters.edit', $transporter) }}#transporter-form" aria-label="Edit {{ $transporter->name }}"><x-icon name="edit" size="16"/>Edit</a>@endif @if(auth()->user()->allows('transporters.delete'))<form method="POST" action="{{ route('transporters.destroy', $transporter) }}" data-delete-form data-description="{{ $transporter->name }}">@csrf @method('DELETE')<input type="hidden" name="version" value="{{ $transporter->version }}"><button type="submit" class="button danger" data-delete-trigger disabled aria-label="Delete {{ $transporter->name }}"><x-icon name="trash" size="16"/>Delete</button></form>@endif</div></td></tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><h3>{{ $search !== '' ? 'No matching transporters' : 'Add your first transporter' }}</h3><p>{{ $search !== '' ? 'Try a different name or clear your search.' : 'Use the form above to add a name to your freight dropdown.' }}</p></div></td></tr>
                @endforelse
            </tbody></table></div>
            <div class="table-footer"><span>{{ $transporters->firstItem() ?? 0 }}–{{ $transporters->lastItem() ?? 0 }} of {{ $transporters->total() }} transporters</span><nav class="pagination" aria-label="Transporter pages">@if($transporters->previousPageUrl())<a class="page-button" href="{{ $transporters->previousPageUrl() }}" aria-label="Previous page">‹</a>@endif<span>Page {{ $transporters->currentPage() }} of {{ $transporters->lastPage() }}</span>@if($transporters->hasMorePages())<a class="page-button" href="{{ $transporters->nextPageUrl() }}" aria-label="Next page">›</a>@endif</nav></div>
        </section>
        <x-footer/>
    </div>
</main>
<dialog id="delete-dialog" aria-labelledby="delete-title" aria-describedby="delete-description"><div class="dialog-content"><span class="delete-symbol"><x-icon name="trash" size="24"/></span><h2 id="delete-title">Delete this transporter?</h2><p id="delete-description"></p><p>This removes the transporter from the directory and freight dropdown. Transporters used in freight rates cannot be deleted until those rates are reassigned.</p><div class="dialog-actions"><button type="button" class="button secondary" id="cancel-delete" autofocus>Keep transporter</button><button type="button" class="button danger" id="confirm-delete">Delete transporter</button></div></div></dialog>
<noscript><p class="notice">Enable JavaScript to delete transporters with confirmation.</p></noscript>
<x-toasts/></body>
</html>



