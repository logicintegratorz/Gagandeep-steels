<x-app-layout
    title="Leads"
    :body-class="request('view') === 'extended' ? 'extended-leads' : ''"
>

@if(request('view') === 'extended')
    <script
        src="{{ asset('js/extended-leads.js').'?v='.filemtime(public_path('js/extended-leads.js')) }}"
        defer
    ></script>

    <button
        type="button"
        class="extended-sidebar-edge"
        aria-label="Open navigation"
        aria-controls="sidebar"
        aria-expanded="false"
        title="Hover to open navigation"
    >
        <span aria-hidden="true">☰</span>
    </button>
@endif


@include('leads.nav')


<script
    src="{{ asset('js/leads-list.js').'?v='.filemtime(public_path('js/leads-list.js')) }}"
    defer
></script>


@if(auth()->user()->isAdmin())
    <link
        rel="stylesheet"
        href="{{ asset('css/lead-owner-history.css').'?v='.filemtime(public_path('css/lead-owner-history.css')) }}"
    >

    <script
        src="{{ asset('js/lead-owner-history.js').'?v='.filemtime(public_path('js/lead-owner-history.js')) }}"
        defer
    ></script>
@endif


@php
    $extendedView = request('view') === 'extended';
    $showTouchedOnly = $extendedView && request('contacts_filter') === 'touched';
    $extendedIndexUrl = route('leads.index', ['view' => 'extended']);
    $toggleExtendedUrl = $showTouchedOnly
        ? $extendedIndexUrl
        : route('leads.index', ['view' => 'extended', 'contacts_filter' => 'touched']);
@endphp

