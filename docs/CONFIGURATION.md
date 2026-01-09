# Configuration

Advanced configuration features in Synapse.

## Environment Variables in JSON Config Files

Synapse allows you to reference environment variables directly in JSON configuration files using the `env::` prefix.

### Supported Files

This feature works in all JSON configuration files:

- `synapps/config/apps.json`
- `synapps/config/apps.dev.json`
- `synapps/config/system.json`
- `synapps/config/system.dev.json`
- `synapps/config/modules.json`
- `synapps/config/modules.dev.json`
- `module.json` (in any module)

**Note:** This does NOT work in `composer.json` or `package.json`.

### Usage

Use the `env::` prefix followed by the environment variable name:

```json
{
    "app_name": "env::APP_NAME",
    "app_url": "env::APP_URL",
    "app_debug": "env::APP_DEBUG",
    "custom_api_key": "env::CUSTOM_API_KEY"
}
```

### How It Works

During configuration generation (`synapps:config`), Synapse automatically:

1. Scans all JSON config values for the `env::` prefix
2. Extracts the environment variable name
3. Replaces the value with the actual environment variable value from `.env`
4. Compiles the final configuration

### Examples

#### apps.json

```json
{
    "app_code": "my_app",
    "app_name": "env::APP_NAME",
    "app_url": "env::APP_URL",
    "app_key": "env::APP_KEY",
    "mode": "env::APP_ENV",
    "locale": "env::APP_LOCALE",
    "timezone": "env::APP_TIMEZONE"
}
```

With `.env`:
```env
APP_NAME="My Application"
APP_URL=https://example.com
APP_KEY=base64:...
APP_ENV=production
APP_LOCALE=en
APP_TIMEZONE=UTC
```

Results in compiled config:
```json
{
    "app_code": "my_app",
    "app_name": "My Application",
    "app_url": "https://example.com",
    "app_key": "base64:...",
    "mode": "production",
    "locale": "en",
    "timezone": "UTC"
}
```

#### module.json

```json
{
    "id": "payment",
    "name": "Payment Module",
    "version": "1.0.0",
    "url_prefix": "payment",
    "config": {
        "stripe_key": "env::STRIPE_KEY",
        "stripe_secret": "env::STRIPE_SECRET",
        "paypal_client_id": "env::PAYPAL_CLIENT_ID",
        "paypal_secret": "env::PAYPAL_SECRET"
    }
}
```

#### system.json

```json
{
    "database": {
        "host": "env::DB_HOST",
        "port": "env::DB_PORT",
        "database": "env::DB_DATABASE",
        "username": "env::DB_USERNAME",
        "password": "env::DB_PASSWORD"
    },
    "mail": {
        "host": "env::MAIL_HOST",
        "port": "env::MAIL_PORT",
        "from_address": "env::MAIL_FROM_ADDRESS"
    }
}
```

### Nested Values

The `env::` prefix works with nested JSON structures:

```json
{
    "services": {
        "api": {
            "key": "env::API_KEY",
            "secret": "env::API_SECRET",
            "url": "env::API_URL"
        },
        "cache": {
            "driver": "env::CACHE_DRIVER",
            "prefix": "env::CACHE_PREFIX"
        }
    }
}
```

### Best Practices

#### 1. Sensitive Data

Always use `env::` for sensitive information:

```json
{
    "database_password": "env::DB_PASSWORD",
    "api_secret": "env::API_SECRET",
    "encryption_key": "env::APP_KEY"
}
```

#### 2. Environment-Specific Values

Use for values that change between environments:

```json
{
    "app_url": "env::APP_URL",
    "app_debug": "env::APP_DEBUG",
    "log_level": "env::LOG_LEVEL"
}
```

#### 3. Default Values

Environment variables without defaults return `null`. Handle in your code:

```php
$apiKey = config('synapps.services.api.key');
if (empty($apiKey)) {
    throw new Exception('API key not configured');
}
```

Or use Laravel's `env()` helper with defaults in PHP config files.

#### 4. Documentation

Document required environment variables in your `module.json` or README:

```json
{
    "id": "payment",
    "name": "Payment Module",
    "description": "Required env vars: STRIPE_KEY, STRIPE_SECRET",
    "config": {
        "stripe_key": "env::STRIPE_KEY",
        "stripe_secret": "env::STRIPE_SECRET"
    }
}
```

### Regenerating Configuration

After changing environment variables, regenerate configuration:

```bash
php artisan synapps:config
```

This re-reads `.env` and compiles fresh configuration with updated values.

