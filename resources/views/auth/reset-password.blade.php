<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth.card')] #[Title('Reset password')] class extends Component {

    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token = ''): void
    {
        $this->token = $token ?: (request()->route('token') ?? '');
        $this->email = request('email', '');
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __($status));
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $this->addError('email', __($status));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

    {{-- Session Status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-4">
        <input type="hidden" wire:model="token">

        {{-- Email Address --}}
        <div class="form-control w-full">
            <label class="label" for="email">
                <span class="label-text font-medium">{{ __('Email') }}</span>
            </label>
            <input
                id="email"
                type="email"
                class="input input-bordered w-full @error('email') input-error @enderror"
                wire:model="email"
                required
                autocomplete="email"
            />
            @error('email')
                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
            @enderror
        </div>

        {{-- Password --}}
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
                autocomplete="new-password"
                placeholder="{{ __('Password') }}"
            />
            @error('password')
                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="form-control w-full">
            <label class="label" for="password_confirmation">
                <span class="label-text font-medium">{{ __('Confirm password') }}</span>
            </label>
            <input
                id="password_confirmation"
                type="password"
                class="input input-bordered w-full @error('password_confirmation') input-error @enderror"
                wire:model="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm password') }}"
            />
            @error('password_confirmation')
                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
            @enderror
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="btn btn-primary w-full" data-test="reset-password-button">
                <span wire:loading.remove wire:target="resetPassword">{{ __('Reset password') }}</span>
                <span wire:loading wire:target="resetPassword" class="flex items-center justify-center gap-2">
                    <span class="loading loading-spinner loading-xs"></span> {{ __('Resetting password...') }}
                </span>
            </button>
        </div>
    </form>
</div>
