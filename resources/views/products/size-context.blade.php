@if($inline ?? false)<input type="hidden" name="return_to" value="rates">@endif
<input type="hidden" name="price" value="{{ $price->id }}"><input type="hidden" name="date" value="{{ $date ?? now('Asia/Kolkata')->toDateString() }}">