### Limitations

1. **Not for composer.json/package.json** - These files are read by Composer/npm, not Synapse
2. **No default values** - Unlike PHP's `env('KEY', 'default')`, JSON `env::` doesn't support defaults
3. **String values only** - All environment variables are returned as strings
4. **Compilation required** - Changes to `.env` require running `synapps:config`

### Accessing Values

After compilation, access values through Laravel's config system:

```php
// From apps.json
$appName = config('synapps.apps.app_name');

// From module.json config section
$stripeKey = config('synapps.modules.payment.config.stripe_key');

// From system.json
$dbHost = config('synapps.system.database.host');
```

---

## Configuration Override System

Synapse provides a powerful configuration override system that allows modules and applications to override Laravel's default configuration files without modifying the original files.

### How It Works

The override system automatically merges configuration files from `synapps/config/override/` into Laravel's configuration during application bootstrap.

### Directory Structure

```
synapps/
└── config/
    └── override/
        ├── app.php        # Override config/app.php
        ├── database.php   # Override config/database.php
        ├── mail.php       # Override config/mail.php
        └── custom.php     # Override any custom config file
```

### Usage

#### 1. Create Override File

Create a configuration file in `synapps/config/override/` with the same name as the Laravel config file you want to override.

```php
// synapps/config/override/app.php
<?php

return [
    'name' => env('APP_NAME', 'My Custom App Name'),
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id',
    'fallback_locale' => 'en',

    // Add custom configuration
    'custom_setting' => true,

    // Override specific providers
    'providers' => [
        // Additional service providers
        App\Providers\CustomServiceProvider::class,
    ],
];
```

#### 2. Configuration Merging

The override file will be **recursively merged** with the original configuration:

```php
// Original config/app.php
[
    'name' => 'Laravel',
    'timezone' => 'UTC',
    'locale' => 'en',
]

// After merging with synapps/config/override/app.php
[
    'name' => 'My Custom App Name',    // Overridden
    'timezone' => 'Asia/Jakarta',      // Overridden
    'locale' => 'id',                  // Overridden
    'fallback_locale' => 'en',         // Added
    'custom_setting' => true,          // Added
]
```

### Features

#### Recursive Merging

The system performs deep merging of arrays:

```php
// config/database.php (original)
[
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
        ]
    ]
]

// synapps/config/override/database.php
[
    'connections' => [
        'mysql' => [
            'host' => 'db.example.com',  // Override just the host
            'strict' => true,             // Add new option
        ]
    ]
]

// Merged result
[
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',           // Kept from original
            'host' => 'db.example.com',    // Overridden
            'port' => '3306',              // Kept from original
            'strict' => true,              // Added from override
        ]
    ]
]
```

#### Automatic Discovery

Override files are automatically discovered and applied during application bootstrap. No manual registration required.

#### Safe Overrides

- Only affects files that exist in `config/` directory
- Override files that don't match existing config files are ignored
- Original config files remain unchanged

### Common Use Cases

#### 1. Environment-Specific Configuration

```php
// synapps/config/override/app.php
<?php

return [
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://production.example.com'),

    // Production-specific settings
    'asset_url' => env('ASSET_URL', 'https://cdn.example.com'),
];
```

#### 2. Module-Specific Service Providers

```php
// synapps/config/override/app.php
<?php

return [
    'providers' => [
        // Add module service providers
        SynApps\Modules\Blog\BlogServiceProvider::class,
        SynApps\Modules\Shop\ShopServiceProvider::class,
    ],
];
```

#### 3. Custom Database Connections

```php
// synapps/config/override/database.php
<?php

return [
    'connections' => [
        'analytics' => [
            'driver' => 'mysql',
            'host' => env('ANALYTICS_DB_HOST', '127.0.0.1'),
            'database' => env('ANALYTICS_DB_DATABASE', 'analytics'),
            'username' => env('ANALYTICS_DB_USERNAME', 'root'),
            'password' => env('ANALYTICS_DB_PASSWORD', ''),
        ],
    ],
];
```

### Best Practices

1. **Use Environment Variables** - Always use `env()` for sensitive or environment-specific values
2. **Document Your Overrides** - Add comments explaining why you're overriding specific values
3. **Keep Overrides Minimal** - Only override what's necessary
4. **Version Control** - Commit override files but exclude sensitive data

### Troubleshooting

**Issue:** Override not being applied

**Solutions:**
1. Ensure file name matches exactly (e.g., `app.php`, not `App.php`)
2. Clear config cache: `php artisan config:clear`
3. Verify the config file exists in Laravel's `config/` directory

