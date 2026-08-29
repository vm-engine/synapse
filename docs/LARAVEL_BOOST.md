# Laravel Boost Compatibility Guide

This guide shows how to use [Laravel Boost](https://github.com/laravel/mcp) MCP tools effectively with the Synapse modular architecture.

## Overview

Laravel Boost is an MCP (Model Context Protocol) server that provides powerful tools for Laravel development. Synapse is fully compatible with all Laravel Boost tools.

## Available Tools

### 1. list-artisan-commands

Lists all available Artisan commands, including Synapse-specific commands.

**Usage:**
```bash
# Claude Code automatically uses this tool when you ask about commands
"What commands are available for Synapse?"
"Show me all synapps commands"
```

**Synapse Commands:**
- `synapps:setup` - Interactive setup wizard
- `synapps:config` - Regenerate configuration files
- `synapps:discover` - Discover modules and dependencies
- `synapps:patch` - Integrate Synapse resources and configure testing infrastructure
- `synapps:link-errors` - Link custom error views
- `synapps:publish-settings` - Publish Settings module
- `synapse:db-config` - Manage database configuration
- `synapse:db-lang` - Manage database translations

### 2. tinker - Interactive PHP REPL

Execute PHP code to debug, query models, and test Synapse services.

**Examples:**

#### Testing DbConfig Service
```php
// Get configuration value
dbconf('app.site_name', 'Default Site');

// Set configuration value
dbconf(['app.maintenance_mode' => true]);

// Using facade
use VmEngine\Synapse\Facades\DbConfig;
DbConfig::get('app', 'maintenance_mode', false);
DbConfig::set('app', 'site_name', 'My Laravel App', 'Site Name');

// Force refresh from database (bypass cache)
dbconf('!app.site_name');
```

#### Testing DbLang Service
```php
// Get translation
___('welcome.title');
___('welcome.message', 'id'); // Specific language

// Using facade
use VmEngine\Synapse\Facades\DbLang;
DbLang::get('welcome.title');
DbLang::set('welcome.title', 'Welcome!', 'Welcome Title');
DbLang::setLanguage('id');
```

#### Testing Breadcrumbs
```php
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

$breadcrumbs = Breadcrumbs::make('Home', '/')
    ->add('Users', '/users')
    ->add('John Doe', '/users/1', 'fa-user');

$breadcrumbs->items(); // Get all items
$breadcrumbs->count(); // Get count
```

#### Testing Module Discovery
```php
use VmEngine\Synapse\Services\SynapseUtils;

// List all module namespaces
SynapseUtils::listModuleNamespaces(
    config('synapse.module_namespaces'),
    fn($path) => true
);

// Check autoload status
SynapseUtils::isSynAppsAutoloaded();
```

#### Testing Helper Functions
```php
// Path helpers
synapps_path('modules/home');
template_assets('images', 'logo.png');

// Routing helpers
is_route('home.index', 'active');
is_route_prefix('admin', 'active');

// Number helpers
is_decimal(10.5); // true
clean_number_format(1234.56, 2, '.', ','); // "1,234.56"

// Array helpers
recuresive_array_merge(['a' => 1], ['b' => 2]);
array_is_list([1, 2, 3]); // true
```

#### Testing WithDeleteToken Trait
```php
use App\Models\Post;

$post = Post::find(1);
$token = $post->delete_token;

// Validate token
$id = Post::validateDeleteToken($token);
// Returns post ID or false
```

### 3. database-query - Read Database

Query Synapse database tables directly.

**Examples:**

#### Query Configuration
```sql
-- View all configurations
SELECT * FROM configs;

-- Find specific config
SELECT * FROM configs WHERE `group` = 'app' AND `key` = 'site_name';

-- List all config groups
SELECT DISTINCT `group` FROM configs;
```

#### Query Translations
```sql
-- View all translations
SELECT * FROM languages;

-- Find translation by language
SELECT * FROM languages WHERE language_code = 'en';

-- Find specific translation
SELECT * FROM languages WHERE `key` = 'welcome.title';

-- Count translations per language
SELECT language_code, COUNT(*) as total
FROM languages
GROUP BY language_code;
```

#### Module-Specific Tables
```sql
-- Example: If you have a module with custom tables
-- Replace with your actual module tables
SELECT * FROM module_specific_table;
```

### 4. search-docs - Search Documentation

Search Laravel ecosystem documentation including Synapse-specific docs.

**Usage:**
```bash
# Ask Claude to search for specific topics
"How do I create a Synapse module?"
"Search docs for DbConfig service usage"
"Find information about Blade components in Synapse"
```

**Topics to search:**
- Module creation and structure
- Configuration management
- Database services (DbConfig, DbLang)
- Blade components
- Helper functions
- Testing with Pest
- Route registration
- Service providers

### 5. browser-logs - View Browser Errors

Read browser console logs and errors when developing with Livewire.

**Usage:**
Useful when debugging:
- Livewire component errors
- Alpine.js issues
- JavaScript errors in Synapse components
- Blade component rendering issues

### 6. get-absolute-url - URL Generation

Get correct URLs with proper scheme, domain, and port.

**Usage:**
```bash
# Claude automatically uses this when sharing URLs
"What's the URL for the admin dashboard?"
"Share the login page URL"
```

## Best Practices

### 1. Use tinker for Quick Testing

Instead of writing test files for quick experiments:

```php
// Test new helper function
tinker: dbconf(['test.value' => 'Hello World']);
tinker: dbconf('test.value');
```

### 2. Use database-query for Data Inspection

Quick data inspection without writing queries:

```sql
-- Check if configs are being cached correctly
SELECT * FROM configs ORDER BY updated_at DESC LIMIT 10;

-- Verify multi-language setup
SELECT DISTINCT language_code FROM languages;
```

### 3. Use search-docs Before Implementing

Always search documentation first:

```bash
"How do I add a new Blade component to Synapse?"
"What's the module.json schema?"
```

### 4. Debugging Workflow

1. **Check browser-logs** for frontend errors
2. **Use tinker** to test backend logic
3. **Use database-query** to verify data state
4. **Use search-docs** to find solutions

## Synapse-Specific Workflows

### Creating a New Module

```bash
# 1. Search for module creation docs
"How do I create a module in Synapse?"

# 2. Use tinker to verify namespace
tinker: SynapseUtils::listModuleNamespaces(config('synapse.module_namespaces'), fn($p) => true);

# 3. After creating module, discover it
php artisan synapps:discover

# 4. Verify in database
database-query: SELECT * FROM configs WHERE `group` = 'modules';
```

### Testing DbConfig Service

```bash
# 1. Set config via tinker
tinker: DbConfig::set('app', 'feature_enabled', true, 'Feature Flag');

# 2. Verify in database
database-query: SELECT * FROM configs WHERE `group` = 'app' AND `key` = 'feature_enabled';

# 3. Test cache bypass
tinker: dbconf('!app.feature_enabled'); // Bypass cache

# 4. Test in browser and check logs
browser-logs
```

### Debugging Livewire Components

```bash
# 1. Check browser console for errors
browser-logs

# 2. Test component in tinker
tinker: Livewire::test(ComponentName::class)->call('methodName')->assertSee('expected');

# 3. Verify database state
database-query: SELECT * FROM relevant_table;

# 4. Search docs if needed
"How do Livewire components work with Synapse modules?"
```

## Integration with Claude Code

When using Claude Code with Laravel Boost:

1. **Ask natural questions**: "What Synapse commands are available?"
2. **Request specific queries**: "Show me all configs in the database"
3. **Debug issues**: "Check browser logs for errors"
4. **Search efficiently**: "Find documentation about module routing"

Claude Code automatically:
- Uses `search-docs` for Laravel/Synapse documentation
- Uses `tinker` for code execution and testing
- Uses `database-query` for data inspection
- Uses `list-artisan-commands` when discussing commands
- Uses `get-absolute-url` when sharing URLs

## Tips

1. **Prefer tinker over temporary scripts**: Quick testing is faster in tinker
2. **Use database-query for read-only inspection**: Don't modify data directly
3. **Search docs first**: Save time by finding existing solutions
4. **Check browser logs**: Essential for Livewire/Alpine.js debugging
5. **Use list-artisan-commands**: Discover available Synapse commands

## Related Documentation

- [Commands Reference](COMMANDS.md)
- [Services Documentation](SERVICES.md)
- [Helper Functions](HELPERS.md)
- [Testing Guide](../TESTING.md)
- [Module Creation](MODULES.md)

## Additional Resources

- [Laravel Boost Documentation](https://github.com/laravel/mcp)
- [MCP Protocol](https://modelcontextprotocol.io/)
- [Claude Code Documentation](https://claude.com/claude-code)
