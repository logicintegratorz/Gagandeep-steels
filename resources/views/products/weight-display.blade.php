@php([$metres, $feet] = \App\Support\WeightDisplay::parts($weightValue ?? ''))
<span class="weight-display">
@if($metres !== '')
<span class="weight-line"><span>{{ $metres }}</span> <small>kg/mtr</small></span>
<span class="weight-line"><span>{{ $feet }}</span> <small>kg/feet</small></span>
@else
<span>{{ $weightValue ?: '—' }}</span>
@endif
</span>
