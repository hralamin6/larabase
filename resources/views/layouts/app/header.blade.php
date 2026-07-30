<x-layouts::base :title="$title ?? null" class="min-h-screen bg-base-200/50">
    <div class="min-h-screen flex flex-col">
        {{-- Glassmorphism Top Navigation --}}
        <header class="navbar bg-base-100/90 backdrop-blur-md border-b border-base-300/80 sticky top-0 z-30 px-4 sm:px-6 shadow-xs">
            {{-- Mobile drawer toggle --}}
            <div class="flex-none lg:hidden me-2">
                <label for="mobile-header-drawer" class="btn btn-square btn-ghost btn-sm">
                    <x-icon name="o-bars-3" class="w-5 h-5 text-base-content" />
                </label>
            </div>

            {{-- Brand Logo & Title --}}
            <div class="flex-none pe-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group" wire:navigate>
                    <div class="flex aspect-square size-8.5 items-center justify-center rounded-xl bg-gradient-to-tr from-primary to-primary-focus text-primary-content shadow-md shadow-primary/20 transition-transform group-hover:scale-105">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </div>
                    <span class="font-bold text-base tracking-tight text-base-content hidden sm:inline-block">{{ config('app.name', 'Laravel') }}</span>
                </a>
            </div>

            {{-- Horizontal Navigation Menu (Desktop) --}}
            <div class="flex-1 hidden lg:flex px-2">
                <ul class="menu menu-horizontal p-0 gap-1 font-medium text-sm">
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="rounded-xl px-3.5 py-2 transition-all gap-2 {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary font-semibold' : 'text-base-content/80 hover:bg-base-200/70' }}"
                           wire:navigate>
                            <x-icon name="o-home" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-base-content/60' }}" />
                            <span>{{ __('Dashboard') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('profile.edit') }}"
                           class="rounded-xl px-3.5 py-2 transition-all gap-2 {{ request()->routeIs('profile.edit') ? 'bg-primary/10 text-primary font-semibold' : 'text-base-content/80 hover:bg-base-200/70' }}"
                           wire:navigate>
                            <x-icon name="o-user-circle" class="w-4 h-4 {{ request()->routeIs('profile.edit') ? 'text-primary' : 'text-base-content/60' }}" />
                            <span>{{ __('My Profile') }}</span>
                        </a>
                    </li>

                    @can('roles.view')
                    <li>
                        <div class="dropdown dropdown-bottom">
                            <label tabindex="0" class="rounded-xl px-3 py-2 cursor-pointer transition-all gap-1.5 flex items-center {{ request()->routeIs('roles.index') ? 'bg-primary/10 text-primary font-semibold' : 'text-base-content/80 hover:bg-base-200/70' }}">
                                <x-icon name="o-cog-8-tooth" class="w-4 h-4 {{ request()->routeIs('roles.index') ? 'text-primary' : 'text-base-content/60' }}" />
                                <span>{{ __('Root Settings') }}</span>
                                <x-icon name="o-chevron-down" class="w-3.5 h-3.5 opacity-60" />
                            </label>
                            <ul tabindex="0" class="dropdown-content z-[30] menu menu-sm p-2 shadow-xl bg-base-100 rounded-2xl w-52 border border-base-300 mt-2">
                                <li>
                                    <a href="{{ route('roles.index') }}" class="rounded-xl py-2 gap-2 {{ request()->routeIs('roles.index') ? 'active' : '' }}" wire:navigate>
                                        <x-icon name="o-key" class="w-4 h-4" />
                                        <span>{{ __('Roles & Permissions') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endcan
                </ul>
            </div>


            {{-- Right Actions Bar --}}
            <div class="flex-1 lg:flex-none flex items-center justify-end gap-1.5">
                {{-- Repository --}}
                <a href="https://github.com/laravel/livewire-starter-kit"
                   target="_blank"
                   class="btn btn-ghost btn-circle btn-sm hidden sm:flex text-base-content/70 hover:text-base-content"
                   title="{{ __('Repository') }}">
                    <x-icon name="o-folder" class="w-4.5 h-4.5" />
                </a>

                {{-- Documentation --}}
                <a href="https://laravel.com/docs/starter-kits#livewire"
                   target="_blank"
                   class="btn btn-ghost btn-circle btn-sm hidden sm:flex text-base-content/70 hover:text-base-content"
                   title="{{ __('Documentation') }}">
                    <x-icon name="o-book-open" class="w-4.5 h-4.5" />
                </a>

                {{-- MaryUI Theme Toggle --}}
                <x-theme-toggle class="btn btn-ghost btn-circle btn-sm text-base-content/70 hover:text-base-content" />

                <div class="divider divider-horizontal mx-1 my-2 hidden sm:flex"></div>

                {{-- User Dropdown --}}
                <div class="w-auto">
                    <x-desktop-user-menu />
                </div>
            </div>
        </header>

        {{-- Mobile Nav Drawer --}}
        <div class="drawer lg:hidden">
            <input id="mobile-header-drawer" type="checkbox" class="drawer-toggle" />
            <div class="drawer-content"></div>
            <div class="drawer-side z-40">
                <label for="mobile-header-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
                <aside class="w-72 min-h-screen bg-base-100 border-r border-base-300/80 p-5 flex flex-col justify-between">
                    <div>
                        {{-- Drawer Branding --}}
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-base-300/60">
                            <div class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-primary to-primary-focus text-primary-content shadow-md shadow-primary/20">
                                <x-app-logo-icon class="size-5 fill-current" />
                            </div>
                            <a href="{{ route('dashboard') }}" class="font-bold text-base text-base-content tracking-tight" wire:navigate>
                                {{ config('app.name', 'Laravel') }}
                            </a>
                        </div>

                        {{-- Navigation Links --}}
                        <p class="text-[11px] font-bold text-base-content/40 uppercase tracking-widest px-2 mb-2">{{ __('Navigation') }}</p>
                        <ul class="menu menu-md w-full p-0 gap-1">
                            <li>
                                <a href="{{ route('dashboard') }}"
                                   class="rounded-xl font-medium gap-3 py-2.5 {{ request()->routeIs('dashboard') ? 'active bg-primary/10 text-primary font-semibold' : 'text-base-content/80' }}"
                                   wire:navigate>
                                    <x-icon name="o-home" class="w-5 h-5" /> {{ __('Dashboard') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}"
                                   class="rounded-xl font-medium gap-3 py-2.5 {{ request()->routeIs('profile.edit') ? 'active bg-primary/10 text-primary font-semibold' : 'text-base-content/80' }}"
                                   wire:navigate>
                                    <x-icon name="o-user" class="w-5 h-5" /> {{ __('Profile') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('security.edit') }}"
                                   class="rounded-xl font-medium gap-3 py-2.5 {{ request()->routeIs('security.edit') ? 'active bg-primary/10 text-primary font-semibold' : 'text-base-content/80' }}"
                                   wire:navigate>
                                    <x-icon name="o-shield-check" class="w-5 h-5" /> {{ __('Security') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('appearance.edit') }}"
                                   class="rounded-xl font-medium gap-3 py-2.5 {{ request()->routeIs('appearance.edit') ? 'active bg-primary/10 text-primary font-semibold' : 'text-base-content/80' }}"
                                   wire:navigate>
                                    <x-icon name="o-swatch" class="w-5 h-5" /> {{ __('Appearance') }}
                                </a>
                            </li>
                        </ul>

                        <div class="divider my-4"></div>

                        <p class="text-[11px] font-bold text-base-content/40 uppercase tracking-widest px-2 mb-2">{{ __('Resources') }}</p>
                        <ul class="menu menu-md w-full p-0 gap-1">
                            <li>
                                <a href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="rounded-xl font-medium gap-3 py-2.5 text-base-content/70">
                                    <x-icon name="o-folder" class="w-5 h-5" /> {{ __('Repository') }}
                                </a>
                            </li>
                            <li>
                                <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="rounded-xl font-medium gap-3 py-2.5 text-base-content/70">
                                    <x-icon name="o-book-open" class="w-5 h-5" /> {{ __('Documentation') }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Drawer Footer --}}
                    <div class="pt-4 border-t border-base-300/60">
                        <x-desktop-user-menu />
                    </div>
                </aside>
            </div>
        </div>

        {{-- Page Body --}}
        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>
</x-layouts::base>
