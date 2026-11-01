@php([$metreWeight,$feetWeight] = \App\Support\WeightDisplay::parts($weightValue ?? ''))
<div class="weight-pair" data-weight-pair>
<input type="hidden" name="weight" value="{{ $weightValue ?? '' }}" data-weight-value>
<label>Weight (kg/mtr)<input type="text" inputmode="decimal" data-weight-metre value="{{ $metreWeight }}" placeholder="kg/mtr"></label>
<label>Weight (kg/feet)<input type="text" inputmode="decimal" data-weight-feet value="{{ $feetWeight }}" placeholder="kg/feet"></label>
@if($metreWeight === '' && filled($weightValue ?? null))<small data-weight-legacy>Saved weight: {{ $weightValue }}. Enter a value to replace it.</small>@endif
</div>
