<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth.card')] #[Title('Confirm password')] class extends Component {

    public string $password = '';

    /**
     * Confirm the user's password.
     */
    public function confirmPassword(): void
    {
        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Confirm password')"
        :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <x-passkey-verify
        options-route="passkey.confirm-options"
        submit-route="passkey.confirm"
        :label="__('Confirm with passkey')"
        :loading-label="__('Confirming...')"
        :separator="__('Or confirm with password')"
    />

    <form wire:submit="confirmPassword" class="flex flex-col gap-4">
        <div class="form-control w-full">
            <label class="label" for="password">
                <span class="label-text font-medium">{{ __('Password') }}</span>
            </label>
            <input
                id="password"
                type="password"
                class="input input-bordered w-full @error('password') input-error @enderror"
                wire:model="password"
                required
                autocomplete="current-password"
                placeholder="{{ __('Password') }}"
            />
            @error('password')
                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="confirm-password-button">
            <span wire:loading.remove wire:target="confirmPassword">{{ __('Confirm') }}</span>
            <span wire:loading wire:target="confirmPassword" class="flex items-center justify-center gap-2">
                <span class="loading loading-spinner loading-xs"></span> {{ __('Confirming...') }}
            </span>
        </button>
    </form>
</div>
