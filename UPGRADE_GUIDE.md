# Upgrade Guide

## Upgrading from v1.x to v2.0

### Requirements

- PHP 8.2+
- Laravel 12.x
- Livewire 4.x (previously 3.x)

### Step 1: Update Dependencies

Update your `composer.json`:

```json
{
    "require": {
        "vm-engine/synapse": "^2.0",
        "livewire/livewire": "^4.0"
    }
}
```

Then run:

```bash
composer update
```

### Step 2: Migrate JavaScript Hooks

Livewire v4 replaced the hook system with interceptors. Update any custom JavaScript hooks:

**Before (v3):**
```javascript
Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
    // Before request
});

Livewire.hook('morph.updated', ({ el, component }) => {
    // After DOM morph
});

Livewire.hook('message.processed', (message, component) => {
    // After message processed
});
```

**After (v4):**
```javascript
Livewire.interceptMessage(({ onSuccess, onError, onFailure }) => {
    // Before request (runs immediately)

    onSuccess(() => {
        // After successful response
    });

    onError(() => {
        // After error response
    });

    onFailure(() => {
        // After failed request (network error, etc.)
    });
});
```

### Step 3: Migrate Routes

Replace `Route::get()` with `Route::livewire()` for Livewire full-page components:

**Before (v3):**
```php
use App\Livewire\Dashboard;

Route::get('/dashboard', Dashboard::class)->name('dashboard');
```

**After (v4):**
```php
use App\Livewire\Dashboard;

Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
```

### Step 4: Convert Components to MFC (Optional)

Livewire v4 introduces Multi-File Components (MFC) as the new recommended format. You can optionally convert your existing class-based components.

**Before (class-based):**
```
src/Livewire/UserList.php              # PHP class
resources/views/livewire/user-list.blade.php   # Blade view
```

**After (MFC):**
```
resources/views/components/user-list/user-list.php         # Anonymous PHP class
resources/views/components/user-list/user-list.blade.php   # Blade view
```

**MFC PHP file format:**
```php
<?php

use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        // Initialize
    }

    // Methods here
};
```

**Generate new MFC components:**
```bash
# Via artisan (directory modules)
php artisan synapps:make-livewire {module} {name} --mfc

# Via synmod-make (package modules)
./vendor/bin/synmod-make livewire {module} {name} --mfc
```

### Step 5: Update Component Registration

If you were manually registering Livewire components, switch to the `AutoRegistersComponents` trait which now supports MFC discovery:

**Before:**
```php
class MyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('my-module.user-list', UserList::class);
        Livewire::component('my-module.user-form', UserForm::class);
    }
}
```

**After:**
```php
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class MyServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register(): void
    {
        $this->registerComponents();
    }
}
```

The trait automatically discovers both class-based components in `src/Livewire/` and MFC components in `resources/views/components/`.

### Step 6: Verify

Run your test suite to verify everything works:

```bash
./vendor/bin/pest
```

### New Features Available

After upgrading, you can use these new Livewire v4 features:

- **MFC Components:** `php artisan synapps:make-livewire {module} {name} --mfc`
- **SFC Components:** `php artisan synapps:make-livewire {module} {name} --sfc`
- **Route::livewire():** Dedicated routing method for full-page components
- **Livewire.interceptMessage():** Unified request lifecycle interceptor

### Troubleshooting

**"Route::livewire() method not found"**
- Ensure Livewire v4 is installed: `composer show livewire/livewire`
- Clear route cache: `php artisan route:clear`

**Components not discovered**
- Run `php artisan synapps:discover` to refresh module discovery
- Ensure MFC directories follow the naming convention: `resources/views/components/{name}/{name}.php`

**JavaScript errors in console**
- Search your codebase for `Livewire.hook(` and migrate to `Livewire.interceptMessage()`
- Check browser console for specific deprecation warnings
