<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth.card')] #[Title('Two-factor authentication')] class extends Component {

    public string $code = '';
    public string $recovery_code = '';

    /**
     * Authenticate the user using two-factor authentication.
     */
    public function twoFactorLogin(): void
    {
        $userId = session('login.id');

        if (! $userId || ! ($user = User::find($userId))) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        if ($this->recovery_code) {
            $this->validate(['recovery_code' => ['required', 'string']]);

            if (! $this->validRecoveryCode($user, $this->recovery_code)) {
                throw ValidationException::withMessages([
                    'recovery_code' => __('The provided recovery code was invalid.'),
                ]);
            }

            $user->replaceRecoveryCode($this->recovery_code);
        } else {
            $this->validate(['code' => ['required', 'string']]);

            if (! $this->validOtpCode($user, $this->code)) {
                throw ValidationException::withMessages([
                    'code' => __('The provided two factor authentication code was invalid.'),
                ]);
            }
        }

        Auth::login($user, session('login.remember', false));

        session()->forget(['login.id', 'login.remember']);
        session()->regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    protected function validRecoveryCode($user, string $recoveryCode): bool
    {
        return $recoveryCode && collect($user->recoveryCodes())->first(function ($code) use ($recoveryCode) {
            return hash_equals($code, $recoveryCode);
        });
    }

    protected function validOtpCode($user, string $code): bool
    {
        return $code && app(TwoFactorAuthenticationProvider::class)->verify(
            decrypt($user->two_factor_secret), $code
        );
    }
}; ?>

<div class="flex flex-col gap-6">
    <div
        class="relative w-full h-auto"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            digits: ['', '', '', '', '', ''],
            focusOtp() {
                this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
            },
            init() {
                if (! this.showRecoveryInput) {
                    this.focusOtp();
                }
            },
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;
                this.code = '';
                this.recovery_code = '';
                this.digits = ['', '', '', '', '', ''];
                $nextTick(() => {
                    this.showRecoveryInput
                        ? this.$refs.recovery_code?.focus()
                        : this.focusOtp();
                });
            },
            onDigitInput(index, event) {
                const val = event.target.value.replace(/\D/g, '').slice(-1);
                this.digits[index] = val;
                this.code = this.digits.join('');
                $wire.code = this.code;
                if (val && index < 5) {
                    this.$refs['digit_' + (index + 1)]?.focus();
                }
            },
            onKeydown(index, event) {
                if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                    this.$refs['digit_' + (index - 1)]?.focus();
                }
            },
            onPaste(event) {
                const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                pasted.split('').forEach((ch, i) => { this.digits[i] = ch; });
                this.code = this.digits.join('');
                $wire.code = this.code;
                const lastFilled = Math.min(pasted.length, 5);
                this.$nextTick(() => this.$refs['digit_' + lastFilled]?.focus());
                event.preventDefault();
            },
        }"
    >
        <div x-show="!showRecoveryInput">
            <x-auth-header
                :title="__('Authentication code')"
                :description="__('Enter the authentication code provided by your authenticator application.')"
            />
        </div>

        <div x-show="showRecoveryInput">
            <x-auth-header
                :title="__('Recovery code')"
                :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
            />
        </div>

        <form wire:submit="twoFactorLogin">
            <div class="space-y-5 text-center mt-4">
                {{-- OTP input --}}
                <div x-show="!showRecoveryInput">
                    <div class="flex items-center justify-center gap-2 my-5" x-ref="otp" @paste="onPaste($event)">
                        @for ($i = 0; $i < 6; $i++)
                            <input
                                type="text"
                                inputmode="numeric"
                                maxlength="1"
                                x-ref="digit_{{ $i }}"
                                x-model="digits[{{ $i }}]"
                                @input="onDigitInput({{ $i }}, $event)"
                                @keydown="onKeydown({{ $i }}, $event)"
                                class="input input-bordered w-11 h-12 text-center text-lg font-semibold focus:input-primary"
                                autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                            />
                        @endfor
                    </div>
                    @error('code')
                        <p class="text-sm text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Recovery code input --}}
                <div x-show="showRecoveryInput">
                    <div class="my-5 form-control w-full">
                        <input
                            type="text"
                            class="input input-bordered w-full @error('recovery_code') input-error @enderror"
                            x-ref="recovery_code"
                            wire:model="recovery_code"
                            autocomplete="one-time-code"
                        />
                        @error('recovery_code')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-full"
                >
                    <span wire:loading.remove wire:target="twoFactorLogin">{{ __('Continue') }}</span>
                    <span wire:loading wire:target="twoFactorLogin" class="flex items-center justify-center gap-2">
                        <span class="loading loading-spinner loading-xs"></span> {{ __('Verifying...') }}
                    </span>
                </button>
            </div>

            <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                <span class="opacity-50">{{ __('or you can') }}</span>
                <div class="inline font-medium underline cursor-pointer opacity-80">
                    <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                    <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                </div>
            </div>
        </form>
    </div>
</div>
