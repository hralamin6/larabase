<x-layouts::base :title="$title ?? null" class="min-h-screen bg-base-200/50">
    <div class="min-h-screen flex flex-col">
        {{-- Mobile header with hamburger & glassmorphism --}}
        <div class="navbar bg-base-100/90 backdrop-blur-md border-b border-base-300/80 lg:hidden sticky top-0 z-30 px-4">
            <div class="flex-none">
                <label for="main-sidebar" class="btn btn-square btn-ghost btn-sm">
                    <x-icon name="o-bars-3" class="w-5 h-5 text-base-content" />
                </label>
            </div>
            <div class="flex-1 px-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5" wire:navigate>
                    <div class="flex aspect-square size-8 items-center justify-center rounded-xl bg-gradient-to-tr from-primary to-primary-focus text-primary-content shadow-xs">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </div>
                    <span class="font-bold text-base tracking-tight text-base-content">{{ config('app.name', 'Laravel') }}</span>
                </a>
            </div>
            <div class="flex-none flex items-center gap-1">
                <x-theme-toggle class="btn btn-ghost btn-circle btn-sm" />

                @auth
                    {{-- Mobile user dropdown --}}
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-circle avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-full w-8 h-8 shadow-xs flex items-center justify-center font-bold text-xs">
                                <span>{{ auth()->user()->initials() }}</span>
                            </div>
                        </label>
                        <ul tabindex="0" class="mt-3 z-[40] p-2 shadow-xl menu menu-sm dropdown-content bg-base-100 rounded-2xl w-56 border border-base-300">
                            <li class="px-3 py-2 bg-base-200/50 rounded-xl mb-1">
                                <div class="flex items-center gap-2.5 p-0 hover:bg-transparent">
                                    <div class="bg-primary text-primary-content rounded-lg w-7 h-7 flex items-center justify-center font-bold text-xs">
                                        {{ auth()->user()->initials() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold truncate text-base-content">{{ auth()->user()->name }}</p>
                                        <p class="text-xs truncate text-base-content/60">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}" wire:navigate>
                                    <x-icon name="o-cog-6-tooth" class="w-4 h-4" /> {{ __('Settings') }}
                                </a>
                            </li>
                            <div class="divider my-0"></div>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-2 text-error" data-test="logout-button">
                                        <x-icon name="o-arrow-right-on-rectangle" class="w-4 h-4" /> {{ __('Log out') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Sidebar + Content Drawer layout --}}
        <div class="drawer lg:drawer-open flex-1">
            <input id="main-sidebar" type="checkbox" class="drawer-toggle" />

            {{-- Main Page Content --}}
            <div class="drawer-content flex flex-col min-h-full">
                {{ $slot }}
            </div>

            {{-- Sidebar --}}
            <div class="drawer-side z-30">
                <label for="main-sidebar" aria-label="close sidebar" class="drawer-overlay"></label>
                <aside class="flex flex-col h-screen sticky top-0 w-64 bg-base-100 border-r border-base-300/80 shadow-xs">

                    {{-- Branding Header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-base-300/60">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group" wire:navigate>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-primary to-primary-focus text-primary-content shadow-md shadow-primary/20 transition-transform group-hover:scale-105">
                                <x-app-logo-icon class="size-5 fill-current" />
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-base text-base-content tracking-tight leading-tight">{{ config('app.name', 'Laravel') }}</span>
                                <span class="text-[10px] font-semibold text-base-content/40 tracking-wider uppercase">Starter App</span>
                            </div>
                        </a>
                        <span class="badge badge-xs badge-neutral font-mono text-[10px] px-1.5 py-0.5">v1.0</span>
                    </div>

                    {{-- Navigation Body --}}
                    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">

                        {{-- Overview Section --}}
                        <div>
                            <p class="text-[11px] font-bold text-base-content/40 uppercase tracking-widest px-3 mb-2">{{ __('Overview') }}</p>
                            <ul class="menu menu-md w-full p-0 gap-1">
                                <li>
                                    <a href="{{ route('dashboard') }}"
                                       class="rounded-xl font-medium gap-3 py-2.5 transition-all {{ request()->routeIs('dashboard') ? 'active bg-primary/10 text-primary font-semibold border-s-4 border-primary rounded-s-none' : 'hover:bg-base-200/70 text-base-content/80' }}"
                                       wire:navigate>
                                        <x-icon name="o-home" class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-base-content/60' }}" />
                                        <span>{{ __('Dashboard') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- Account Section --}}
                        <div>
                            <p class="text-[11px] font-bold text-base-content/40 uppercase tracking-widest px-3 mb-2">{{ __('Account') }}</p>
                            <ul class="menu menu-md w-full p-0 gap-1">
                                <li>
                                    <a href="{{ route('profile.edit') }}"
                                       class="rounded-xl font-medium gap-3 py-2.5 transition-all {{ request()->routeIs('profile.edit') ? 'active bg-primary/10 text-primary font-semibold border-s-4 border-primary rounded-s-none' : 'hover:bg-base-200/70 text-base-content/80' }}"
                                       wire:navigate>
                                        <x-icon name="o-user-circle" class="w-5 h-5 shrink-0 {{ request()->routeIs('profile.edit') ? 'text-primary' : 'text-base-content/60' }}" />
                                        <span>{{ __('My Profile') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>


                        {{-- Root Settings Section --}}
                        @can('roles.view')
                        <div>
                            <p class="text-[11px] font-bold text-base-content/40 uppercase tracking-widest px-3 mb-2">{{ __('System Administration') }}</p>
                            <ul class="menu menu-md w-full p-0 gap-1">
                                <li>
                                    <details {{ request()->routeIs('roles.index') ? 'open' : '' }}>
                                        <summary class="rounded-xl font-medium gap-3 py-2.5 text-base-content/80 hover:bg-base-200/70">
                                            <x-icon name="o-cog-8-tooth" class="w-5 h-5 shrink-0 text-base-content/60" />
                                            <span>{{ __('Root Settings') }}</span>
                                        </summary>
                                        <ul class="mt-1 space-y-1 ps-4">
                                            <li>
                                                <a href="{{ route('roles.index') }}"
                                                   class="rounded-xl font-medium gap-2.5 py-2 transition-all {{ request()->routeIs('roles.index') ? 'active bg-primary/10 text-primary font-semibold border-s-4 border-primary rounded-s-none' : 'hover:bg-base-200/70 text-base-content/80' }}"
                                                   wire:navigate>
                                                    <x-icon name="o-key" class="w-4 h-4 shrink-0 {{ request()->routeIs('roles.index') ? 'text-primary' : 'text-base-content/60' }}" />
                                                    <span>{{ __('Roles & Permissions') }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </details>
                                </li>
                            </ul>
                        </div>
                        @endcan




                        {{-- Resources Section --}}
                        <div>
                            <p class="text-[11px] font-bold text-base-content/40 uppercase tracking-widest px-3 mb-2">{{ __('Resources') }}</p>
                            <ul class="menu menu-md w-full p-0 gap-1">
                                <li>
                                    <a href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="rounded-xl font-medium gap-3 py-2.5 text-base-content/70 hover:bg-base-200/70">
                                        <x-icon name="o-folder" class="w-5 h-5 text-base-content/50" />
                                        <span>{{ __('Repository') }}</span>
                                        <x-icon name="o-arrow-top-right-on-square" class="w-3.5 h-3.5 ms-auto text-base-content/30" />
                                    </a>
                                </li>
                                <li>
                                    <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="rounded-xl font-medium gap-3 py-2.5 text-base-content/70 hover:bg-base-200/70">
                                        <x-icon name="o-book-open" class="w-5 h-5 text-base-content/50" />
                                        <span>{{ __('Documentation') }}</span>
                                        <x-icon name="o-arrow-top-right-on-square" class="w-3.5 h-3.5 ms-auto text-base-content/30" />
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Footer User Section --}}
                    <div class="border-t border-base-300/60 p-3 bg-base-100">
                        <x-desktop-user-menu />
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-layouts::base>
