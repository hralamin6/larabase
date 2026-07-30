<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth.card')] #[Title('Email verification')] class extends Component {

    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="mt-4 flex flex-col gap-6">
    <p class="text-sm text-center text-base-content/60">
        {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <p class="text-sm text-center font-medium text-success">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </p>
    @endif

    <div class="flex flex-col items-center justify-between space-y-3">
        <form wire:submit="sendVerification">
            <button type="submit" class="btn btn-primary w-full">
                <span wire:loading.remove wire:target="sendVerification">{{ __('Resend verification email') }}</span>
                <span wire:loading wire:target="sendVerification" class="flex items-center justify-center gap-2">
                    <span class="loading loading-spinner loading-xs"></span> {{ __('Sending...') }}
                </span>
            </button>
        </form>

        <form wire:submit="logout">
            <button type="submit" class="btn btn-ghost btn-sm text-sm cursor-pointer" data-test="logout-button">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</div>
