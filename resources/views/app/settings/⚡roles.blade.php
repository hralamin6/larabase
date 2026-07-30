<?php

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Title('Roles & Permissions')] class extends Component {
    use Toast;

    public ?int $roleId = null;

    public string $roleName = '';

    public array $selectedPermissions = [];

    public ?int $userId = null;

    public array $userRoles = [];

    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public string $userSortField = 'name';

    public string $userSortDirection = 'asc';

    public function mount(): void
    {
        $this->authorize('roles.view');
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function sortUsersBy(string $field): void
    {
        if ($this->userSortField === $field) {
            $this->userSortDirection = $this->userSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->userSortField = $field;
            $this->userSortDirection = 'asc';
        }
    }

    public function createRole(): void
    {
        $this->authorize('roles.create');

        $this->roleId = null;
        $this->roleName = '';
        $this->selectedPermissions = [];
        $this->dispatch('open-role-modal');
    }

    public function editRole(int $id): void
    {
        $this->authorize('roles.edit');

        $role = Role::with('permissions')->findOrFail($id);

        $this->roleId = $role->id;
        $this->roleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->dispatch('open-role-modal');
    }

    public function saveRole(): void
    {
        if ($this->roleId) {
            $this->authorize('roles.edit');
        } else {
            $this->authorize('roles.create');
        }

        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name,'.($this->roleId ?? 'NULL').',id',
            'selectedPermissions' => 'array',
        ]);

        $role = Role::updateOrCreate(
            ['id' => $this->roleId],
            ['name' => $this->roleName, 'guard_name' => 'web']
        );

        $role->syncPermissions($this->selectedPermissions);

        $this->reset(['roleId', 'roleName', 'selectedPermissions']);
        $this->dispatch('close-role-modal');
        $this->success(__('Role saved successfully!'));
    }

    public function deleteRole(int $id): void
    {
        $this->authorize('roles.delete');

        $role = Role::findOrFail($id);

        if ($role->name === 'Super Admin') {
            $this->error(__('The Super Admin role cannot be deleted.'));

            return;
        }

        $role->delete();

        $this->success(__('Role deleted successfully!'));
    }

    public function editUserRoles(int $id): void
    {
        $this->authorize('users.roles');

        $user = User::with('roles')->findOrFail($id);

        $this->userId = $user->id;
        $this->userRoles = $user->roles->pluck('name')->toArray();

        $this->dispatch('open-user-modal');
    }

    public function saveUserRoles(): void
    {
        $this->authorize('users.roles');

        $user = User::findOrFail($this->userId);
        $user->syncRoles($this->userRoles);

        $this->reset(['userId', 'userRoles']);
        $this->dispatch('close-user-modal');
        $this->success(__('User roles updated successfully!'));
    }

    public function with(): array
    {
        $rolesQuery = Role::withCount(['permissions', 'users']);

        if (in_array($this->sortField, ['name', 'guard_name', 'permissions_count', 'users_count'])) {
            $rolesQuery->orderBy($this->sortField, $this->sortDirection);
        }

        $roles = $rolesQuery->get();

        $groupedPermissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0] ?? 'general';
        });

        $usersQuery = User::with('roles');

        if (! empty($this->search)) {
            $usersQuery->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if (in_array($this->userSortField, ['name', 'email'])) {
            $usersQuery->orderBy($this->userSortField, $this->userSortDirection);
        }

        $users = $usersQuery->limit(20)->get();

        return [
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
            'users' => $users,
            'allRoles' => $roles,
        ];
    }
}; ?>

<section x-cloak class="w-full max-w-7xl mx-auto space-y-4 relative"
    x-data="{
        tab: 'roles',
        showRoleModal: false,
        showUserModal: false,
        toggleModule(permissions) {
            let allSelected = permissions.every(p => $wire.selectedPermissions.includes(p));
            if (allSelected) {
                $wire.selectedPermissions = $wire.selectedPermissions.filter(p => !permissions.includes(p));
            } else {
                permissions.forEach(p => {
                    if (!$wire.selectedPermissions.includes(p)) {
                        $wire.selectedPermissions.push(p);
                    }
                });
            }
        }
    }"
    @open-role-modal.window="showRoleModal = true"
    @close-role-modal.window="showRoleModal = false"
    @open-user-modal.window="showUserModal = true"
    @close-user-modal.window="showUserModal = false"
