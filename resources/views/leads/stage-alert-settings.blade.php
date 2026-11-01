<section class="panel lead-panel">
 <h2>Same-stage lead alerts</h2>
 <p>Notify the assigned user and all administrators when an active lead stays in the same pipeline stage for the selected number of days. Changing the lead stage resets its timer. Won and Lost leads are excluded.</p>
 <form method="POST" action="{{ route('leads.settings.save') }}">
  @csrf
  <input type="hidden" name="section" value="stage_alert">
  <input type="hidden" name="version" value="{{ $settings->version }}">
  <div class="lead-grid">
   <label>Alert after same stage (days)<input type="number" name="same_stage_alert_days" min="1" max="3650" required value="{{ old('same_stage_alert_days',$settings->same_stage_alert_days ?? 3) }}"></label>
  </div>
  <button class="button primary">Save alert duration</button>
 </form>
</section>
