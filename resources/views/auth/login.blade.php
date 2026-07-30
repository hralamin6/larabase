<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth.card')] #[Title('Log in')] class extends Component {

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        $user = Auth::getProvider()->retrieveByCredentials($this->only('email'));

        if (! $user || ! Auth::getProvider()->validateCredentials($user, ['password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (Features::enabled(Features::twoFactorAuthentication()) && $user->hasEnabledTwoFactorAuthentication()) {
            Session::put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            $this->redirect(route('two-factor.login'), navigate: true);
            return;
        }

        RateLimiter::clear($this->throttleKey());
        Auth::login($user, $this->remember);
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }


    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    {{-- Session Status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    <x-passkey-verify />

    <form wire:submit="login" class="flex flex-col gap-4">
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
                autocomplete="email"
                placeholder="email@example.com"
            />
            @error('email')
                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
            @enderror
        </div>

        {{-- Password --}}
        <div class="form-control w-full">
            <div class="flex items-center justify-between">
                <label class="label" for="password">
                    <span class="label-text font-medium">{{ __('Password') }}</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link link-primary text-sm" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
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

        {{-- Remember Me --}}
        <div class="form-control">
            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" wire:model="remember" class="checkbox checkbox-primary checkbox-sm" />
                <span class="label-text">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="btn btn-primary w-full" data-test="login-button">
                <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
                <span wire:loading wire:target="login" class="flex items-center justify-center gap-2">
                    <span class="loading loading-spinner loading-xs"></span> {{ __('Logging in...') }}
                </span>
            </button>
        </div>
    </form>

    <div class="space-x-1 text-sm text-center text-base-content/60">
        <span>{{ __("Don't have an account?") }}</span>
        <a href="{{ route('register') }}" class="link link-primary" wire:navigate>{{ __('Sign up') }}</a>
    </div>
</div>