<section class="panel lead-panel {{ $extendedView ? 'extended-lead-panel' : '' }}">

    <div class="{{ $extendedView ? 'extended-lead-sticky-header' : '' }}">
        <div class="lead-list-heading">
            <div>
                <h2>{{ $extendedView ? ($showTouchedOnly ? 'Touched leads' : 'All leads') : 'Assigned & saved leads' }}</h2>

                <p>{{ $extendedView ? ($showTouchedOnly ? 'Untouched leads are hidden. Unarchive them to return to the complete list.' : 'All available leads are shown, including untouched leads.') : 'This list shows assigned leads and saved contacts. Select leads below to transfer them to a team member.' }}</p>
            </div>

            <div class="extended-lead-heading-actions">
                @if($extendedView)
                    <span class="lead-list-count">{{ $totalLeadCount }} leads</span>
                    <span class="lead-list-count lead-list-count-untouched">{{ $untouchedLeadCount }} untouched</span>
                    <span class="lead-list-count lead-list-count-touched">{{ $touchedLeadCount }} touched</span>
                    <a class="button {{ $showTouchedOnly ? 'primary' : 'secondary' }}" href="{{ $toggleExtendedUrl }}" data-extended-archive-toggle aria-pressed="{{ $showTouchedOnly ? 'true' : 'false' }}">
                        {{ $showTouchedOnly ? 'Unarchive untouched leads' : 'Archive untouched leads' }}
                    </a>
                @endif
                <a class="button secondary" href="{{ route('home') }}">← Back</a>
            </div>
        </div>

        @include('leads.filters')
    </div>


    <form
        method="POST"
        action="{{ route('leads.transfer') }}"
        id="lead-bulk-transfer"
    >

        @csrf


        <div class="lead-bulk-bar">

            <div>
                <strong>
                    <output id="lead-selection-count">0</output>
                    / {{ $leads->total() }} selected
                </strong>

                <small>
                    Only leads you own or have transfer permission for can be selected.
                </small>
            </div>


            <label for="lead-bulk-owner">
                Transfer to

                <select
                    id="lead-bulk-owner"
                    name="owner_id"
                    required
                >
                    <option value="">
                        Choose a team member
                    </option>

                    @foreach($owners->groupBy(fn($owner)=>$owner->role?->name ?? 'No role')->sortKeys() as $roleName=>$roleOwners)
                        <optgroup label="{{ $roleName }}">
                            @foreach($roleOwners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach
                        </optgroup>
                    @endforeach
                </select>
            </label>


            <button
                class="button primary"
                id="lead-bulk-submit"
                disabled
            >
                Transfer selected
            </button>

        </div>


        <div class="table-scroll">

            <table class="lead-directory">

                <thead>

                    <tr>

                        <th class="lead-select-cell">
                            <input
                                type="checkbox"
                                id="lead-select-all"
                                aria-label="Select all leads on this page"
                            >
                        </th>

                        <th>Lead / company</th>

                        <th>Contact</th>

                        <th>Stage</th>

                        <th>Assigned to</th>

                        <th class="lead-priority-column">
                            Priority
                        </th>

                        <th>Value</th>

                        <th>Work items</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($leads as $lead)

                        <tr>

                            {{-- SELECT --}}
                            <td class="lead-select-cell">

                                @php($transferable = !$lead->claimed_at && ($lead->owner_id === auth()->id() || auth()->user()->allows('leads.assign')))
                                <input
                                    type="checkbox"
                                    @if($transferable) name="lead_ids[]" value="{{ $lead->id }}" @endif
                                    data-lead-select
                                    data-transferable="{{ $transferable ? 'true' : 'false' }}"
                                    aria-label="Select {{ $lead->name }}"
                                >
                                @if($transferable)<input type="hidden" name="versions[{{ $lead->id }}]" value="{{ $lead->version }}">@endif

                            </td>


                            {{-- LEAD / COMPANY --}}
                            <td>

                                <a href="{{ $extendedView ? route('leads.show', array_filter(['lead' => $lead, 'from' => 'extended', 'contacts_filter' => $showTouchedOnly ? 'touched' : null])) : route('leads.show', $lead) }}">
                                    <strong>
                                        {{ $lead->name }}
                                    </strong>
                                </a>

                                <small>
                                    {{ $lead->company }}
                                </small>

                                @if($lead->job_role)
                                    <small>
                                        {{ $lead->job_role }}
                                    </small>
                                @endif

                            </td>


                            {{-- CONTACT --}}
                            <td>

                                {{ $lead->phone }}

                                <small>
                                    {{ $lead->email }}
                                </small>

                            </td>


                            {{-- STAGE --}}
                            <td>
                                {{ $lead->stage }}
                            </td>


                            {{-- ASSIGNED TO --}}
                            <td>

                                @if(auth()->user()->isAdmin())

                                    <button
                                        type="button"
                                        class="lead-owner-history-trigger"
                                        data-lead-history-url="{{ route('leads.history', $lead) }}"
                                        aria-haspopup="dialog"
                                        title="View full lead history"
                                    >
                                        {{ $lead->owner?->name ?? 'Unassigned' }}
                                    </button>

                                @else

                                    {{ $lead->owner?->name ?? 'Unassigned' }}

                                @endif

                            </td>


                            {{-- PRIORITY --}}
                            <td class="lead-priority-column">

                                <span
                                    class="lead-priority-badge priority-{{ strtolower($lead->priority) }}"
                                >
                                    {{ $lead->priority }}
                                </span>

                            </td>


                            {{-- VALUE --}}
                            <td>
                                {{ $lead->value ?? '—' }}
                            </td>


                            {{-- WORK ITEMS --}}
                            <td>
                                {{ $lead->work_items_count }}
                            </td>


                            {{-- ACTIONS --}}
                            <td>

                                <div class="lead-row-actions">

                                    @if(auth()->user()->allows('leads.edit'))

                                        <a
                                            class="button secondary"
                                            href="{{ route('leads.edit', $lead) }}"
                                        >
                                            Edit
                                        </a>

                                    @endif


                                    @if(auth()->user()->allows('leads.delete'))

                                        <button
                                            class="button danger"
                                            type="submit"
                                            form="lead-delete-{{ $lead->id }}"
                                        >
                                            Delete
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="9">
                                No leads here yet. A lead appears when one is assigned to you.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </form>


    @if(auth()->user()->allows('leads.delete'))

        @foreach($leads as $lead)

            <form
                id="lead-delete-{{ $lead->id }}"
                method="POST"
                action="{{ route('leads.destroy', $lead) }}"
                onsubmit="return confirm('Delete this lead? It will disappear from leads, lists and follow-ups.')"
            >

                @csrf
                @method('DELETE')

                <input
                    type="hidden"
                    name="version"
                    value="{{ $lead->version }}"
                >

            </form>

        @endforeach

    @endif


    @include('leads.pages', ['records' => $leads])

</section>


@if(auth()->user()->isAdmin())

    <dialog
        class="lead-owner-history-dialog"
        id="lead-owner-history"
        aria-labelledby="lead-owner-history-title"
    >
        <div data-lead-history-body></div>
    </dialog>

@endif


</x-app-layout>
