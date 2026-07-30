<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth.card')] #[Title('Register')] class extends Component {

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    {{-- Session Status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-4">
        {{-- Name --}}
        <div class="form-control w-full">
            <label class="label" for="name">
                <span class="label-text font-medium">{{ __('Name') }}</span>
            </label>
            <input
                id="name"
                type="text"
                class="input input-bordered w-full @error('name') input-error @enderror"
                wire:model="name"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Full name') }}"
            />
            @error('name')
                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
            @enderror
        </div>

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
                autocomplete="email"
                placeholder="email@example.com"
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
            <button type="submit" class="btn btn-primary w-full" data-test="register-user-button">
                <span wire:loading.remove wire:target="register">{{ __('Create account') }}</span>
                <span wire:loading wire:target="register" class="flex items-center justify-center gap-2">
                    <span class="loading loading-spinner loading-xs"></span> {{ __('Creating account...') }}
                </span>
            </button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-base-content/60">
        <span>{{ __('Already have an account?') }}</span>
        <a href="{{ route('login') }}" class="link link-primary" wire:navigate>{{ __('Log in') }}</a>
    </div>
</div>
