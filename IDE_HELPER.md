# IDE Helper Generator - Complete Documentation

> **Universal IDE Helper Generator for vm-engine/synapse**
> Generates PHPDoc annotations for better IDE autocompletion by analyzing facades, models, and helper functions.

## Table of Contents

1. [Quick Start](#quick-start)
2. [What's New in V2](#whats-new-in-v2)
3. [Basic Usage](#basic-usage)
4. [Command Line Options](#command-line-options)
5. [Default Scan Paths](#default-scan-paths)
6. [Output Location](#output-location)
7. [Usage Examples](#usage-examples)
8. [Before vs After Comparison](#before-vs-after-comparison)
9. [NPM/Composer Integration](#npmcomposer-integration)
10. [Advanced Usage](#advanced-usage)
11. [Migration Guide](#migration-guide)
12. [Troubleshooting](#troubleshooting)
13. [FAQs](#faqs)

---

## Quick Start

### For Standard Laravel Apps

```bash
# 1. Navigate to Laravel root
cd /path/to/laravel

# 2. Run the generator
php packages/synapse/generate-ide-helper.php

# 3. Add to .gitignore
echo "_ide_helper.php" >> .gitignore

# Done! ✓
```

**What it does:**
- ✅ Scans `packages/synapse/src` for package models
- ✅ Scans `app/Models` for application models (automatic!)
- ✅ Scans Laravel framework auth models
- ✅ Outputs to `_ide_helper.php` in Laravel root

### For Modular Laravel Apps

```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/users/Models
```

---

## What's New in V2

### ✨ Major Improvements

#### 1. Output Location Changed to Current Working Directory

**Before V1:**
- File always created in script directory (`packages/synapse/_ide_helper.php`)
- Required manual copying to Laravel root
- Confusing and error-prone

**After V2:**
- File created in current working directory (where you run the command)
- No file moving needed
- Works like standard CLI tools

**Impact:**
```bash
# V1 (old)
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
# Output: packages/synapse/_ide_helper.php ❌
# Then: cp packages/synapse/_ide_helper.php _ide_helper.php

# V2 (new)
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
# Output: /path/to/laravel/_ide_helper.php ✓
```

#### 2. Added `app/Models` to Default Scan Paths

**Before V1:**
- Only scanned `./src` and Laravel framework auth models
- Required manual `--scan-models=../../app/Models`

**After V2:**
- Automatically scans `./app/Models`
- Works out of the box for Laravel apps
- One less thing to configure

**Default Paths Now:**
```
./src                                    (package models)
./app/Models                             (Laravel app models - NEW!)
./vendor/laravel/framework/.../Auth      (Laravel auth models)
```

#### 3. Improved Warning Logic

- Only shows warnings for explicitly provided paths
- Silently skips missing default paths (no noise)

### Benefits of V2

| Aspect | V1 (Old) | V2 (New) |
|--------|----------|----------|
| **Output Location** | Script directory | Current directory ✓ |
| **app/Models Scan** | Manual | Automatic ✓ |
| **File Moving** | Required | Not needed ✓ |
| **NPM Script** | Complex with `cd` | Simple one-liner ✓ |
| **Intuitive** | ❌ | ✅ |

---

## Basic Usage

### Show Help

```bash
php generate-ide-helper.php --help
```

### Default Execution

```bash
# Run from Laravel root (recommended)
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
# Output: /path/to/laravel/_ide_helper.php
```

### Important: Always Run from Laravel Root

**✅ Recommended:**
```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
# Creates: /path/to/laravel/_ide_helper.php
```

**⚠️ Not recommended:**
```bash
cd /path/to/laravel/packages/synapse
php generate-ide-helper.php
# Creates: /path/to/laravel/packages/synapse/_ide_helper.php
```

---

## Command Line Options

### --scan-models

Scan additional directories for Eloquent models.

**Single path:**
```bash
php generate-ide-helper.php --scan-models=/path/to/models
```

**Multiple paths (comma-separated):**
```bash
php generate-ide-helper.php --scan-models=/path1,/path2,/path3
```

**Multiple paths (multiple flags):**
```bash
php generate-ide-helper.php \
  --scan-models=/path1 \
  --scan-models=/path2
```

**Relative paths:**
```bash
php generate-ide-helper.php --scan-models=synapps/modules/home/Models
```

### --scan-helpers

Scan additional helper files.

**Examples:**
```bash
# Single file
php generate-ide-helper.php --scan-helpers=app/helpers.php

# Multiple files (comma-separated)
php generate-ide-helper.php --scan-helpers=app/helpers.php,modules/helpers.php

# Multiple files (multiple flags)
php generate-ide-helper.php \
  --scan-helpers=app/helpers.php \
  --scan-helpers=modules/custom_helpers.php
```

### --output

Specify custom output file location.

**Examples:**
```bash
# Relative path (to current directory)
php generate-ide-helper.php --output=custom_ide_helper.php

# Absolute path
php generate-ide-helper.php --output=/absolute/path/_ide_helper.php
```

### --help

Show help message with all available options.

```bash
php generate-ide-helper.php --help
```

### Options Cheat Sheet

| Option | Description | Example |
|--------|-------------|---------|
| `--scan-models=<path>` | Additional model directories | `--scan-models=synapps/modules/*/Models` |
| `--scan-helpers=<path>` | Additional helper files | `--scan-helpers=app/helpers.php` |
| `--output=<file>` | Output file location | `--output=_ide_helper.php` |
| `--help` | Show help message | `--help` |

---

## Default Scan Paths

When you run the script without options, it automatically scans:

### Models
- `./src` (package models)
- `./app/Models` (Laravel application models) **← NEW in V2!**
- `./vendor/laravel/framework/src/Illuminate/Foundation/Auth` (Laravel auth models)

### Helpers
- `./src/helper.php` (package helpers)

### Facades
- All Laravel core facades (App, Artisan, Auth, Blade, Cache, Config, DB, Event, File, Log, Mail, Queue, Route, Storage, View, etc.)
- Livewire (if installed)

---

## Output Location

### How It Works

The generated file is placed in the **current working directory** (where you run the command).

**Uses:** `getcwd()` instead of `__DIR__`
- `getcwd()` = current working directory
- `__DIR__` = script directory

### Examples

**Run from Laravel root:**
```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
# Output: /path/to/laravel/_ide_helper.php ✓
```

**Run from package directory:**
```bash
cd /path/to/laravel/packages/synapse
php generate-ide-helper.php
# Output: /path/to/laravel/packages/synapse/_ide_helper.php
```

**Use absolute path for control:**
```bash
cd /anywhere
php /path/to/packages/synapse/generate-ide-helper.php \
  --output=/path/to/laravel/_ide_helper.php
# Output: /path/to/laravel/_ide_helper.php ✓
```

---

## Usage Examples

### Example 1: Standard Laravel App

```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
```

**Scans:**
- Package models in `packages/synapse/src`
- App models in `app/Models` ✓
- Laravel auth models

**Output:** `/path/to/laravel/_ide_helper.php`

### Example 2: With Module Models

```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/users/Models \
  --scan-models=synapps/modules/products/Models
```

**Scans:**
- All default paths (src, app/Models, laravel/auth) ✓
- Home module models
- Users module models
- Products module models

### Example 3: With Helper Files

```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/*/Models \
  --scan-helpers=app/helpers.php \
  --scan-helpers=synapps/helpers/custom.php
```

### Example 4: Custom Output Location

```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --output=storage/_ide_helper.php
```

### Example 5: Everything Combined

```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models,synapps/modules/users/Models \
  --scan-helpers=app/helpers.php,modules/*/helpers.php \
  --output=_ide_helper.php
```

---

## Before vs After Comparison

### Standard Laravel App

#### ❌ Before V2
```bash
# Step 1: Run with complex options
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --scan-models=../../app/Models \
  --output=../../_ide_helper.php

# Step 2: File created in wrong location sometimes
# Step 3: Need to move file manually
cp packages/synapse/_ide_helper.php _ide_helper.php
rm packages/synapse/_ide_helper.php
```

#### ✅ After V2
```bash
# One simple command
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php

# Done! ✓
# app/Models scanned automatically ✓
# Output in correct location ✓
```

### Modular Laravel App

#### ❌ Before V2
```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --scan-models=../../app/Models \
  --scan-models=../../synapps/modules/home/Models \
  --scan-models=../../synapps/modules/users/Models \
  --output=../../_ide_helper.php

# Complex relative paths
# Manual app/Models specification
# Easy to make mistakes
```

#### ✅ After V2
```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/users/Models

# app/Models automatically included ✓
# Simpler paths ✓
# Output automatically correct ✓
```

### Side-by-Side Feature Comparison

| Feature | Before V2 | After V2 |
|---------|-----------|----------|
| **Output Location** | Script directory (`__DIR__`) | Current directory (`getcwd()`) |
| **app/Models Scan** | ❌ Manual (`--scan-models`) | ✅ Automatic |
| **Relative Paths** | From script location | From current directory |
| **Default Behavior** | Package only | Package + App |
| **File Moving** | ❌ Required | ✅ Not needed |
| **NPM Script** | Complex with `cd` | Simple one-liner |
| **Intuitive** | ❌ | ✅ |
| **Laravel Standard** | ❌ | ✅ |

---

## NPM/Composer Integration

### NPM Script (Recommended)

**Add to `package.json`:**
```json
{
  "scripts": {
    "ide-helper": "php packages/synapse/generate-ide-helper.php"
  }
}
```

**Run:**
```bash
npm run ide-helper
```

**With custom paths:**
```json
{
  "scripts": {
    "ide-helper": "php packages/synapse/generate-ide-helper.php --scan-models=synapps/modules/*/Models"
  }
}
```

### Composer Script

**Add to `composer.json`:**
```json
{
  "scripts": {
    "ide-helper": "php packages/synapse/generate-ide-helper.php"
  }
}
```

**Run:**
```bash
composer ide-helper
```

### Before vs After NPM Scripts

**❌ V1 (Complex):**
```json
{
  "scripts": {
    "ide-helper": "cd packages/synapse && php generate-ide-helper.php --scan-models=../../app/Models --output=../../_ide_helper.php"
  }
}
```

**✅ V2 (Simple):**
```json
{
  "scripts": {
    "ide-helper": "php packages/synapse/generate-ide-helper.php"
  }
}
```

---

## Advanced Usage

### Real World Laravel Project Structure

```
laravel/
├── app/
│   └── Models/
│       ├── User.php
│       └── Post.php
├── packages/
│   └── synapse/
│       ├── src/
│       │   └── Models/
│       │       └── SynapseModel.php
│       └── generate-ide-helper.php
└── synapps/
    └── modules/
        ├── home/
        │   └── Models/
        │       └── HomeModel.php
        └── blog/
            └── Models/
                └── BlogPost.php
```

### Command for This Structure

**V1 (Old - Complex):**
```bash
php packages/synapse/generate-ide-helper.php \
  --scan-models=app/Models \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/blog/Models \
  --output=_ide_helper.php
```

**V2 (New - Simple):**
```bash
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/blog/Models
```

**What Changed:**
- ✅ `app/Models` scanned automatically
- ✅ `--output` not needed (defaults to current directory)
- ✅ Cleaner, shorter command

### Path Formats

**Absolute paths:**
```bash
# Unix/Linux/Mac
--scan-models=/var/www/app/Models

# Windows
--scan-models=C:/xampp/htdocs/app/Models
```

**Relative paths (from current directory):**
```bash
--scan-models=app/Models
--scan-models=synapps/modules/*/Models
--scan-models=../other-project/Models
```

**Comma-separated:**
```bash
--scan-models=path1,path2,path3
```

**Multiple flags:**
```bash
--scan-models=path1 --scan-models=path2 --scan-models=path3
```

### Generated Output

The script generates `_ide_helper.php` containing:

**1. Facade Annotations:**
```php
/**
 * @see \Illuminate\Cache\CacheManager
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool put(string $key, mixed $value, int|null $ttl = null)
 */
class Cache extends \Illuminate\Support\Facades\Facade {}
```

**2. Model Annotations:**
```php
/**
 * User Model
 * @see App\Models\User
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static User|null find($id, $columns = ['*'])
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends \Illuminate\Database\Eloquent\Model {}
```

**3. Helper Function Annotations:**
```php
/**
 * synapps_path helper function
 * @param string $path
 * @return mixed
 */
function synapps_path($path = '') {}
```

---

## Migration Guide

### From V1 to V2

#### Step 1: Update NPM/Composer Scripts

**Old (`package.json`):**
```json
{
  "scripts": {
    "ide-helper": "cd packages/synapse && php generate-ide-helper.php --scan-models=../../app/Models --output=../../_ide_helper.php"
  }
}
```

**New (`package.json`):**
```json
{
  "scripts": {
    "ide-helper": "php packages/synapse/generate-ide-helper.php"
  }
}
```

#### Step 2: Clean Up Old Files

```bash
# Remove old helper files
rm packages/synapse/_ide_helper.php 2>/dev/null || true
rm _ide_helper.php 2>/dev/null || true

# Generate new
npm run ide-helper

# Verify location
ls -la _ide_helper.php  # Should be in Laravel root ✓
```

#### Step 3: Update Team Documentation

Tell your team:
- ✅ Run from Laravel root directory
- ✅ `app/Models` is now scanned automatically
- ✅ Output goes to current directory
- ✅ Simpler command required

#### Step 4: Update .gitignore

Make sure `.gitignore` includes:
```gitignore
/_ide_helper.php
_ide_helper.php
```

### If You Need Old Behavior

Use `--output` with absolute path:
```bash
cd /anywhere
php /path/to/packages/synapse/generate-ide-helper.php \
  --output=/path/to/packages/synapse/_ide_helper.php
```

---

## Troubleshooting

### Issue: Path Not Found Warning

```
⚠ Path not found: /some/path/Models
```

**Solution:**
- Check that the path exists and is correct
- Use absolute paths or correct relative paths (from current directory)
- Remember: warnings only show for explicitly provided paths, not default paths

### Issue: No Models Discovered

**Solution:**
Ensure your models extend `Model`:
```php
// ✅ Correct - will be discovered
class User extends Model {}

// ❌ Incorrect - won't be discovered
class User {}
```

### Issue: No Helpers Discovered

**Solution:**
Ensure helpers use standard `function` syntax:
```php
// ✅ Correct - will be discovered
function my_helper($param) {}

// ❌ Incorrect - won't be discovered
$my_helper = function($param) {};
```

### Issue: File Created in Wrong Location

**Problem:** File created in package directory instead of Laravel root.

**Solution:**
Make sure you run from Laravel root:
```bash
# ✅ Correct
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php

# ❌ Wrong
cd /path/to/laravel/packages/synapse
php generate-ide-helper.php
```

### Issue: app/Models Not Being Scanned

**Check:**
1. Run from correct directory (Laravel root)
2. Verify path exists: `ls -la app/Models`
3. Check output for: `→ Scanning: .../app/Models`

If `app/Models` doesn't exist, it's silently skipped (no warning). This is normal!

---

## FAQs

### Q: Where does the file go now?
**A:** In the directory where you run the command (current working directory).

### Q: I liked the old behavior, can I get it back?
**A:** Yes! Either:
- Run from the package directory: `cd packages/synapse && php generate-ide-helper.php`
- Use `--output` with absolute path: `--output=/absolute/path/_ide_helper.php`

### Q: Do I still need to specify --scan-models for app/Models?
**A:** No! It's now scanned automatically in V2.

### Q: What if app/Models doesn't exist?
**A:** No problem - it's silently skipped (no warnings for default paths).

### Q: Can I still scan additional paths?
**A:** Yes! Use `--scan-models=/additional/path` as before.

### Q: How do I scan all module models?
**A:** Use shell expansion or specify each:
```bash
# Option 1: Specify each module
php generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/users/Models

# Option 2: Use comma-separated
php generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models,synapps/modules/users/Models
```

### Q: Can I use this in CI/CD?
**A:** Yes! Example GitHub Actions workflow:
```yaml
- name: Generate IDE Helper
  run: |
    cd /path/to/laravel
    php packages/synapse/generate-ide-helper.php
    git add _ide_helper.php
    git commit -m "Update IDE helper" || exit 0
```

### Q: Does it work with PhpStorm/VS Code/etc.?
**A:** Yes! The `_ide_helper.php` file is recognized by:
- PhpStorm/IntelliJ IDEA (automatically)
- VS Code with PHP Intelephense
- Other IDEs with PHP support

---

## Best Practices

### 1. Always Run from Laravel Root

```bash
# ✅ Good
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php

# ❌ Avoid
cd /path/to/laravel/packages/synapse
php generate-ide-helper.php
```

### 2. Use NPM/Composer Scripts

```json
{
  "scripts": {
    "ide-helper": "php packages/synapse/generate-ide-helper.php"
  }
}
```

Then: `npm run ide-helper`

### 3. Add to .gitignore

```gitignore
/_ide_helper.php
_ide_helper.php
```

### 4. Regenerate After Changes

Regenerate when you:
- Add new models
- Add new helper functions
- Add new modules
- Update method signatures

### 5. Use Relative Paths for Team Consistency

```bash
# ✅ Good - works for all team members
php generate-ide-helper.php --scan-models=synapps/modules/home/Models

# ❌ Avoid - paths vary between environments
php generate-ide-helper.php --scan-models=/Users/john/projects/app/Models
```

---

## Summary

### What V2 Brings

**Changes:**
1. ✨ Output file placed in current working directory
2. ✨ `app/Models` added to default scan paths
3. ✨ Improved warning logic (only for explicit paths)
4. ✨ Better help documentation

**Benefits:**
- ✅ Simpler for standard Laravel apps
- ✅ More intuitive behavior
- ✅ Less configuration needed
- ✅ Cleaner integration with build tools
- ✅ Works like standard CLI tools

### One Command to Rule Them All

For most Laravel apps, this is all you need:

```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
```

**That's it!** 🎉

---

## Quick Reference

```bash
# Basic usage (recommended)
php packages/synapse/generate-ide-helper.php

# With module scanning
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/*/Models

# With helpers
php packages/synapse/generate-ide-helper.php \
  --scan-helpers=app/helpers.php

# Custom output
php packages/synapse/generate-ide-helper.php \
  --output=/custom/path/_ide_helper.php

# Show help
php packages/synapse/generate-ide-helper.php --help
```

---

**Documentation Version:** 2.0
**Last Updated:** 2025-10-19
**Package:** vm-engine/synapse
