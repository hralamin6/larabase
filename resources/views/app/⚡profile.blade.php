<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('My Profile')] class extends Component {
    use PasswordValidationRules;
    use ProfileValidationRules;
    use Toast;

    // Profile Information
    public string $name = '';

    public string $email = '';

    // Password Update
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Two-Factor Authentication
    public bool $canManageTwoFactor = false;

    public bool $twoFactorEnabled = false;

    public bool $requiresConfirmation = false;

    public bool $show2faModal = false;

    public string $twoFactorQrCodeSvg = '';

    public string $twoFactorSetupKey = '';

    public string $twoFactorCode = '';

    public bool $showRecoveryCodesModal = false;

    public array $twoFactorRecoveryCodes = [];

    // Passkeys
    #[Locked]
    public bool $canManagePasskeys = false;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeletePasskeyModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    // Session Management
    public bool $showLogoutOtherSessionsModal = false;

    public string $logoutOtherSessionsPassword = '';

    public bool $showRemoveSessionModal = false;

    #[Locked]
    public ?string $removingSessionId = null;

    #[Locked]
    public string $removingSessionDevice = '';

    #[Locked]
    public bool $removingSessionIsCurrent = false;

    // Delete Account
    public bool $showDeleteAccountModal = false;

    public string $deleteAccountPassword = '';

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;

        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null($user->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication($user);
            }

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->success(__('Profile information updated successfully.'));
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset(['current_password', 'password', 'password_confirmation']);

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->success(__('Password updated successfully.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    // 2FA Actions
    public function startTwoFactorSetup(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $user = Auth::user();
        $enableTwoFactorAuthentication($user);

        $this->twoFactorQrCodeSvg = $user->twoFactorQrCodeSvg();
        $this->twoFactorSetupKey = decrypt($user->two_factor_secret);
        $this->twoFactorCode = '';
        $this->resetErrorBag();
        $this->show2faModal = true;
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate([
            'twoFactorCode' => 'required|string|size:6',
        ]);

        try {
            $confirmTwoFactorAuthentication(Auth::user(), trim($this->twoFactorCode));

            $this->twoFactorEnabled = true;
            $this->show2faModal = false;
            $this->twoFactorCode = '';
            $this->resetErrorBag();

            $user = Auth::user()->fresh();
            if ($user->two_factor_recovery_codes) {
                $this->twoFactorRecoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
                $this->showRecoveryCodesModal = true;
            }

            $this->success(__('Two-factor authentication enabled successfully!'));
        } catch (ValidationException $e) {
            $this->resetErrorBag();
            $this->addError('twoFactorCode', __('The provided two-factor authentication code was invalid.'));
        }
    }

    public function disableTwoFactor(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(Auth::user());

        $this->twoFactorEnabled = false;
        $this->show2faModal = false;
        $this->twoFactorCode = '';
        $this->resetErrorBag();

        $this->success(__('Two-factor authentication disabled.'));
    }

    public function close2faModal(): void
    {
        $this->show2faModal = false;
        $this->twoFactorCode = '';
        $this->resetErrorBag();
    }

    public function showRecoveryCodes(): void
    {
        $user = Auth::user();

        if ($user->two_factor_recovery_codes) {
            $this->twoFactorRecoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
            $this->showRecoveryCodesModal = true;
        }
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $user = Auth::user();
        $generateNewRecoveryCodes($user);

        $freshUser = $user->fresh();
        $this->twoFactorRecoveryCodes = json_decode(decrypt($freshUser->two_factor_recovery_codes), true) ?? [];

        $this->success(__('Recovery codes regenerated successfully.'));
    }

    public function closeRecoveryCodesModal(): void
    {
        $this->showRecoveryCodesModal = false;
    }

    // Passkey Actions
    public function confirmPasskeyDeletion(int $id, string $name): void
    {
        $this->deletingPasskeyId = $id;
        $this->deletingPasskeyName = $name;
        $this->showDeletePasskeyModal = true;
    }

    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $deletePasskey(Auth::user(), $this->deletingPasskeyId);

        $this->showDeletePasskeyModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';

        $this->loadPasskeys();

        $this->success(__('Passkey deleted successfully.'));
    }

    public function closeDeletePasskeyModal(): void
    {
        $this->showDeletePasskeyModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    #[On('passkey-registered')]
    public function loadPasskeys(): void
    {
        if ($this->canManagePasskeys) {
            $this->passkeys = Auth::user()->passkeys()
                ->select(['id', 'name', 'last_used_at', 'created_at'])
                ->latest()
                ->get()
                ->toArray();
        }
    }

    // Session Management Actions
    #[Computed]
    public function sessions(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                $agent = $this->parseUserAgent($session->user_agent);

                return (object) [
                    'id' => $session->id,
                    'short_id' => substr($session->id, 0, 8).'...'.substr($session->id, -4),
                    'agent' => $agent,
                    'ip_address' => $session->ip_address ?? '127.0.0.1',
                    'is_current_device' => $session->id === request()->session()->getId(),
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'last_active_exact' => Carbon::createFromTimestamp($session->last_activity)->format('M d, Y H:i:s'),
                ];
            })->all();
    }

    protected function parseUserAgent(?string $userAgent): object
    {
        if (! $userAgent) {
            return (object) [
                'is_desktop' => true,
                'platform' => __('Unknown OS'),
                'browser' => __('Unknown Browser'),
                'raw' => __('No User-Agent string available'),
            ];
        }

        $isDesktop = ! preg_match('/Mobile|Android|iPhone|iPad|iPod|Opera Mini|IEMobile/i', $userAgent);

        $platform = __('Unknown OS');
        if (preg_match('/Windows NT 10\.0/i', $userAgent)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6\.1/i', $userAgent)) {
            $platform = 'Windows 7';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux x86_64/i', $userAgent)) {
            $platform = 'Linux 64-bit';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iPhone/i', $userAgent)) {
            $platform = 'iPhone (iOS)';
        } elseif (preg_match('/iPad/i', $userAgent)) {
            $platform = 'iPad (iPadOS)';
        }

        $browser = __('Unknown Browser');
        if (preg_match('/Edg\/([0-9\.]+)/i', $userAgent, $m)) {
            $browser = 'Microsoft Edge '.explode('.', $m[1])[0];
        } elseif (preg_match('/Chrome\/([0-9\.]+)/i', $userAgent, $m) && ! preg_match('/Edg/i', $userAgent)) {
            $browser = 'Chrome '.explode('.', $m[1])[0];
        } elseif (preg_match('/Firefox\/([0-9\.]+)/i', $userAgent, $m)) {
            $browser = 'Firefox '.explode('.', $m[1])[0];
        } elseif (preg_match('/Safari\/([0-9\.]+)/i', $userAgent, $m) && ! preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera|OPR\/([0-9\.]+)/i', $userAgent, $m)) {
            $browser = 'Opera';
        }

        return (object) [
            'is_desktop' => $isDesktop,
            'platform' => $platform,
            'browser' => $browser,
            'raw' => $userAgent,
        ];
    }

    public function confirmRemoveSession(string $id, string $device, bool $isCurrent): void
    {
        $this->removingSessionId = $id;
        $this->removingSessionDevice = $device;
        $this->removingSessionIsCurrent = $isCurrent;
        $this->showRemoveSessionModal = true;
    }

    public function removeSession(): void
    {
        if (! $this->removingSessionId) {
            return;
        }

        if ($this->removingSessionIsCurrent) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            $this->redirect('/', navigate: true);

            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->where('id', $this->removingSessionId)
            ->delete();

        $this->showRemoveSessionModal = false;
        $this->removingSessionId = null;
        $this->removingSessionDevice = '';
        $this->removingSessionIsCurrent = false;

        $this->success(__('Session revoked successfully.'));
    }

    public function closeRemoveSessionModal(): void
    {
        $this->showRemoveSessionModal = false;
        $this->removingSessionId = null;
        $this->removingSessionDevice = '';
        $this->removingSessionIsCurrent = false;
    }

    public function logoutOtherBrowserSessions(): void
    {
        $this->validate([
            'logoutOtherSessionsPassword' => 'required|current_password',
        ]);

        Auth::logoutOtherDevices($this->logoutOtherSessionsPassword);

        $this->deleteOtherSessionRecords();

        $this->logoutOtherSessionsPassword = '';
        $this->showLogoutOtherSessionsModal = false;
        $this->resetErrorBag();

        $this->success(__('Logged out of all other browser sessions successfully.'));
    }

    protected function deleteOtherSessionRecords(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }

    public function closeLogoutOtherSessionsModal(): void
    {
        $this->showLogoutOtherSessionsModal = false;
        $this->logoutOtherSessionsPassword = '';
        $this->resetErrorBag();
    }

    // Delete Account Actions
    public function deleteAccount(): void
    {
        $this->validate([
            'deleteAccountPassword' => 'required|current_password',
        ]);

        $user = Auth::user();
        Auth::logout();
        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/', navigate: true);
    }

    public function closeDeleteAccountModal(): void
    {
        $this->showDeleteAccountModal = false;
        $this->deleteAccountPassword = '';
        $this->resetErrorBag();
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

<section x-cloak class="w-full space-y-4 relative"
    x-data="{
        tab: $persist('profile').as('profile-tab'),
        theme: $persist('light').as('mary-theme'),
        darkThemes: ['dark', 'synthwave', 'halloween', 'forest', 'black', 'luxury', 'dracula', 'business', 'night', 'coffee', 'dim', 'sunset', 'abyss'],
        setTheme(value) {
            this.theme = value;
            document.documentElement.setAttribute('data-theme', value);
            var isDark = this.darkThemes.includes(value);
            var themeClass = isDark ? 'dark' : 'light';
            document.documentElement.setAttribute('class', themeClass);
            this.$dispatch('theme-changed', value);
        }
    }"
>
    {{-- Standalone Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-base-300 pb-3">
        <div class="flex items-center gap-3">
            <div class="avatar placeholder">
                <div class="bg-gradient-to-tr from-primary to-primary-focus text-primary-content rounded-xl w-11 h-11 flex items-center justify-center font-bold text-base shadow-md shadow-primary/20">
                    <span>{{ auth()->user()->initials() }}</span>
                </div>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-base-content flex items-center gap-2">
                    <span>{{ auth()->user()->name }}</span>
                    @if(auth()->user()->hasRole('Super Admin'))
                        <span class="badge badge-sm badge-primary font-semibold">{{ __('Super Admin') }}</span>
                    @endif
                </h1>
                <p class="text-xs sm:text-sm text-base-content/60 mt-0.5">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
    </div>

    {{-- Main Tabbed Layout Container --}}
    <div class="space-y-4 w-full">
        {{-- Navigation Tabs with Persistence --}}
        <div class="flex items-center gap-1.5 border-b border-base-300 pb-2 overflow-x-auto">
            <button
                type="button"
                class="btn btn-xs sm:btn-sm rounded-lg transition-all"
                :class="tab === 'profile' ? 'btn-primary' : 'btn-ghost'"
                @click="tab = 'profile'"
            >
                <x-icon name="o-user" class="w-3.5 h-3.5" />
                <span>{{ __('Profile Info') }}</span>
            </button>

            <button
                type="button"
                class="btn btn-xs sm:btn-sm rounded-lg transition-all"
                :class="tab === 'security' ? 'btn-primary' : 'btn-ghost'"
                @click="tab = 'security'"
            >
                <x-icon name="o-shield-check" class="w-3.5 h-3.5" />
                <span>{{ __('Security & Password') }}</span>
            </button>

            <button
                type="button"
                class="btn btn-xs sm:btn-sm rounded-lg transition-all"
                :class="tab === 'sessions' ? 'btn-primary' : 'btn-ghost'"
                @click="tab = 'sessions'"
            >
                <x-icon name="o-computer-desktop" class="w-3.5 h-3.5" />
                <span>{{ __('Sessions') }}</span>
            </button>

            @if($canManagePasskeys)
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm rounded-lg transition-all"
                    :class="tab === 'passkeys' ? 'btn-primary' : 'btn-ghost'"
                    @click="tab = 'passkeys'"
                >
                    <x-icon name="o-key" class="w-3.5 h-3.5" />
                    <span>{{ __('Passkeys') }}</span>
                </button>
            @endif

            <button
                type="button"
                class="btn btn-xs sm:btn-sm rounded-lg transition-all"
                :class="tab === 'appearance' ? 'btn-primary' : 'btn-ghost'"
                @click="tab = 'appearance'"
            >
                <x-icon name="o-swatch" class="w-3.5 h-3.5" />
                <span>{{ __('Appearance') }}</span>
            </button>

            <button
                type="button"
                class="btn btn-xs sm:btn-sm rounded-lg text-error transition-all"
                :class="tab === 'danger' ? 'btn-error btn-outline' : 'btn-ghost'"
                @click="tab = 'danger'"
            >
                <x-icon name="o-exclamation-triangle" class="w-3.5 h-3.5" />
                <span>{{ __('Delete Account') }}</span>
            </button>
        </div>

        {{-- TAB 1: Profile Information --}}
        <div x-show="tab === 'profile'" class="space-y-4 w-full" x-cloak>
            <div class="card bg-base-100 border border-base-300 shadow-2xs rounded-xl p-4 sm:p-6 w-full max-w-3xl">
                <h3 class="font-bold text-base text-base-content mb-1">{{ __('Profile Information') }}</h3>
                <p class="text-xs text-base-content/60 mb-4">{{ __('Update your account name and email address.') }}</p>

                <form wire:submit.prevent="updateProfileInformation" class="space-y-4">
                    <div class="form-control w-full">
                        <label class="label py-1" for="profile-name">
                            <span class="label-text text-xs font-semibold text-base-content">{{ __('Name') }}</span>
                        </label>
                        <input
                            id="profile-name"
                            type="text"
                            class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm @error('name') input-error @enderror"
                            wire:model="name"
                            required
                            autocomplete="name"
                        />
                        @error('name')
                            <span class="text-xs text-error mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label py-1" for="profile-email">
                            <span class="label-text text-xs font-semibold text-base-content">{{ __('Email Address') }}</span>
                        </label>
                        <input
                            id="profile-email"
                            type="email"
                            class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm @error('email') input-error @enderror"
                            wire:model="email"
                            required
                            autocomplete="email"
                        />
                        @error('email')
                            <span class="text-xs text-error mt-1">{{ $message }}</span>
                        @enderror

                        @if ($this->hasUnverifiedEmail)
                            <div class="mt-3 p-3 rounded-lg bg-warning/10 border border-warning/20">
                                <p class="text-xs text-warning-content">
                                    {{ __('Your email address is unverified.') }}
                                    <button
                                        type="button"
                                        class="link link-primary font-semibold ms-1"
                                        wire:click.prevent="resendVerificationNotification"
                                    >
                                        {{ __('Click here to re-send the verification email.') }}
                                    </button>
                                </p>

                                @if (session('status') === 'verification-link-sent')
                                    <p class="mt-1 text-xs font-medium text-success">
                                        {{ __('A new verification link has been sent to your email address.') }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                            wire:loading.attr="disabled"
                            wire:target="updateProfileInformation"
                        >
                            <span wire:loading wire:target="updateProfileInformation" class="loading loading-spinner loading-xs"></span>
                            <x-icon wire:loading.remove wire:target="updateProfileInformation" name="o-check" class="w-3.5 h-3.5" />
                            <span>{{ __('Save Changes') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TAB 2: Security & Password --}}
        <div x-show="tab === 'security'" class="space-y-4 w-full" x-cloak>
            {{-- Update Password Card --}}
            <div class="card bg-base-100 border border-base-300 shadow-2xs rounded-xl p-4 sm:p-6 w-full max-w-3xl space-y-4">
                <div>
                    <h3 class="font-bold text-base text-base-content mb-1">{{ __('Update Password') }}</h3>
                    <p class="text-xs text-base-content/60">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>
                </div>

                <form wire:submit.prevent="updatePassword" class="space-y-4">
                    <div class="form-control w-full">
                        <label class="label py-1" for="current_password">
                            <span class="label-text text-xs font-semibold text-base-content">{{ __('Current Password') }}</span>
                        </label>
                        <input
                            id="current_password"
                            type="password"
                            class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm @error('current_password') input-error @enderror"
                            wire:model="current_password"
                            required
                            autocomplete="current-password"
                        />
                        @error('current_password')
                            <span class="text-xs text-error mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label py-1" for="new_password">
                            <span class="label-text text-xs font-semibold text-base-content">{{ __('New Password') }}</span>
                        </label>
                        <input
                            id="new_password"
                            type="password"
                            class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm @error('password') input-error @enderror"
                            wire:model="password"
                            required
                            autocomplete="new-password"
                        />
                        @error('password')
                            <span class="text-xs text-error mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label py-1" for="password_confirmation">
                            <span class="label-text text-xs font-semibold text-base-content">{{ __('Confirm Password') }}</span>
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm"
                            wire:model="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                            wire:loading.attr="disabled"
                            wire:target="updatePassword"
                        >
                            <span wire:loading wire:target="updatePassword" class="loading loading-spinner loading-xs"></span>
                            <x-icon wire:loading.remove wire:target="updatePassword" name="o-key" class="w-3.5 h-3.5" />
                            <span>{{ __('Update Password') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Two-Factor Authentication Card --}}
            @if ($canManageTwoFactor)
                <div class="card bg-base-100 border border-base-300 shadow-2xs rounded-xl p-4 sm:p-6 w-full max-w-3xl space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-base text-base-content mb-1 flex items-center gap-2">
                                <span>{{ __('Two-Factor Authentication') }}</span>
                                @if ($twoFactorEnabled)
                                    <span class="badge badge-sm badge-success font-semibold">{{ __('Enabled') }}</span>
                                @else
                                    <span class="badge badge-sm badge-neutral font-semibold">{{ __('Disabled') }}</span>
                                @endif
                            </h3>
                            <p class="text-xs text-base-content/60">
                                {{ __('Add additional security to your account using two-factor authentication (TOTP).') }}
                            </p>
                        </div>
                    </div>

                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p class="text-xs text-base-content/70">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="btn btn-xs sm:btn-sm rounded-lg btn-outline gap-1.5"
                                    wire:click="showRecoveryCodes"
                                >
                                    <x-icon name="o-key" class="w-3.5 h-3.5" />
                                    <span>{{ __('View Recovery Codes') }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-xs sm:btn-sm rounded-lg btn-error btn-outline gap-1.5"
                                    wire:click="disableTwoFactor"
                                >
                                    <x-icon name="o-shield-exclamation" class="w-3.5 h-3.5" />
                                    <span>{{ __('Disable 2FA') }}</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="space-y-3">
                            <p class="text-xs text-base-content/70">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login.') }}
                            </p>

                            <div class="flex justify-start">
                                <button
                                    type="button"
                                    class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                                    wire:click="startTwoFactorSetup"
                                    wire:loading.attr="disabled"
                                    wire:target="startTwoFactorSetup"
                                >
                                    <span wire:loading wire:target="startTwoFactorSetup" class="loading loading-spinner loading-xs"></span>
                                    <x-icon wire:loading.remove wire:target="startTwoFactorSetup" name="o-qr-code" class="w-3.5 h-3.5" />
                                    <span>{{ __('Enable 2FA') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- TAB 3: Browser Sessions (Enhanced with Details & Remove Option) --}}
        <div x-show="tab === 'sessions'" class="space-y-4 w-full" x-cloak>
            <div class="card bg-base-100 border border-base-300 shadow-2xs rounded-xl p-4 sm:p-6 w-full max-w-4xl space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-base text-base-content mb-1 flex items-center gap-2">
                            <x-icon name="o-computer-desktop" class="w-4 h-4 text-primary" />
                            <span>{{ __('Browser Sessions') }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Manage and log out your active sessions on other browsers and devices.') }}
                        </p>
                    </div>

                    @if (count($this->sessions) > 1)
                        <button
                            type="button"
                            class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5 shrink-0"
                            @click="$wire.showLogoutOtherSessionsModal = true"
                        >
                            <x-icon name="o-arrow-right-on-rectangle" class="w-3.5 h-3.5" />
                            <span class="hidden sm:inline">{{ __('Log Out Other Sessions') }}</span>
                        </button>
                    @endif
                </div>

                {{-- Active Sessions Cards List --}}
                <div class="space-y-3 pt-1">
                    @forelse ($this->sessions as $s)
                        @php
                            $deviceName = $s->agent->browser . ' on ' . $s->agent->platform;
                        @endphp
                        <div class="p-4 rounded-xl border border-base-300 bg-base-200/30 hover:bg-base-200/60 transition-colors space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        @if ($s->agent->is_desktop)
                                            <x-icon name="o-computer-desktop" class="w-5 h-5" />
                                        @else
                                            <x-icon name="o-device-phone-mobile" class="w-5 h-5" />
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-xs sm:text-sm font-bold text-base-content">
                                                {{ $deviceName }}
                                            </p>
                                            @if ($s->is_current_device)
                                                <span class="badge badge-xs badge-success font-semibold gap-1">
                                                    <span class="size-1.5 rounded-full bg-success-content animate-pulse"></span>
                                                    {{ __('This Device') }}
                                                </span>
                                            @else
                                                <span class="badge badge-xs badge-ghost text-base-content/60 font-mono">
                                                    {{ $s->last_active }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2 text-[11px] text-base-content/60 font-mono flex-wrap">
                                            <span>IP: {{ $s->ip_address }}</span>
                                            <span class="opacity-40">•</span>
                                            <span>ID: {{ $s->short_id }}</span>
                                            <span class="opacity-40">•</span>
                                            <span>{{ $s->last_active_exact }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Individual Remove/Revoke Button --}}
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-xs text-error hover:bg-error/10 rounded-lg gap-1"
                                    title="{{ __('Revoke Session') }}"
                                    wire:click="confirmRemoveSession('{{ $s->id }}', '{{ $deviceName }}', {{ $s->is_current_device ? 'true' : 'false' }})"
                                >
                                    <x-icon name="o-trash" class="w-3.5 h-3.5" />
                                    <span class="hidden sm:inline">{{ __('Revoke') }}</span>
                                </button>
                            </div>

                            {{-- Collapsible User Agent String Details --}}
                            <details class="text-[11px] text-base-content/50 border-t border-base-300/60 pt-2">
                                <summary class="cursor-pointer hover:text-base-content/80 select-none font-medium flex items-center gap-1">
                                    <x-icon name="o-information-circle" class="w-3 h-3 text-info" />
                                    <span>{{ __('Technical User-Agent Details') }}</span>
                                </summary>
                                <div class="mt-1.5 p-2 bg-base-100 rounded-lg border border-base-300 font-mono text-[10px] break-all select-all">
                                    {{ $s->agent->raw }}
                                </div>
                            </details>
                        </div>
                    @empty
                        <p class="text-xs text-base-content/60 italic py-4 text-center">{{ __('No active session records found.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TAB 4: Passkeys --}}
        @if ($canManagePasskeys)
            <div x-show="tab === 'passkeys'" class="space-y-4 w-full" x-cloak>
                <div class="card bg-base-100 border border-base-300 shadow-2xs rounded-xl p-4 sm:p-6 w-full max-w-3xl space-y-4">
                    <div>
                        <h3 class="font-bold text-base text-base-content mb-1">{{ __('Passkeys') }}</h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Passkeys allow passwordless authentication using Touch ID, Face ID, or security keys.') }}
                        </p>
                    </div>

                    {{-- Passkeys List --}}
                    <div class="overflow-x-auto rounded-lg border border-base-300">
                        <table class="table table-xs sm:table-sm w-full">
                            <thead>
                                <tr class="bg-base-200/50 text-xs">
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Created') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($passkeys as $pk)
                                    <tr class="hover:bg-base-200/30">
                                        <td class="font-medium text-xs sm:text-sm">
                                            <div class="flex items-center gap-1.5">
                                                <x-icon name="o-key" class="w-3.5 h-3.5 text-primary" />
                                                <span>{{ $pk['name'] ?? __('Unnamed Passkey') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-xs text-base-content/60">
                                            {{ \Carbon\Carbon::parse($pk['created_at'])->diffForHumans() }}
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-ghost btn-xs text-error btn-square"
                                                wire:click="confirmPasskeyDeletion({{ $pk['id'] }}, '{{ $pk['name'] ?? '' }}')"
                                            >
                                                <x-icon name="o-trash" class="w-3.5 h-3.5" />
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-xs text-base-content/60">
                                            {{ __('No passkeys registered.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Passkey Registration Component --}}
                    <div class="pt-2">
                        <x-passkey-registration />
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 5: Appearance --}}
        <div x-show="tab === 'appearance'" class="space-y-4 w-full" x-cloak>
            <div class="card bg-base-100 border border-base-300 shadow-2xs rounded-xl p-4 sm:p-6 w-full space-y-4">
                <div>
                    <h3 class="font-bold text-base text-base-content mb-1">{{ __('Appearance & Themes') }}</h3>
                    <p class="text-xs text-base-content/60">{{ __('Select your preferred DaisyUI application theme.') }}</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                    @php
                        $themes = [
                            'light' => 'Light', 'dark' => 'Dark', 'cupcake' => 'Cupcake', 'emerald' => 'Emerald',
                            'corporate' => 'Corporate', 'synthwave' => 'Synthwave', 'retro' => 'Retro', 'cyberpunk' => 'Cyberpunk',
                            'valentine' => 'Valentine', 'halloween' => 'Halloween', 'garden' => 'Garden', 'forest' => 'Forest',
                            'aqua' => 'Aqua', 'lofi' => 'Lofi', 'pastel' => 'Pastel', 'fantasy' => 'Fantasy',
                            'wireframe' => 'Wireframe', 'black' => 'Black', 'luxury' => 'Luxury', 'dracula' => 'Dracula',
                            'cmyk' => 'CMYK', 'autumn' => 'Autumn', 'business' => 'Business', 'acid' => 'Acid',
                            'lemon' => 'Lemon', 'night' => 'Night', 'coffee' => 'Coffee', 'winter' => 'Winter',
                            'dim' => 'Dim', 'nord' => 'Nord', 'sunset' => 'Sunset', 'caramellatte' => 'Caramellatte', 'silk' => 'Silk'
                        ];
                    @endphp

                    @foreach ($themes as $key => $tName)
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="theme"
                                class="sr-only"
                                value="{{ $key }}"
                                x-bind:checked="theme === '{{ $key }}'"
                                @change="setTheme('{{ $key }}')"
                            />
                            <div
                                class="flex flex-col gap-1.5 p-2.5 rounded-lg border-2 transition-all"
                                x-bind:class="theme === '{{ $key }}' ? 'border-primary bg-primary/10 shadow-xs' : 'border-base-300 hover:border-base-content/30 bg-base-100'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold truncate text-base-content">{{ $tName }}</span>
                                    <div x-show="theme === '{{ $key }}'" class="size-2 rounded-full bg-primary shrink-0"></div>
                                </div>
                                <div data-theme="{{ $key }}" class="rounded-md p-1.5 bg-base-100 border border-base-300 flex items-center gap-1">
                                    <div class="size-2.5 rounded-full bg-primary"></div>
                                    <div class="size-2.5 rounded-full bg-secondary"></div>
                                    <div class="size-2.5 rounded-full bg-accent"></div>
                                    <div class="size-2.5 rounded-full bg-neutral"></div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- TAB 6: Delete Account / Danger Zone --}}
        <div x-show="tab === 'danger'" class="space-y-4 w-full" x-cloak>
            <div class="card bg-base-100 border border-error/30 shadow-2xs rounded-xl p-4 sm:p-6 w-full max-w-3xl space-y-4">
                <div>
                    <h3 class="font-bold text-base text-error mb-1">{{ __('Delete Account') }}</h3>
                    <p class="text-xs text-base-content/60">
                        {{ __('Permanently delete your account and all associated data. This action cannot be undone.') }}
                    </p>
                </div>

                <div class="flex justify-start">
                    <button
                        type="button"
                        class="btn btn-error btn-xs sm:btn-sm rounded-lg gap-1.5"
                        @click="$wire.showDeleteAccountModal = true"
                    >
                        <x-icon name="o-trash" class="w-3.5 h-3.5" />
                        <span>{{ __('Delete Account') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Individual Session Revoke Modal --}}
    <div class="modal {{ $showRemoveSessionModal ? 'modal-open' : '' }}" role="dialog">
        <div class="modal-box max-w-md bg-base-100 rounded-2xl p-6 border border-base-300 shadow-2xl space-y-4">
            <h3 class="text-lg font-bold text-base-content flex items-center gap-2">
                <x-icon name="o-trash" class="w-5 h-5 text-error" />
                <span>{{ __('Revoke Session') }}</span>
            </h3>
            <p class="text-xs text-base-content/70 leading-relaxed">
                @if ($removingSessionIsCurrent)
                    {{ __('Are you sure you want to revoke your current browser session (":device")? You will be logged out immediately.', ['device' => $removingSessionDevice]) }}
                @else
                    {{ __('Are you sure you want to revoke the session for ":device"? The user on that device will be logged out.', ['device' => $removingSessionDevice]) }}
                @endif
            </p>

            <div class="modal-action border-t border-base-300 pt-3 mt-4 flex gap-2 justify-end">
                <button
                    type="button"
                    class="btn btn-ghost btn-xs sm:btn-sm rounded-lg"
                    wire:click="closeRemoveSessionModal"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="button"
                    class="btn btn-error btn-xs sm:btn-sm rounded-lg gap-1.5"
                    wire:click="removeSession"
                    wire:loading.attr="disabled"
                    wire:target="removeSession"
                >
                    <span wire:loading wire:target="removeSession" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Revoke Session') }}</span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeRemoveSessionModal"></div>
    </div>

    {{-- Log Out Other Browser Sessions Modal --}}
    <div class="modal {{ $showLogoutOtherSessionsModal ? 'modal-open' : '' }}" role="dialog">
        <div class="modal-box max-w-md bg-base-100 rounded-2xl p-6 border border-base-300 shadow-2xl space-y-4">
            <h3 class="font-bold text-lg text-base-content flex items-center gap-2">
                <x-icon name="o-arrow-right-on-rectangle" class="w-5 h-5 text-primary" />
                <span>{{ __('Log Out Other Browser Sessions') }}</span>
            </h3>

            <p class="text-xs text-base-content/70 leading-relaxed">
                {{ __('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.') }}
            </p>

            <form wire:submit.prevent="logoutOtherBrowserSessions" class="space-y-3 pt-2">
                <div class="form-control w-full">
                    <label class="label py-1" for="logout-session-password">
                        <span class="label-text text-xs font-semibold text-base-content">{{ __('Password') }}</span>
                    </label>
                    <input
                        id="logout-session-password"
                        type="password"
                        class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm @error('logoutOtherSessionsPassword') input-error @enderror"
                        wire:model="logoutOtherSessionsPassword"
                        placeholder="{{ __('Enter your password') }}"
                        required
                    />
                    @error('logoutOtherSessionsPassword')
                        <span class="text-xs text-error mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="modal-action border-t border-base-300 pt-3 mt-4 flex gap-2 justify-end">
                    <button
                        type="button"
                        class="btn btn-ghost btn-xs sm:btn-sm rounded-lg"
                        wire:click="closeLogoutOtherSessionsModal"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                        wire:loading.attr="disabled"
                        wire:target="logoutOtherBrowserSessions"
                    >
                        <span wire:loading wire:target="logoutOtherBrowserSessions" class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Log Out Other Sessions') }}</span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeLogoutOtherSessionsModal"></div>
    </div>

    {{-- Redesigned 2FA Setup Modal with x-pin & Copy Key --}}
    <div class="modal {{ $show2faModal ? 'modal-open' : '' }}" role="dialog">
        <div class="modal-box max-w-lg bg-base-100 rounded-2xl p-6 border border-base-300 shadow-2xl space-y-5">
            <div class="flex items-center justify-between border-b border-base-300 pb-3">
                <h3 class="font-bold text-lg text-base-content flex items-center gap-2.5">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <x-icon name="o-qr-code" class="w-5 h-5" />
                    </div>
                    <span>{{ __('Two-Factor Setup') }}</span>
                </h3>
                <button
                    type="button"
                    class="btn btn-ghost btn-xs btn-square"
                    wire:click="close2faModal"
                >
                    <x-icon name="o-x-mark" class="w-4 h-4" />
                </button>
            </div>

            <p class="text-xs text-base-content/70 leading-relaxed">
                {{ __('Scan the QR code below using your authenticator app (Google Authenticator, 1Password, or Authy), or copy the setup key manually.') }}
            </p>

            @if ($twoFactorQrCodeSvg)
                <div class="flex flex-col items-center justify-center p-4 bg-white rounded-2xl shadow-inner border border-base-300 max-w-xs mx-auto">
                    {!! $twoFactorQrCodeSvg !!}
                </div>
            @endif

            @if ($twoFactorSetupKey)
                <div class="flex items-center justify-between bg-base-200/80 p-3 rounded-xl border border-base-300">
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] text-base-content/60 font-semibold uppercase tracking-wider">{{ __('Setup Key') }}</p>
                        <p class="font-mono text-xs sm:text-sm font-bold text-primary select-all truncate mt-0.5">{{ $twoFactorSetupKey }}</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-ghost btn-xs rounded-lg gap-1 text-primary shrink-0"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText('{{ $twoFactorSetupKey }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    >
                        <template x-if="!copied">
                            <div class="flex items-center gap-1">
                                <x-icon name="o-clipboard-document" class="w-3.5 h-3.5" />
                                <span>{{ __('Copy') }}</span>
                            </div>
                        </template>
                        <template x-if="copied">
                            <div class="flex items-center gap-1 text-success font-semibold">
                                <x-icon name="o-check" class="w-3.5 h-3.5" />
                                <span>{{ __('Copied!') }}</span>
                            </div>
                        </template>
                    </button>
                </div>
            @endif

            <form wire:submit.prevent="confirmTwoFactor" class="space-y-4 pt-2">
                <div class="form-control w-full items-center">
                    <label class="label py-1 mb-1">
                        <span class="label-text text-xs font-bold text-base-content">{{ __('Enter 6-Digit Verification PIN') }}</span>
                    </label>

                    {{-- MaryUI x-pin Component --}}
                    <div class="flex justify-center my-1">
                        <x-pin size="6" wire:model="twoFactorCode" numeric omit-error />
                    </div>

                    @error('twoFactorCode')
                        <span class="text-xs text-error mt-2 font-medium text-center block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="modal-action border-t border-base-300 pt-4 mt-4 flex gap-2 justify-end">
                    <button
                        type="button"
                        class="btn btn-ghost btn-xs sm:btn-sm rounded-lg"
                        wire:click="close2faModal"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                        wire:loading.attr="disabled"
                        wire:target="confirmTwoFactor"
                    >
                        <span wire:loading wire:target="confirmTwoFactor" class="loading loading-spinner loading-xs"></span>
                        <x-icon wire:loading.remove wire:target="confirmTwoFactor" name="o-check" class="w-3.5 h-3.5" />
                        <span>{{ __('Verify & Enable 2FA') }}</span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="close2faModal"></div>
    </div>

    {{-- Recovery Codes Modal --}}
    <div class="modal {{ $showRecoveryCodesModal ? 'modal-open' : '' }}" role="dialog">
        <div class="modal-box max-w-md bg-base-100 rounded-2xl p-6 border border-base-300 shadow-2xl space-y-4">
            <h3 class="font-bold text-lg text-base-content flex items-center gap-2">
                <x-icon name="o-key" class="w-5 h-5 text-primary" />
                <span>{{ __('2FA Recovery Codes') }}</span>
            </h3>

            <p class="text-xs text-base-content/70 leading-relaxed">
                {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two-factor authentication device is lost.') }}
            </p>

            <div class="grid grid-cols-2 gap-2 bg-base-200/60 p-3 rounded-xl border border-base-300 font-mono text-xs text-center">
                @foreach ($twoFactorRecoveryCodes as $code)
                    <div class="p-1.5 bg-base-100 rounded-lg border border-base-300 font-semibold select-all">
                        {{ $code }}
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between border-t border-base-300 pt-3 mt-4">
                <button
                    type="button"
                    class="btn btn-ghost btn-xs sm:btn-sm rounded-lg gap-1 text-primary"
                    wire:click="regenerateRecoveryCodes"
                    wire:loading.attr="disabled"
                    wire:target="regenerateRecoveryCodes"
                >
                    <span wire:loading wire:target="regenerateRecoveryCodes" class="loading loading-spinner loading-xs"></span>
                    <x-icon wire:loading.remove wire:target="regenerateRecoveryCodes" name="o-arrow-path" class="w-3.5 h-3.5" />
                    <span>{{ __('Regenerate Codes') }}</span>
                </button>

                <button
                    type="button"
                    class="btn btn-primary btn-xs sm:btn-sm rounded-lg"
                    wire:click="closeRecoveryCodesModal"
                >
                    {{ __('Done') }}
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeRecoveryCodesModal"></div>
    </div>

    {{-- Passkey Delete Modal --}}
    <div class="modal {{ $showDeletePasskeyModal ? 'modal-open' : '' }}" role="dialog">
        <div class="modal-box max-w-md bg-base-100 rounded-2xl p-6 border border-base-300 shadow-2xl space-y-4">
            <h3 class="text-lg font-bold text-base-content flex items-center gap-2">
                <x-icon name="o-trash" class="w-5 h-5 text-error" />
                <span>{{ __('Remove Passkey') }}</span>
            </h3>
            <p class="text-xs text-base-content/70">
                {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
            </p>

            <div class="modal-action border-t border-base-300 pt-3 mt-4 flex gap-2 justify-end">
                <button
                    type="button"
                    class="btn btn-ghost btn-xs sm:btn-sm rounded-lg"
                    wire:click="closeDeletePasskeyModal"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="button"
                    class="btn btn-error btn-xs sm:btn-sm rounded-lg gap-1.5"
                    wire:click="deletePasskey"
                >
                    <span>{{ __('Remove Passkey') }}</span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeDeletePasskeyModal"></div>
    </div>

    {{-- Delete Account Modal --}}
    <div class="modal {{ $showDeleteAccountModal ? 'modal-open' : '' }}" role="dialog">
        <div class="modal-box max-w-md bg-base-100 rounded-2xl p-6 border border-base-300 shadow-2xl space-y-4">
            <h3 class="text-lg font-bold text-error flex items-center gap-2">
                <x-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <span>{{ __('Confirm Account Deletion') }}</span>
            </h3>
            <p class="text-xs text-base-content/70">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <form wire:submit.prevent="deleteAccount" class="space-y-3 pt-2">
                <div class="form-control w-full">
                    <label class="label py-1" for="delete-password">
                        <span class="label-text text-xs font-semibold text-base-content">{{ __('Password') }}</span>
                    </label>
                    <input
                        id="delete-password"
                        type="password"
                        class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm @error('deleteAccountPassword') input-error @enderror"
                        wire:model="deleteAccountPassword"
                        placeholder="{{ __('Enter your password') }}"
                        required
                    />
                    @error('deleteAccountPassword')
                        <span class="text-xs text-error mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="modal-action border-t border-base-300 pt-3 mt-4 flex gap-2 justify-end">
                    <button
                        type="button"
                        class="btn btn-ghost btn-xs sm:btn-sm rounded-lg"
                        wire:click="closeDeleteAccountModal"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="btn btn-error btn-xs sm:btn-sm rounded-lg gap-1.5"
                        wire:loading.attr="disabled"
                        wire:target="deleteAccount"
                    >
                        <span wire:loading wire:target="deleteAccount" class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Permanently Delete') }}</span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeDeleteAccountModal"></div>
    </div>
</section>
