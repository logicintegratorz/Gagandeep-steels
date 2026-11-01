<x-app-layout title="WhatsApp connection">
@include('leads.nav')
<section class="panel lead-panel list-page">
 <div class="lead-list-heading"><div><h2>Connect WhatsApp</h2><p>Once connected, scheduled messages go to list contacts automatically.</p></div><span class="lead-list-count">{{ $settings->whatsappReady() ? 'Connection saved' : 'Setup needed' }}</span></div>
 <div class="list-message"><strong>Before you start</strong><p>Use a Meta WhatsApp Business number and an approved message template with one text variable. The list’s message fills that variable.</p><small>A saved connection does not confirm Meta approval. Try a small list first and check Recent sends.</small></div>
 <form method="POST" action="{{ route('leads.lists.whatsapp-settings.save') }}" class="list-create">@csrf
  <div class="lead-grid">
   <label>Phone number ID<input name="phone_number_id" inputmode="numeric" value="{{ old('phone_number_id',$settings->whatsapp_config['phone_number_id']??'') }}" required></label>
   <label>API version<input name="api_version" placeholder="v24.0" value="{{ old('api_version',$settings->whatsapp_config['api_version']??'') }}" required></label>
   <label>Approved template name<input name="template_name" placeholder="list_message" value="{{ old('template_name',$settings->whatsapp_config['template_name']??'') }}" required></label>
   <label>Template language<input name="language_code" placeholder="en_US" value="{{ old('language_code',$settings->whatsapp_config['language_code']??'en_US') }}" required></label>
   <label>Access token<input name="token" type="password" autocomplete="new-password" placeholder="{{ $settings->whatsapp_token ? 'Saved — leave blank to keep it' : 'Paste token' }}"></label>
  </div>
  <button class="button primary">Save WhatsApp connection</button>
 </form>
 <p class="lead-section-help">Meta first accepts a send request; delivery to the contact may still fail. Check your Meta dashboard for final delivery status.</p>
</section>
</x-app-layout>
