# 📋 Application Development Guidelines & Standards

### 1. 🌐 Internationalization & Localization
- **Rule**: Always wrap all user-facing static text using `@lang('...')` or `__('...')`. Never leave raw un-translated strings in templates.

### 2. 🔐 Authorization & Permission-First Architecture (Spatie RBAC)
- **Permission-First Rule**: Do NOT check roles directly in PHP/Livewire code (e.g. avoid `$user->hasRole('admin')`). Always authorize against specific permissions (e.g. `$this->authorize('feature.view')` or `$user->can('feature.manage')`).
- **Seeder Maintenance**: When creating new screens or actions, update/create permission seeders (`RolesAndPermissionsSeeder`) to register the required permissions.

### 3. 🎨 UI, Frontend & Livewire Conventions
- **UI Framework**: Use **MaryUI** & **DaisyUI** with Tailwind CSS. Support all DaisyUI themes via `data-theme`.
- **Views Architecture**:
  - `resources/views/auth/`: Single-file Livewire auth components (`auth::*`).
  - `resources/views/app/`: App & backend dashboard components (`app::*`).
  - `resources/views/web/`: Frontend UI views (`web::*`).
  - `resources/views/layouts/`: Base layouts (`layouts::base`, `layouts::app.*`, `layouts::auth.*`, `layouts::web.*`).
- **Performance & Alpine.js**: Use Alpine.js (`x-data`, `x-show`, `$persist`) for modals, tabs, and client-side toggles to minimize unnecessary server requests.
- **Table Design**: Keep table layouts compact, responsive, and mobile-friendly (`table-xs sm:table-sm`). Implement clickable column sorting (`sortBy`, `sortDirection`) with visual direction indicators.
- **Action Loading Indicators**: Add action-scoped loading spinners (`wire:loading`, `wire:target="action"`) directly on buttons and mutation triggers instead of universal top progress bars. 

### 4. 🗄️ Database & Migration Guidelines
- **Consolidated Feature Migrations**: Group related tables into module migration files (e.g., `create_module_tables`) to avoid migration folder clutter.
- **Foreign Keys Safety**: Disable foreign key constraints during drops in `down()`:
  ```php
  public function down(): void
  {
      Schema::disableForeignKeyConstraints();
      Schema::dropIfExists('table_name');
      Schema::enableForeignKeyConstraints();
  }
  ```
### 4. 🗄️ Seeding new data
after creating a system create related data properly using seeder, use findOrCreate so that i can easyly run php artisan db seed every time i need.

### . 🚫 Development & Testing Constraints
- Do NOT create test files or use Chrome browser subagent directly unless explicitly requested by the user.
- Format modified PHP files with Laravel Pint (`vendor/bin/pint --dirty --format agent`).