---

## Configuration Files Reference

### Source Configuration Files

Located in `synapps/config/`:

| File | Purpose | Supports `env::` |
|------|---------|------------------|
| `apps.json` | Application-level configuration | ✅ Yes |
| `apps.dev.json` | Development overrides for apps.json | ✅ Yes |
| `system.json` | System-level configuration | ✅ Yes |
| `system.dev.json` | Development overrides for system.json | ✅ Yes |
| `modules.json` | Module overrides and settings | ✅ Yes |
| `modules.dev.json` | Development overrides for modules.json | ✅ Yes |
| `config.json` | Protection rules for configuration | ❌ No |

### Compiled Configuration Files

Generated in `synapps/config/compiled/`:

| File | Purpose |
|------|---------|
| `apps.json` | Compiled application configuration |
| `system.json` | Compiled system configuration |
| `modules.json` | Compiled module configuration with discovery |
| `binding.json` | Service container bindings |
| `checksum` | Configuration hash for cache validation |

### Override Configuration Files

Located in `synapps/config/override/`:

- Any PHP file matching a Laravel config file name
- Automatically merged with Laravel's configuration
- Supports deep array merging
- Applied during application bootstrap

---

---

## Theme Configuration

Synapse provides a comprehensive theme system with configuration at both application and theme levels.

### Application-Level Configuration

Configure themes in `synapps/config/apps.json`:

```json
{
  "themes": {
    "active": "default",
    "fallback": "default",
    "css": {
      "all": [],
      "app": [],
      "frontend": [],
      "guest": []
    },
    "js": {
      "all": [],
      "app": [],
      "frontend": [],
      "guest": []
    },
    "headers": {
      "all": [],
      "app": [],
      "frontend": [],
      "guest": []
    }
  }
}
```

**Configuration Keys:**

- `active` - Currently active theme name
- `fallback` - Fallback theme when active theme is missing
- `css` - Additional CSS files to load (CDN URLs)
- `js` - Additional JavaScript files to load (CDN URLs)
- `headers` - Additional HTML headers (meta tags, etc.)

**Layout Types:**

- `all` - Loaded on every page
- `app` - Backend/admin pages (URL matches backend prefix)
- `frontend` - Public website pages
- `guest` - Authentication pages (login, register)

### Theme-Level Configuration

Each theme has its own `theme.json` file in `synapps/resources/themes/{theme-name}/`:

```json
{
  "id": "my-theme",
  "name": "My Custom Theme",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "A custom theme",
  "framework": "tailwind",
  "tailwind_version": "4.x",
  "alpine_version": "3.x",
  "layouts": ["app", "guest", "frontend"],

  "css": {
    "all": ["https://fonts.googleapis.com/css2?family=Inter"],
    "app": [],
    "frontend": []
  },

  "js": {
    "all": [],
    "app": []
  },

  "headers": {
    "all": ["<meta name=\"theme-color\" content=\"#4F46E5\">"]
  }
}
```

### Resource Merging

During config generation, Synapse automatically:

1. Loads active theme's `theme.json`
2. Merges theme resources with application-level resources
3. Stores merged configuration in compiled `apps.json`

**Merge Order:**
```
Application resources (apps.json)
  + Theme resources (theme.json)
  = Final configuration (compiled/apps.json)
```

### Accessing Theme Configuration

```php
// Get active theme name
$theme = config('synapps.apps.themes.active');

// Get theme metadata
$metadata = config('synapps.apps.themes.metadata');

// Get CSS resources for specific layout
$appCss = config('synapps.apps.themes.css.app', []);

// Get JS resources for all layouts
$allJs = config('synapps.apps.themes.js.all', []);
```

### Environment Variables in Theme Config

Theme configuration supports `env::` prefix:

```json
{
  "css": {
    "all": ["env::CDN_FONT_URL"],
    "app": ["env::ADMIN_CSS_URL"]
  },
  "headers": {
    "all": ["<meta name=\"api-key\" content=\"env::API_KEY\">"]
  }
}
```

---

## See Also

- [Theme System Documentation](THEMES.md) - Complete theming guide
- [Artisan Commands](COMMANDS.md) - `synapps:config` and `synapps:make-theme` commands
- [Installation Guide](INSTALLATION.md) - Initial configuration setup
- [Creating Modules](MODULES.md) - Module configuration with `module.json`
- [Helper Functions](HELPERS.md) - Theme helper functions
