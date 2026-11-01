<x-app-layout :title="'WhatsApp · '.$list->name">
@include('leads.nav')
<div class="lead-detail-back"><a href="{{ route('leads.lists.show',$list) }}">← Back to {{ $list->name }}</a></div>
<section class="panel lead-panel list-page">
 <div class="lead-list-heading"><div><h2>Send to selected contacts</h2><p>{{ $contacts->count() }} selected {{ \Illuminate\Support\Str::plural('contact',$contacts->count()) }}. WhatsApp opens one contact at a time; a single click cannot send to everyone without its API.</p></div></div>
 <div class="list-message"><small>Message</small><p>{{ $list->description }}</p></div>
 <p class="lead-section-help">1. Open the next contact’s chat. 2. Press Send in WhatsApp. 3. Return to this page and open the next chat. Repeat until every contact is done. CRM can track chats opened, but cannot confirm which messages you sent.</p>
 @if($contacts->contains(fn($contact)=>filled($contact['url'])))
 <div class="whatsapp-queue" id="whatsapp-queue" data-queue-key="whatsapp-list-{{ $list->id }}-{{ hash('sha256',$contacts->pluck('lead.id')->implode(',').'|'.$list->description) }}"><div><strong id="whatsapp-next-label" aria-live="polite">Ready for the first contact</strong><small><output id="whatsapp-opened-count">0</output> of {{ $contacts->filter(fn($contact)=>filled($contact['url']))->count() }} chats opened. Opened does not mean sent.</small></div><div class="whatsapp-queue-actions"><a class="button primary" id="whatsapp-next" href="#" target="_blank" rel="noopener noreferrer">Open next chat ↗</a><button type="button" class="button secondary" id="whatsapp-reset" hidden>Start over</button></div></div>
 @endif
 <div class="list-automation-cards whatsapp-contact-list" id="whatsapp-contact-list">
 @foreach($contacts as $contact)
 <article @if($contact['url']) data-whatsapp-contact data-whatsapp-id="{{ $contact['lead']->id }}" data-whatsapp-url="{{ $contact['url'] }}" @endif><strong>{{ $contact['lead']->name }}</strong><small>{{ $contact['lead']->company ?: 'No company' }} · {{ $contact['lead']->whatsapp ?: ($contact['lead']->phone ?: 'No number') }}</small>
 @if($contact['url'])<a class="button secondary" data-whatsapp-open href="{{ $contact['url'] }}" target="_blank" rel="noopener noreferrer">Open this chat ↗</a><small data-whatsapp-status>Not opened</small>
 @else<small>Enter a valid Indian mobile number in this lead’s WhatsApp or Phone field.</small><a class="button secondary" href="{{ route('leads.edit',$contact['lead']) }}">Edit lead</a>@endif</article>
 @endforeach
 </div>
</section>
<script src="{{ asset('js/lead-whatsapp-links.js').'?v='.filemtime(public_path('js/lead-whatsapp-links.js')) }}" defer></script>
</x-app-layout>
