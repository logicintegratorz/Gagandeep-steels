@props(['id', 'names', 'selected' => '', 'required' => false, 'placeholder' => 'Select transporter', 'label' => 'Transporter'])
<div class="searchable-select" data-searchable-select data-options-url="{{ route('transporters.options') }}">
    <select id="{{ $id }}" name="transporter" aria-label="{{ $label }}" @required($required) @if($required && $errors->has('transporter')) aria-invalid="true" @endif>
        <option value="">{{ $placeholder }}</option>
        @foreach($names as $name)
            <option value="{{ $name }}" @selected((string) $selected === (string) $name)>{{ $name }}</option>
        @endforeach
    </select>
</div>
