# Installation Guide

## Requirements

- PHP 8.2+
- Laravel 12.x|13.x
- Livewire ^4.0 (installed transitively as a dependency of this package)
- Composer
- Node.js & NPM

## Installation

Since this is a private package, you need to manually add it to your `composer.json`:

### 1. Add Repository to composer.json

Add the private git repository to your `composer.json` **before** requiring the package — Composer needs to know where to fetch it from, since it isn't published on Packagist:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "ssh://git@repo.4visionmedia.com:40288/vm-engine/synapse.git"
        }
    ]
}
```

### 2. Require the Package

Add the package to your requirements:

```json
{
    "require": {
        "vm-engine/synapse": "^2.4"
    }
}
```

### 3. Install via Composer

Run composer install:

```bash
composer install
```

> **Note**: You may need to configure your Git credentials to access the private repository.

## Setup Guide

Follow these steps to set up Synapse in your Laravel project:

### 1. Initialize SynApps Directory

**Option A: New Project Setup (Recommended)**

Run the interactive setup wizard:

```bash
php artisan synapps:setup
```

This command will:
1. **Prompt you for project information:**
   - App code (snake-case identifier)
   - App name
   - Author information
   - Repository URL
   - Domain URLs (dev/prod)
   - URL prefixes (frontend/API)
   - Application title and description

2. **Copy the SynApps stub directory** to `synapps/`

3. **Generate configuration file** at `synapps/config/apps.json`

4. **Automatically run these setup commands:**
   - `vendor:publish --tag=synapse-config --force` - Publish Synapse config files
   - `synapps:config` - Regenerate configuration files
   - `synapps:link-errors` - Create symlink for error views
   - `synapps:patch` - Patch JS/CSS imports, comment default seeder, and configure testing infrastructure
   - `synapps:discover` - Discover modules and update dependencies

**Option B: Existing SynApps Repository**

If you have an existing SynApps repository, clone it to the `synapps/` folder:

```bash
git clone <your-synapps-repo-url> synapps
```

Then manually run the setup commands:

```bash
php artisan vendor:publish --tag="synapse-config" --force
php artisan synapps:config
php artisan synapps:link-errors
php artisan synapps:patch
php artisan synapps:discover
```

> **Skip this step** if your Laravel project is already a Synapse project.

### 2. Run Database Migrations

If you're using the example module or have module migrations:

```bash
php artisan migrate
```

This creates database tables defined in module migrations.

### 3. Compile Assets

Build your frontend assets:

```bash
npm run build
```

For development with hot reload:

```bash
npm run dev
```

## What's Next?

- Learn about [Artisan Commands](COMMANDS.md)
- Explore [Blade Components](COMPONENTS.md)
- Understand [Database Services](SERVICES.md)
- Create your first [Module](MODULES.md)
