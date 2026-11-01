<aside class="toast-region" aria-label="Notifications" tabindex="-1">
    @foreach(['success' => 'Success', 'status' => 'Notice', 'warning' => 'Attention', 'error' => 'Unable to complete'] as $key => $title)
        @if(session($key))
            <div class="toast toast-{{ $key }}" role="{{ $key === 'error' ? 'alert' : 'status' }}" data-toast>
                <div><strong>{{ $title }}</strong><p>{{ session($key) }}</p></div>
                <button type="button" class="toast-close" aria-label="Dismiss notification" hidden>&times;</button>
            </div>
        @endif
    @endforeach
    @if(isset($errors) && $errors->any())
        <div class="toast toast-error" role="alert" data-toast>
            <div><strong>Please check the following</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            <button type="button" class="toast-close" aria-label="Dismiss notification" hidden>&times;</button>
        </div>
    @endif
</aside>
