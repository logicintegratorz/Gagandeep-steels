
<x-app-layout title="Send notification">

    <div class="page-heading">
        <div>
            <h1>Send notification<span>.</span></h1>
        </div>
    </div>

    <x-notification-nav/>

    <section class="panel">
        <div class="panel-heading">
            <span class="section-icon">
                <x-icon name="bell"/>
            </span>
            <div>
                <h2>Compose message</h2>
                <p>Only active accounts receive notifications. Messages appear in their CRM inbox.</p>
            </div>
        </div>

        <form method="POST"
              action="{{ route('broadcasts.store') }}"
              class="transporter-form broadcast-form"
              data-save-form>

            @csrf

            <input type="hidden"
                   name="submission_key"
                   value="{{ old('submission_key', (string) \Illuminate\Support\Str::uuid()) }}">

            <!-- Automatically send to selected users -->
            <input type="hidden" name="audience" value="users">

            <!-- Notification Title -->
            <label class="address-field">
                Title
                <input type="text"
                       name="title"
                       value="{{ old('title') }}"
                       maxlength="120"
                       placeholder="e.g. Tomorrow’s dispatch schedule">
            </label>

            <!-- Notification Message -->
            <label class="address-field">
                Message *
                <textarea name="message"
                          rows="5"
                          maxlength="2000"
                          required
                          placeholder="Write a clear message for your team…">{{ old('message') }}</textarea>

                <small class="cell-note">Up to 2,000 characters.</small>
            </label>

            <!-- Select Users -->
            <fieldset class="recipient-picker address-field"
                      data-audience-panel="users">

                <legend>Select users</legend>

                <label>
                    
                    <input type="search"
                           data-recipient-search
                           placeholder="Search name or role…">
                </label>

                <div class="recipient-options">

                    @foreach($recipients as $recipient)

                        <label class="permission-check"
                               data-recipient-option>

                            <input type="checkbox"
                                   name="user_ids[]"
                                   value="{{ $recipient->id }}"
                                   @checked(in_array($recipient->id, old('user_ids', [])))>

                            <span>
                                {{ $recipient->name }}

                                <small class="cell-note">
                                    {{ $recipient->role->name }}
                                </small>
                            </span>

                        </label>

                    @endforeach

                </div>

                <p class="cell-note">
                    Only checked users receive this notification.
                    Searching does not clear selections.
                </p>

            </fieldset>

            <!-- Form Actions -->
            <div class="form-actions">

                <span class="form-hint" data-audience-hint>
                    Review the message and selected recipients before sending.
                </span>

                <a class="button secondary"
                   href="{{ route('notifications.index') }}">
                    Cancel
                </a>

                <button type="submit"
                        class="button primary"
                        data-submit>
                    <span>Send notification</span>
                </button>

            </div>

        </form>
    </section>

    <script src="{{ asset('js/notification-compose.js') }}" defer></script>

</x-app-layout>