>



    {{-- Standalone Page Header (Compact) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-base-300 pb-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-base-content flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <x-icon name="o-key" class="size-4.5" />
                </div>
                <span>{{ __('Roles & Permissions') }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-base-content/60 mt-0.5">
                {{ __('Manage system roles, assign granular permissions, and configure user role access') }}
            </p>
        </div>
    </div>

    <div class="space-y-4">
        {{-- Header Navigation Tabs & Actions --}}
        <div class="flex items-center justify-between border-b border-base-300 pb-2.5 gap-2 flex-wrap">
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm rounded-lg transition-all"
                    :class="tab === 'roles' ? 'btn-primary' : 'btn-ghost'"
                    @click="tab = 'roles'"
                >
                    <x-icon name="o-shield-check" class="w-3.5 h-3.5" />
                    {{ __('Roles') }}
                </button>
                @can('users.roles')
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm rounded-lg transition-all"
                        :class="tab === 'users' ? 'btn-primary' : 'btn-ghost'"
                        @click="tab = 'users'"
                    >
                        <x-icon name="o-users" class="w-3.5 h-3.5" />
                        {{ __('User Assignments') }}
                    </button>
                @endcan
            </div>

            <div x-show="tab === 'roles'">
                @can('roles.create')
                    <button
                        type="button"
                        class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                        wire:click="createRole"
                        wire:loading.attr="disabled"
                        wire:target="createRole"
                    >
                        <span wire:loading wire:target="createRole" class="loading loading-spinner loading-xs"></span>
                        <x-icon wire:loading.remove wire:target="createRole" name="o-plus" class="w-3.5 h-3.5" />
                        <span>{{ __('Create Role') }}</span>
                    </button>
                @endcan
            </div>
        </div>

        {{-- TAB 1: Roles List (Compact & Responsive with Sorting) --}}
        <div x-show="tab === 'roles'" class="space-y-3">
            <div class="overflow-x-auto rounded-xl border border-base-300 bg-base-100 shadow-2xs">
                <table class="table table-xs sm:table-sm w-full">
                    <thead>
                        <tr class="bg-base-200/60 text-xs">
                            <th wire:click="sortBy('name')" class="cursor-pointer select-none hover:bg-base-200/80 transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ __('Role Name') }}</span>
                                    @if ($sortField === 'name')
                                        <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3.5 h-3.5 text-primary" />
                                    @else
                                        <x-icon name="o-chevron-up-down" class="w-3.5 h-3.5 text-base-content/30" />
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sortBy('guard_name')" class="hidden sm:table-cell cursor-pointer select-none hover:bg-base-200/80 transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ __('Guard') }}</span>
                                    @if ($sortField === 'guard_name')
                                        <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3.5 h-3.5 text-primary" />
                                    @else
                                        <x-icon name="o-chevron-up-down" class="w-3.5 h-3.5 text-base-content/30" />
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sortBy('permissions_count')" class="cursor-pointer select-none hover:bg-base-200/80 transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ __('Permissions') }}</span>
                                    @if ($sortField === 'permissions_count')
                                        <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3.5 h-3.5 text-primary" />
                                    @else
                                        <x-icon name="o-chevron-up-down" class="w-3.5 h-3.5 text-base-content/30" />
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sortBy('users_count')" class="cursor-pointer select-none hover:bg-base-200/80 transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ __('Assigned Users') }}</span>
                                    @if ($sortField === 'users_count')
                                        <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3.5 h-3.5 text-primary" />
                                    @else
                                        <x-icon name="o-chevron-up-down" class="w-3.5 h-3.5 text-base-content/30" />
                                    @endif
                                </div>
                            </th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td class="font-semibold text-base-content">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <x-icon name="o-key" class="w-3.5 h-3.5 text-primary shrink-0" />
                                            <span class="text-xs sm:text-sm">{{ $role->name }}</span>
                                        </div>
                                        <div class="sm:hidden flex items-center gap-1">
                                            <span class="badge badge-xs badge-neutral font-mono">{{ $role->guard_name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden sm:table-cell">
                                    <span class="badge badge-xs badge-neutral font-mono">{{ $role->guard_name }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-xs sm:badge-sm badge-primary/20 text-primary font-medium">
                                        {{ $role->permissions_count }} <span class="hidden sm:inline ms-1">{{ __('permissions') }}</span>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-xs sm:badge-sm badge-secondary/20 text-secondary font-medium">
                                        {{ $role->users_count }} <span class="hidden sm:inline ms-1">{{ __('users') }}</span>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="flex items-center justify-end gap-0.5">
                                        @can('roles.edit')
                                            <button
                                                type="button"
                                                class="btn btn-ghost btn-xs btn-square"
                                                title="{{ __('Edit Role') }}"
                                                wire:click="editRole({{ $role->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="editRole({{ $role->id }})"
                                            >
                                                <span wire:loading wire:target="editRole({{ $role->id }})" class="loading loading-spinner loading-xs text-info"></span>
                                                <x-icon wire:loading.remove wire:target="editRole({{ $role->id }})" name="o-pencil-square" class="w-3.5 h-3.5 text-info" />
                                            </button>
                                        @endcan
                                        @can('roles.delete')
                                            @if ($role->name !== 'Super Admin')
                                                <button
                                                    type="button"
                                                    class="btn btn-ghost btn-xs btn-square text-error"
                                                    title="{{ __('Delete Role') }}"
                                                    wire:click="deleteRole({{ $role->id }})"
                                                    wire:confirm="{{ __('Are you sure you want to delete this role?') }}"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteRole({{ $role->id }})"
                                                >
                                                    <span wire:loading wire:target="deleteRole({{ $role->id }})" class="loading loading-spinner loading-xs text-error"></span>
                                                    <x-icon wire:loading.remove wire:target="deleteRole({{ $role->id }})" name="o-trash" class="w-3.5 h-3.5" />
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-xs text-base-content/60">
                                    {{ __('No roles found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: User Role Assignments (Compact & Responsive with Sorting) --}}
        @can('users.roles')
            <div x-show="tab === 'users'" class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search users...') }}"
                            class="input input-xs sm:input-sm input-bordered w-full pe-8 rounded-lg text-xs"
                        />
                        <x-icon name="o-magnifying-glass" class="w-3.5 h-3.5 absolute right-2.5 top-2 sm:top-2.5 text-base-content/40" />
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-base-300 bg-base-100 shadow-2xs">
                    <table class="table table-xs sm:table-sm w-full">
                        <thead>
                            <tr class="bg-base-200/60 text-xs">
                                <th wire:click="sortUsersBy('name')" class="cursor-pointer select-none hover:bg-base-200/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        <span>{{ __('User') }}</span>
                                        @if ($userSortField === 'name')
                                            <x-icon name="{{ $userSortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3.5 h-3.5 text-primary" />
                                        @else
                                            <x-icon name="o-chevron-up-down" class="w-3.5 h-3.5 text-base-content/30" />
                                        @endif
                                    </div>
                                </th>
                                <th wire:click="sortUsersBy('email')" class="hidden md:table-cell cursor-pointer select-none hover:bg-base-200/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        <span>{{ __('Email') }}</span>
                                        @if ($userSortField === 'email')
                                            <x-icon name="{{ $userSortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3.5 h-3.5 text-primary" />
                                        @else
                                            <x-icon name="o-chevron-up-down" class="w-3.5 h-3.5 text-base-content/30" />
                                        @endif
                                    </div>
                                </th>
                                <th>{{ __('Assigned Roles') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="font-medium text-base-content">
                                        <div class="flex items-center gap-2">
                                            <div class="avatar placeholder">
                                                <div class="bg-primary text-primary-content rounded-full w-6 h-6 flex items-center justify-center font-bold text-[10px]">
                                                    <span>{{ $user->initials() }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-xs sm:text-sm font-semibold">{{ $user->name }}</span>
                                                <span class="text-[10px] text-base-content/60 md:hidden">{{ $user->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell text-xs text-base-content/70">{{ $user->email }}</td>
                                    <td>
                                        <div class="flex items-center gap-1 flex-wrap">
                                            @forelse ($user->roles as $assignedRole)
                                                <span class="badge badge-xs badge-outline badge-primary font-medium">
                                                    {{ $assignedRole->name }}
                                                </span>
                                            @empty
                                                <span class="text-[10px] text-base-content/40 italic">{{ __('No roles') }}</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs text-primary font-medium gap-1"
                                            wire:click="editUserRoles({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="editUserRoles({{ $user->id }})"
                                        >
                                            <span wire:loading wire:target="editUserRoles({{ $user->id }})" class="loading loading-spinner loading-xs text-primary"></span>
                                            <x-icon wire:loading.remove wire:target="editUserRoles({{ $user->id }})" name="o-user-plus" class="w-3.5 h-3.5" />
                                            <span class="hidden sm:inline">{{ __('Manage Roles') }}</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-xs text-base-content/60">
                                        {{ __('No users found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endcan

        {{-- Role Creation & Edit Modal (Compact & Client-controlled) --}}
        <div
            class="modal"
            :class="{ 'modal-open': showRoleModal }"
            style="display: none;"
            x-show="showRoleModal"
            x-transition.opacity
        >
            <div class="modal-box max-w-xl bg-base-100 border border-base-300 rounded-xl p-4 sm:p-5 shadow-xl">
                <h3 class="font-bold text-base sm:text-lg text-base-content mb-3 flex items-center gap-2">
                    <x-icon name="o-shield-check" class="w-4.5 h-4.5 text-primary" />
                    <span x-text="$wire.roleId ? '{{ __('Edit Role') }}' : '{{ __('Create New Role') }}'"></span>
                </h3>

                <form wire:submit.prevent="saveRole" class="space-y-4">
                    <div class="form-control w-full">
                        <label class="label py-1 font-medium text-xs text-base-content">
                            <span class="label-text">{{ __('Role Name') }}</span>
                        </label>
                        <input
                            type="text"
                            wire:model="roleName"
                            placeholder="{{ __('e.g., Manager, Editor, Accountant') }}"
                            class="input input-sm input-bordered w-full rounded-lg text-xs sm:text-sm @error('roleName') input-error @enderror"
                            required
                        />
                        @error('roleName')
                            <span class="text-xs text-error mt-0.5">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Module-Grouped Permissions Matrix (Compact) --}}
                    <div class="space-y-3 border-t border-base-300 pt-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-base-content">{{ __('Assign Permissions by Module') }}</p>
                            <span class="text-[11px] text-base-content/60 font-mono">
                                <span x-text="$wire.selectedPermissions.length"></span> {{ __('selected') }}
                            </span>
                        </div>

                        <div class="space-y-2.5 max-h-72 overflow-y-auto pe-1">
                            @foreach ($groupedPermissions as $module => $modulePermissions)
                                @php
                                    $permissionNames = $modulePermissions->pluck('name')->toArray();
                                    $jsonPermissions = json_encode($permissionNames);
                                @endphp
                                <div class="rounded-lg border border-base-300 bg-base-200/30 p-2.5 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-primary">
                                            {{ __($module) }}
                                        </span>
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs text-[10px] h-6 min-h-0 px-2"
                                            @click="toggleModule({{ $jsonPermissions }})"
                                        >
                                            {{ __('Select All') }}
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pt-0.5">
                                        @foreach ($modulePermissions as $permission)
                                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded-md hover:bg-base-100 transition-colors">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $permission->name }}"
                                                    wire:model="selectedPermissions"
                                                    class="checkbox checkbox-xs checkbox-primary"
                                                />
                                                <span class="text-xs font-medium text-base-content/80">
                                                    {{ $permission->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-action border-t border-base-300 pt-3 mt-4">
                        <button
                            type="button"
                            class="btn btn-ghost btn-xs sm:btn-sm rounded-lg"
                            @click="showRoleModal = false"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                            wire:loading.attr="disabled"
                            wire:target="saveRole"
                        >
                            <span wire:loading wire:target="saveRole" class="loading loading-spinner loading-xs"></span>
                            <x-icon wire:loading.remove wire:target="saveRole" name="o-check" class="w-3.5 h-3.5" />
                            <span>{{ __('Save Role') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- User Role Assignment Modal (Compact & Client-controlled) --}}
        <div
            class="modal"
            :class="{ 'modal-open': showUserModal }"
            style="display: none;"
            x-show="showUserModal"
            x-transition.opacity
        >
            <div class="modal-box max-w-sm bg-base-100 border border-base-300 rounded-xl p-4 sm:p-5 shadow-xl">
                <h3 class="font-bold text-base sm:text-lg text-base-content mb-3 flex items-center gap-2">
                    <x-icon name="o-user-plus" class="w-4.5 h-4.5 text-primary" />
                    <span>{{ __('Assign User Roles') }}</span>
                </h3>

                <form wire:submit.prevent="saveUserRoles" class="space-y-3">
                    <p class="text-xs text-base-content/70">
                        {{ __('Select roles to assign to this user:') }}
                    </p>

                    <div class="space-y-1.5 max-h-56 overflow-y-auto">
                        @foreach ($allRoles as $r)
                            <label class="flex items-center gap-2.5 cursor-pointer p-2 rounded-lg border border-base-300 hover:bg-base-200/50 transition-colors">
                                <input
                                    type="checkbox"
                                    value="{{ $r->name }}"
                                    wire:model="userRoles"
                                    class="checkbox checkbox-xs checkbox-primary"
                                />
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold text-base-content">{{ $r->name }}</span>
                                    <span class="text-[10px] text-base-content/60">{{ $r->permissions_count }} {{ __('permissions') }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="modal-action border-t border-base-300 pt-3 mt-4">
                        <button
                            type="button"
                            class="btn btn-ghost btn-xs sm:btn-sm rounded-lg"
                            @click="showUserModal = false"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary btn-xs sm:btn-sm rounded-lg gap-1.5"
                            wire:loading.attr="disabled"
                            wire:target="saveUserRoles"
                        >
                            <span wire:loading wire:target="saveUserRoles" class="loading loading-spinner loading-xs"></span>
                            <x-icon wire:loading.remove wire:target="saveUserRoles" name="o-check" class="w-3.5 h-3.5" />
                            <span>{{ __('Update Roles') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</section>
