@auth
<div class="dropdown dropdown-top w-full">
    <label
        tabindex="0"
        class="flex items-center gap-3 w-full p-2 rounded-xl cursor-pointer hover:bg-base-200/70 transition-all duration-200 border border-transparent hover:border-base-300"
        data-test="sidebar-menu-button"
    >
        <div class="avatar placeholder">
            <div class="bg-primary text-primary-content rounded-xl w-9 h-9 shadow-xs flex items-center justify-center font-bold text-sm">
                <span>{{ auth()->user()->initials() }}</span>
            </div>
        </div>
        <div class="flex-1 min-w-0 text-left">
            <p class="text-sm font-semibold truncate text-base-content leading-tight">{{ auth()->user()->name }}</p>
            <p class="text-xs truncate text-base-content/60 leading-tight mt-0.5">{{ auth()->user()->email }}</p>
        </div>
        <x-icon name="o-chevron-up-down" class="w-4 h-4 text-base-content/40 shrink-0" />
    </label>

    <ul tabindex="0" class="dropdown-content z-[30] menu menu-sm shadow-xl bg-base-100 rounded-2xl w-60 border border-base-300 mb-2 p-2 space-y-1">
        <li class="px-3 py-2 bg-base-200/50 rounded-xl mb-1">
            <div class="flex items-center gap-3 p-0 hover:bg-transparent">
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content rounded-lg w-8 h-8 flex items-center justify-center font-semibold text-xs">
                        <span>{{ auth()->user()->initials() }}</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold truncate text-base-content">{{ auth()->user()->name }}</p>
                    <p class="text-xs truncate text-base-content/60">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </li>
        <li>
            <a href="{{ route('profile.edit') }}" class="rounded-lg py-2 gap-2.5 font-medium hover:bg-primary/10 hover:text-primary transition-colors" wire:navigate>
                <x-icon name="o-user-circle" class="w-4 h-4" />
                {{ __('My Profile') }}
            </a>
        </li>
        <div class="divider my-0"></div>
        <li>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button
                    type="submit"
                    class="w-full text-left flex items-center gap-2.5 rounded-lg py-2 text-error hover:bg-error/10 font-medium transition-colors"
                    data-test="logout-button"
                >
                    <x-icon name="o-arrow-right-on-rectangle" class="w-4 h-4" />
                    {{ __('Log out') }}
                </button>
            </form>
        </li>
    </ul>
</div>
@endauth
