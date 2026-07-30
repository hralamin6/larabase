<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth.card')] #[Title('Forgot password')] class extends Component {

    #[Validate('required|email')]
    public string $email = '';

    /**
     * Send a password reset link to the given user.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink($this->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('status', __($status));
            return;
        }

        $this->addError('email', __($status));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

    {{-- Session Status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-4">
        {{-- Email Address --}}
        <div class="form-control w-full">
            <label class="label" for="email">
                <span class="label-text font-medium">{{ __('Email address') }}</span>
            </label>
            <input
                id="email"
                type="email"
                class="input input-bordered w-full @error('email') input-error @enderror"
                wire:model="email"
                required
                autofocus
                placeholder="email@example.com"
            />
            @error('email')
                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" data-test="email-password-reset-link-button">
            <span wire:loading.remove wire:target="sendPasswordResetLink">{{ __('Email password reset link') }}</span>
            <span wire:loading wire:target="sendPasswordResetLink" class="flex items-center justify-center gap-2">
                <span class="loading loading-spinner loading-xs"></span> {{ __('Sending...') }}
            </span>
        </button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-base-content/60">
        <span>{{ __('Or, return to') }}</span>
        <a href="{{ route('login') }}" class="link link-primary" wire:navigate>{{ __('log in') }}</a>
    </div>
</div>
