# Helper Functions

Synapse provides global helper functions for common tasks throughout your application.

## Database Helpers

### dbconf()

Get or set configuration values from the database.

```php
// Get config value
$value = dbconf('app.maintenance_mode', false);

// Force refresh from database (bypass cache)
$value = dbconf('!app.maintenance_mode', false);

// Set config value using array
dbconf(['app.maintenance_mode' => true]);

// Get with default value
$siteName = dbconf('app.site_name', 'My Application');
```

**Parameters:**
- `$configString` (string|array) - Config key in `group.key` format, or array to set value. Prefix with `!` to force bypass cache
- `$default` (mixed) - Default value if config not found
- `$force` (bool) - Force refresh from database (default: false)

**Returns:** mixed - Configuration value

See [Database Services](SERVICES.md#dbconfig-service) for more details.

---

### ___()

Get translation from the database for current or specified language.

```php
// Get translation for current language
$text = ___('welcome.title');

// Get translation for specific language
$text = ___('welcome.title', 'id');

// With default fallback (returns key if not found)
$text = ___('page.heading'); // Returns 'page.heading' if translation missing
```

**Parameters:**
- `$key` (string) - Translation key
- `$languageCode` (string|null) - Optional language code (defaults to current language)

**Returns:** string - Translation value or key if not found

See [Database Services](SERVICES.md#dblang-service) for more details.

---

## JavaScript Helpers

### withBack()

Appends the current page URL as a `back` query parameter to a given URL, enabling form pages to redirect back to the correct list page after saving.

Injected into `resources/js/app.js` by `synapps:patch`. Available globally as `window.withBack()`, and registered as the Alpine magic `$withBack()` for use inside Alpine expressions — Alpine's CSP-safe evaluator (on by default) only resolves its own scope and registered magics, not arbitrary `window.*` globals.

```js
// Basic usage
withBack('/posts/create')
// → '/posts/create?back=<encoded-current-url>'

// Works when the URL already has query params
withBack('/posts/create?draft=1')
// → '/posts/create?draft=1&back=<encoded-current-url>'
```

**Parameters:**
- `url` (string) - Destination URL

**Returns:** string - URL with `back=<current-url>` appended

**Usage in Blade with Alpine.js / wire:navigate:**

```blade
{{-- Simple link --}}
<a :href="$withBack('{{ route('posts.create') }}')" wire:navigate>
    Create Post
</a>

{{-- In a loop --}}
@foreach($posts as $post)
    <a :href="$withBack('{{ route('posts.edit', $post) }}')" wire:navigate>
        Edit
    </a>
@endforeach
```

**How back-URL is consumed:**

Use the [`WithReturnUrl`](TRAITS.md#withreturnurl) trait in your Livewire form component. It automatically binds `$backUrl` from the `?back=` parameter and provides a safe `redirectBack()` method.

```php
use VmEngine\Synapse\Traits\WithReturnUrl;

class PostForm extends Component
{
    use WithReturnUrl;

    public function save(): void
    {
        // ...
        $this->redirectBack('posts.index'); // redirects to back URL or falls back to posts.index
    }
}
```

**Notes:**
- The `back` parameter is re-evaluated at mousedown time (not at render time) so Livewire pagination changes are correctly reflected in the URL
- Invalid or external URLs are rejected by `WithReturnUrl::isSafeReturnUrl()` as an open-redirect protection

---

## Theme Helpers

### theme_asset()

Get URL to theme asset file (images, fonts, CSS, JS, etc.).

```php
// Get logo URL from active theme
$logo = theme_asset('images/logo.svg');
// Returns: http://localhost/assets/themes/default/images/logo.svg

// Get dark mode logo
$darkLogo = theme_asset('images/logo-dark.svg');
// Returns: http://localhost/assets/themes/default/images/logo-dark.svg

// Get asset from specific theme
$customBanner = theme_asset('images/banner.jpg', 'custom-theme');
// Returns: http://localhost/assets/themes/custom-theme/images/banner.jpg

// Get font file
$font = theme_asset('fonts/custom-font.woff2');
// Returns: http://localhost/assets/themes/default/fonts/custom-font.woff2
```

**Parameters:**
- `$path` (string) - Path relative to theme assets folder
- `$theme` (string|null) - Theme name (null = active theme)

**Returns:** string - Public URL to theme asset

**Usage in Blade:**
```blade
<img src="{{ theme_asset('images/logo.svg') }}" alt="Logo">

<!-- Dark mode logo variant -->
<img src="{{ theme_asset('images/logo-dark.svg') }}"
     alt="Logo"
     class="hidden dark:block">
```

---

### theme_path()

Get absolute filesystem path to theme directory or file.

```php
// Get theme root path
$themePath = theme_path();
// Returns: /app/synapps/resources/themes/default

// Get specific file path
$cssPath = theme_path('css/theme.css');
// Returns: /app/synapps/resources/themes/default/css/theme.css

// Get path from specific theme
$layoutPath = theme_path('layouts/app.blade.php', 'custom-theme');
// Returns: /app/synapps/resources/themes/custom-theme/layouts/app.blade.php

// Check if theme file exists
if (file_exists(theme_path('css/custom.css'))) {
    // Theme has custom CSS
}

// Load theme configuration
$config = json_decode(file_get_contents(theme_path('theme.json')), true);
```

**Parameters:**
- `$path` (string) - Path relative to theme folder (empty = theme root)
- `$theme` (string|null) - Theme name (null = active theme)

**Returns:** string - Absolute filesystem path

---

### active_theme()

Get the currently active theme name.

```php
// Get active theme
$theme = active_theme();
// Returns: 'default' or 'custom-theme'

// Conditional rendering based on theme
if (active_theme() === 'dark-theme') {
    // Load extra dark mode styles
}

// Use in Blade
@if(active_theme() === 'custom-theme')
    <link rel="stylesheet" href="/extra-styles.css">
@endif
```

**Parameters:** None

**Returns:** string - Active theme name

---

### theme_service()

Get the ThemeService instance.

```php
// Get theme service
$themeService = theme_service();

// Get active theme name
$theme = theme_service()->getActiveTheme();

// Get current layout type (auto-detected from URL)
$layoutType = theme_service()->getCurrentLayoutType();
// Returns: 'app', 'frontend', or 'guest'

// Resolve theme layout with fallback
$view = theme_service()->resolveThemeLayout('app');
// Returns: 'theme::app' or 'theme-fallback::app' or 'synapps::components.layouts.app'

// Render CDN styles
$styles = theme_service()->renderStyles('app');
// Returns: '<link rel="stylesheet" href="...">...'

// Render CDN scripts
$scripts = theme_service()->renderScripts('app');
// Returns: '<script src="..."></script>...'
```

**Parameters:** None

**Returns:** ThemeService - Theme service instance

See [Theme Service](SERVICES.md#themeservice) and [Theme System](THEMES.md) for more details.

---

## Security Helpers

### csp_nonce()

Get the per-request Content-Security-Policy nonce, bound by the `SecureHeaders` middleware before the response is rendered. Stamp it onto any inline `<script>` tag instead of relying on `'unsafe-inline'`.

```blade
<script nonce="{{ csp_nonce() }}">
    Alpine.data('myComponent', () => ({ open: false }));
</script>
```

**Parameters:** None

**Returns:** string - The current request's CSP nonce, or an empty string if `SecureHeaders` hasn't bound one (e.g. outside an HTTP request lifecycle)

See [Security & Logging Configuration](CONFIGURATION.md#security--logging-configuration) for the related `csp` config block.

---

## Path Helpers

### synapps_path()

Get absolute path to files within the synapps directory.

```php
// Get path to modules directory
$path = synapps_path('modules/example/routes/web.php');
// Returns: /app/synapps/modules/example/routes/web.php

// Get synapps root path
$path = synapps_path();
// Returns: /app/synapps

// Get path to config
$path = synapps_path('config/apps.json');
// Returns: /app/synapps/config/apps.json
```

**Parameters:**
- `$path` (string) - Path relative to synapps directory

**Returns:** string - Absolute path

---

### template_assets()

Generate URL to template assets.

```php
// Get CSS file URL
$url = template_assets('css', 'style.css');
// Returns: https://example.com/assets/template/default/css/style.css

// Get JavaScript file URL
$url = template_assets('js', 'main.js');
// Returns: https://example.com/assets/template/default/js/main.js

// Get image URL
$url = template_assets('images', 'logo.png');
// Returns: https://example.com/assets/template/default/images/logo.png
```

**Parameters:**
- `$folder` (string) - Asset folder name
- `$file` (string) - File name

**Returns:** string - Asset URL

---

### DS

Directory separator constant for cross-platform compatibility.

```php
// Build path with DS constant
$path = __DIR__ . DS . 'config' . DS . 'app.php';

// On Windows: __DIR__ . '\' . 'config' . '\' . 'app.php'
// On Linux: __DIR__ . '/' . 'config' . '/' . 'app.php'
```

**Value:** `DIRECTORY_SEPARATOR`

---

## Routing Helpers

### is_route()

Check if current route matches the given route name(s) and return a class name.

```php
// Single route check
<li class="{{ is_route('users.index') }}">Users</li>
// Returns 'active' if on users.index route

// Multiple routes check
<li class="{{ is_route(['posts.create', 'posts.edit']) }}">Posts</li>
// Returns 'active' if on posts.create OR posts.edit

// Custom class name
<li class="{{ is_route('users.index', 'current') }}">Users</li>
// Returns 'current' instead of 'active'
```

**Parameters:**
- `$name` (string|array) - Route name(s) to check
- `$class` (string) - Class name to return if matched (default: 'active')

**Returns:** string - Class name if matched, empty string otherwise

---

### is_route_prefix()

Check if current route starts with the given prefix and return a class name.

```php
// Check route prefix
<li class="{{ is_route_prefix('admin') }}">Admin</li>
// Returns 'active' for routes like admin.users, admin.settings, etc.

// Custom class name
<li class="{{ is_route_prefix('backend', 'selected') }}">Backend</li>
// Returns 'selected' if route starts with 'backend'
```

**Parameters:**
- `$prefix` (string) - Route prefix to check
- `$class` (string) - Class name to return if matched (default: 'active')

**Returns:** string - Class name if matched, empty string otherwise

---

### route_api_opt()

Get URL for API routes with option to use apps URL or API URL.

```php
// Use API URL
$url = route_api_opt('/api/users', '/users');
// Returns configured API URL + '/users'

// Use apps URL
$url = route_api_opt('/users', '/users', useAppsUrl: true);
// Returns configured apps URL + '/users'
```

**Parameters:**
- `$appsUrl` (string) - Apps URL path
- `$apiUrl` (string) - API URL path

**Returns:** string - Complete URL

---

### route_web_opt()

Get URL for web routes with option to use apps URL or web URL.

```php
// Use web URL
$url = route_web_opt('/users', '/users');
// Returns configured web URL + '/users'

// Use apps URL
$url = route_web_opt('/users', '/users', useAppsUrl: true);
// Returns configured apps URL + '/users'
```

**Parameters:**
- `$appsUrl` (string) - Apps URL path
- `$webUrl` (string) - Web URL path

**Returns:** string - Complete URL

---

## Number Helpers

### is_decimal()

Check if a number has decimal places.

```php
// Check for decimals
is_decimal(1500.02);  // true
is_decimal(1500.00);  // false
is_decimal(1500);     // false
is_decimal('150.5');  // true
```

**Parameters:**
- `$value` (int|float|string) - Value to check

**Returns:** bool - True if has decimals

---

### clean_number_format()

Format number and remove unnecessary decimal zeros.

```php
// Format without decimals
clean_number_format(1500.00);
// Returns: "1.500"

// Format with necessary decimals
clean_number_format(1500.02, 2);
// Returns: "1.500,02"

// Custom formatting
clean_number_format(1234567.89, 2, ',', '.');
// Returns: "1.234.567,89"

// Remove trailing zeros
clean_number_format(1500.50, 2);
// Returns: "1.500,5"
```

**Parameters:**
- `$value` (int|float|string) - Value to format
- `$precision` (int) - Decimal precision (default: 0)
- `$decimal` (string) - Decimal separator (default: ',')
- `$thousand` (string) - Thousand separator (default: '.')

**Returns:** string - Formatted number

---

## Array Helpers

### recuresive_array_merge()

Deep merge two arrays recursively.

```php
$array1 = [
    'config' => ['debug' => true],
    'features' => ['search', 'export']
];

$array2 = [
    'config' => ['debug' => false, 'cache' => true],
    'features' => ['import'],
    'new_key' => 'value'
];

$merged = recuresive_array_merge($array1, $array2);
// Returns:
// [
//     'config' => ['debug' => false, 'cache' => true],
//     'features' => ['search', 'export', 'import'],
//     'new_key' => 'value'
// ]
```

**Parameters:**
- `$a1` (array) - First array
- `$a2` (array) - Second array (takes precedence)

**Returns:** array - Merged array

---

### array_is_list()

Check if array is a list (sequential numeric keys starting from 0).

```php
// Is a list
array_is_list([1, 2, 3]);               // true
array_is_list(['a', 'b', 'c']);         // true

// Not a list
array_is_list(['a' => 1, 'b' => 2]);    // false
array_is_list([1 => 'a', 2 => 'b']);    // false (doesn't start at 0)
array_is_list([0 => 'a', 2 => 'b']);    // false (not sequential)
```

**Parameters:**
- `$arr` (array) - Array to check

**Returns:** bool - True if array is a list

---

## Pagination Helpers

### pagination_format()

Format pagination data into array.

```php
$pagination = pagination_format(100, 0, 20);
// Returns:
// [
//     'count' => 100,
//     'offset' => 0,
//     'limit' => 20,
//     'total_pages' => 5,
//     'current_page' => 1
// ]
```

**Parameters:**
- `$count` (int) - Total items count
- `$offset` (int) - Offset value
- `$limit` (int) - Items per page

**Returns:** array - Pagination data

---

### pagination_convert_link()

Convert URL query parameters to offset/limit format.

```php
$url = 'https://api.example.com/users?page=2&per_page=20';
$params = pagination_convert_link($url, 20);
// Returns: ['offset' => 20, 'limit' => 20]
```

**Parameters:**
- `$url` (string) - URL with query parameters
- `$limit` (int) - Items per page

**Returns:** array - Offset and limit values

---

### pagination_generate()

Generate Laravel LengthAwarePaginator instance.

```php
$items = User::all();
$paginator = pagination_generate(
    ['count' => 100, 'offset' => 0, 'limit' => 20],
    '/users',
    'users.index'
);

// Use in Blade
{{ $paginator->links() }}
```

**Parameters:**
- `$param` (array) - Pagination parameters from `pagination_format()`
- `$path` (string) - Base URL path
- `$view` (string) - View name for pagination links

**Returns:** \Illuminate\Pagination\LengthAwarePaginator

---

## Usage Best Practices

### Database Helpers

```php
// Prefer using dbconf() over direct database queries
$maintenanceMode = dbconf('app.maintenance_mode', false);

// Use ___() for all user-facing text that needs translation
echo ___('welcome.message');
```

### Theme Helpers

```php
// ✅ Good - Uses helper function
<img src="{{ theme_asset('images/logo.svg') }}" alt="Logo">

// ❌ Bad - Hardcoded path
<img src="/assets/themes/default/images/logo.svg" alt="Logo">

// ✅ Good - Checks file existence
if (file_exists(theme_path('css/custom.css'))) {
    echo '<link rel="stylesheet" href="'.theme_asset('css/custom.css').'">';
}

// ✅ Good - Theme-specific behavior
if (active_theme() === 'dark-theme') {
    // Load additional dark mode scripts
}
```

### Path Helpers

```php
// Always use synapps_path() for module file paths
$configPath = synapps_path('config/apps.json');

// Use template_assets() for theme resources
<link rel="stylesheet" href="{{ template_assets('css', 'theme.css') }}">
```

### Routing Helpers

```php
// Use in navigation menus for active state
<nav>
    <a href="{{ route('home') }}" class="{{ is_route('home') }}">Home</a>
    <a href="{{ route('users.index') }}" class="{{ is_route_prefix('users') }}">Users</a>
</nav>
```
