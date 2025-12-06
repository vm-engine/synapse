# Services

Synapse provides powerful services for managing configuration, translations, and UI components.

## DbConfig Service

Store and retrieve application configuration from the database with automatic caching.

### Using Facade

```php
use VmEngine\Synapse\Facades\DbConfig;

// Get config value
$value = DbConfig::get('app', 'maintenance_mode', false);

// Set config value
DbConfig::set('app', 'maintenance_mode', true, 'Maintenance Mode');

// Force refresh from database (bypass cache)
$value = DbConfig::get('app', 'maintenance_mode', false, true);
```

### Using Helper Function

```php
// Get config value
$value = dbconf('app.maintenance_mode', false);

// Force refresh from database (prefix with !)
$value = dbconf('!app.maintenance_mode', false);

// Set config value using array
dbconf(['app.maintenance_mode' => true]);

// Alternative get syntax
$value = dbconf('app.site_name', 'Default Site');
```

### Artisan Command

```bash
# Set configuration value
php artisan synapse:db-config app.maintenance_mode true --label="Maintenance Mode"

# Update existing config
php artisan synapse:db-config app.site_name "My Application"
```

### Features

- Automatic caching for performance
- Force refresh option to bypass cache (use `!` prefix in helper)
- Optional labels for better documentation
- Grouped configuration (group.key format)
- Supports both get and set operations

### Database Table

The `configs` table stores configuration:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `group` | string | Configuration group (default: 'global') |
| `key` | string | Configuration key |
| `value` | text | Configuration value (nullable) |
| `label` | string | Optional label for documentation (nullable) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

Unique constraint: `[group, key]`

---

## DbLang Service

Manage multi-language translations stored in the database with automatic language switching and caching.

### Using Facade

```php
use VmEngine\Synapse\Facades\DbLang;

// Get translation for current language
$text = DbLang::get('welcome.title');

// Get translation for specific language
$text = DbLang::get('welcome.title', 'id');

// Set translation
DbLang::set('welcome.title', 'Welcome to our site', 'Welcome Title');

// Set translation for specific language
DbLang::set('welcome.title', 'Selamat datang', 'Welcome Title', 'id');

// Change current language
DbLang::setLanguage('id');

// Get current language code
$lang = DbLang::getLanguage(); // 'id'
```

### Using Helper Function

```php
// Get translation for current language
$text = ___('welcome.title');

// Get translation for specific language
$text = ___('welcome.title', 'id');

// To set translations, use the facade:
use VmEngine\Synapse\Facades\DbLang;
DbLang::set('welcome.title', 'Welcome!', 'Welcome Title');
```

### Artisan Command

```bash
# Set translation for default language (en)
php artisan synapse:db-lang welcome.title "Welcome to our site" --label="Welcome Title"

# Set translation for specific language
php artisan synapse:db-lang welcome.title "Selamat datang" --label="Welcome Title" --language=id

# Update existing translation
php artisan synapse:db-lang menu.home "Home Page" --language=en
```

### Features

- Automatic caching per language for performance
- Multi-language support with auto-creation
- Language switching without reload
- Falls back to key if translation not found
- Optional labels for better documentation
- Returns key as fallback when translation is empty

### Multi-Language Auto-Creation

When multi-language is enabled in `config/synapps.php`:

```php
'apps' => [
    'multi_language' => true,
    'enabled_languages' => ['en', 'id', 'es'],
]
```

Setting a translation will automatically create empty entries for all enabled languages:

```php
// Creates entries for 'en', 'id', and 'es'
DbLang::set('welcome.title', 'Welcome!', 'Welcome Title');
```

### Database Table

The `languages` table stores translations:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `language_code` | string(10) | Language code (default: 'en') |
| `key` | string | Translation key |
| `value` | text | Translation value (nullable) |
| `label` | string | Optional label for documentation (nullable) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

Unique constraint: `[language_code, key]`

---

## Breadcrumbs Service

Generate and manage breadcrumb navigation for your application.

### Basic Usage

```php
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

// Create breadcrumbs
$breadcrumbs = Breadcrumbs::make('Home', '/')
    ->add('Users', '/users')
    ->add('John Doe', '/users/1');

// With icon
$breadcrumbs = Breadcrumbs::make('Home', '/', 'fa-solid fa-home')
    ->add('Users', '/users', 'fa-solid fa-users')
    ->add('John Doe', '/users/1', 'fa-solid fa-user');
```

### In Blade Views

```blade
@php
    $breadcrumbs = Breadcrumbs::make('Home', route('home'))
        ->add('Products', route('products.index'))
        ->add('Electronics', route('products.category', 'electronics'));
@endphp

<!-- Check if has items -->
@if($breadcrumbs->hasItems())
    <nav aria-label="Breadcrumb">
        <ol class="flex space-x-2">
            @foreach($breadcrumbs->items() as $item)
                <li>
                    @if(!$loop->last)
                        <a href="{{ $item['url'] }}" class="text-blue-600 hover:text-blue-800">
                            @if(!empty($item['icon']))
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                            {{ $item['name'] }}
                        </a>
                        <span class="mx-2">/</span>
                    @else
                        <span class="text-gray-600">
                            @if(!empty($item['icon']))
                                <i class="{{ $item['icon'] }}"></i>
                            @endif
                            {{ $item['name'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
```

### Available Methods

| Method | Parameters | Description |
|--------|-----------|-------------|
| `make($name, $url, $icon = null)` | string, string, string? | Create new breadcrumbs instance |
| `add($name, $url, $icon = null)` | string, string, string? | Add breadcrumb item |
| `hasItems()` | - | Check if breadcrumbs has any items |
| `items()` | - | Get all breadcrumb items as array |
| `count()` | - | Get number of breadcrumb items |

### Breadcrumb Item Structure

Each breadcrumb item is an array with:

```php
[
    'name' => 'Item Name',
    'url' => '/path/to/item',
    'icon' => 'fa-solid fa-icon'  // optional
]
```

### Example in Controller

```php
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        $breadcrumbs = Breadcrumbs::make('Home', route('home'))
            ->add('Products', route('products.index'))
            ->add($product->category->name, route('products.category', $product->category))
            ->add($product->name, route('products.show', $product));

        return view('products.show', [
            'product' => $product,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
```

---

## Running Migrations

DbConfig and DbLang services require database tables. Run migrations after installation:

```bash
php artisan migrate
```

This creates the `configs` and `languages` tables automatically.

## Service Registration

All services are automatically registered in the Synapse service provider:

```php
// DbConfig - Available as facade
VmEngine\Synapse\Facades\DbConfig

// DbLang - Available as facade
VmEngine\Synapse\Facades\DbLang

// Available via service container
app('dbconf')
app('dblang')

// Available via helper functions
dbconf('group.key', 'default')
___('translation.key', 'language_code')

// Breadcrumbs - Direct class usage
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
```
