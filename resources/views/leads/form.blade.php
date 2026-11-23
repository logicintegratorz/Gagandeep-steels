@php
  $isManualContact = ! $lead->exists || ! $lead->connection_id;
  $formTitle = $lead->exists ? ($isManualContact ? 'Edit contact' : 'Edit lead') : 'Add contact';
@endphp
<x-app-layout :title="$formTitle">
@include('leads.nav')
<section class="panel lead-panel">
  <h2>{{ $formTitle }}</h2>
  @if($errors->any())
    <div class="lead-errors" role="alert"><strong>Contact save nahi hua.</strong>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
  @endif
  <form method="POST" action="{{ $lead->exists ? route('leads.update',$lead) : route('leads.store') }}">
    @csrf
    @if($lead->exists)@method('PUT')<input type="hidden" name="version" value="{{ $lead->version }}">@endif
    <div class="lead-grid">
      @foreach(['name'=>'Contact name','company'=>'Company','phone'=>'WhatsApp number','alt_phone'=>'Alt number','email'=>'Contact email','website'=>'Website','location'=>'Location','source'=>'Source','category'=>'Category'] as $field=>$label)
        <label>{{ $label }}{{ $field === 'name' ? ' *' : '' }}
          <input name="{{ $field }}" value="{{ $field === 'source' && ! $lead->exists ? 'Manual contact' : old($field,$lead->$field) }}" @readonly($field === 'source' && ! $lead->exists) @required($field === 'name') type="{{ $field === 'email' ? 'email' : 'text' }}" maxlength="{{ in_array($field,['phone','alt_phone','whatsapp']) ? 60 : (in_array($field,['source','category']) ? 100 : ($field === 'website' ? 500 : 255)) }}">
        </label>
      @endforeach
      <label>Job role *<select name="job_role" required><option value="" @selected(!old('job_role',$lead->job_role)) disabled>Select job role</option>@foreach($jobRoles as $jobRole)<option value="{{ $jobRole }}" @selected(old('job_role',$lead->job_role) === $jobRole)>{{ $jobRole }}</option>@endforeach</select></label>
      <label>Stage<select name="stage">@foreach(\App\Models\Lead::STAGES as $stage)<option @selected(old('stage',$lead->stage) === $stage)>{{ $stage }}</option>@endforeach</select></label>
      <label>Priority<select name="priority">@foreach(['Low','Normal','High'] as $priority)<option @selected(old('priority',$lead->priority) === $priority)>{{ $priority }}</option>@endforeach</select></label>
      <label>Estimated value<input name="value" type="number" min="0" step="0.01" value="{{ old('value',$lead->value) }}"></label>
    </div>
    <label>Notes<textarea name="notes" rows="4">{{ old('notes',$lead->notes) }}</textarea></label>
    <div class="lead-nav"><button class="button primary">Save contact</button><a class="button secondary" href="{{ $lead->exists ? route('leads.show',$lead) : route('contacts.index') }}">Cancel</a></div>
  </form>
</section>
</x-app-layout>
