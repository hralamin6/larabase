@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or continue with email'),
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                Livewire.navigate(response.redirect || '/dashboard');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div>
            <div class="grid gap-2">
                <button
                    type="button"
                    class="btn btn-outline w-full gap-2"
                    x-on:click="verify()"
                    x-bind:disabled="loading"
                >
                    <x-icon name="o-finger-print" class="w-5 h-5" />
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>
                        <span class="loading loading-spinner loading-sm"></span>
                        {{ $loadingLabel }}
                    </span>
                </button>
                <p x-show="error" x-text="error" x-cloak
                   class="text-sm text-center text-error"></p>
            </div>

            <div class="divider text-xs text-base-content/50 uppercase">{{ $separator }}</div>
        </div>
    </template>
</div>
