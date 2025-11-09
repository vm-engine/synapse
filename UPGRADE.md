# Upgrade Guide

This guide outlines the upgrade process for major and minor versions of Synapse.

## Table of Contents

- [Versioning Policy](#versioning-policy)
- [Upgrade Process](#upgrade-process)
- [Version-Specific Guides](#version-specific-guides)

---

## Versioning Policy

Synapse follows [Semantic Versioning 2.0.0](https://semver.org/):

### Version Format: MAJOR.MINOR.PATCH

- **MAJOR** (1.x.x) - Breaking changes, incompatible API modifications
- **MINOR** (x.1.x) - New features, backward compatible additions
- **PATCH** (x.x.1) - Bug fixes, backward compatible fixes

### Stability Commitment

#### v1.x - Stable Branch

Once v1.0.0 is released:

✅ **Guaranteed Stability**
- No breaking changes within v1.x series
- All new features will be backward compatible
- Deprecation warnings before removal (min 2 minor versions)

⚠️ **Deprecation Process**
1. Feature marked as deprecated in version X.Y.0
2. Deprecation warning displayed in logs
3. Feature removed in version X.(Y+2).0 at earliest

📅 **Support Timeline**
- **Active Support:** Latest MAJOR version (e.g., v1.x)
- **Security Fixes:** Latest MAJOR + previous MAJOR (e.g., v1.x and v0.x)
- **Support Duration:** 18 months after new MAJOR release

#### Pre-1.0 (Beta/Alpha)

⚠️ **No Stability Guarantee**
- Breaking changes may occur between minor versions
- API may change without notice
- Use in production at your own risk

---

## Upgrade Process

### General Upgrade Steps

1. **Review the CHANGELOG**
   ```bash
   # Check what changed in the new version
   cat vendor/vm-engine/synapse/CHANGELOG.md
   ```

2. **Check Breaking Changes**
   - Read the "Breaking Changes" section in CHANGELOG
   - Review deprecated features you might be using

3. **Update Composer**
   ```bash
   # For specific version
   composer require vm-engine/synapse:^2.0

   # Or update to latest
   composer update vm-engine/synapse
   ```

4. **Run Discovery**
   ```bash
   php artisan synapps:discover
   php artisan config:cache
   ```

5. **Test Your Application**
   ```bash
   composer test
   php artisan test
   ```

6. **Review Module Compatibility**
   - Check if your custom modules need updates
   - Verify all module service providers still work

---

## Version-Specific Guides

### Upgrading to v1.0.0 (From Beta)

#### Breaking Changes

**None** - v1.0.0 is the first stable release.

#### New Features

All features from beta are now stable. See [CHANGELOG.md](CHANGELOG.md) for full list.

#### Required Actions

1. **Update composer.json:**
   ```json
   {
       "require": {
           "vm-engine/synapse": "^1.0"
       }
   }
   ```

2. **Run setup commands:**
   ```bash
   composer update vm-engine/synapse
   php artisan synapps:discover
   php artisan config:cache
   ```

3. **Update module.json files** (if using old format):
   - Ensure all modules have proper `id_case` field
   - Verify `load_priority` is set (default: 99)

4. **Test module loading:**
   ```bash
   php artisan route:list  # Verify all module routes loaded
   php artisan config:show synapps  # Verify config compiled
   ```

#### Recommendations

- ✅ Review and update `.gitignore` to exclude compiled configs
- ✅ Set up testing with `synapps:patch-tests`
- ✅ Configure error views with `synapps:link-errors`

---

### Upgrading to v2.0.0 (Future)

_This section will be updated when v2.0.0 is released._

---

## Deprecation Warnings

Synapse uses Laravel's logging system to warn about deprecated features:

```php
// Example deprecation warning in logs
[WARNING] The 'old_method()' is deprecated and will be removed in v1.5.0.
          Use 'new_method()' instead.
```

**How to check for deprecations:**

1. **Review Laravel logs:**
   ```bash
   php artisan pail --filter=deprecated
   ```

2. **Check your code:**
   ```bash
   grep -r "deprecated" storage/logs/
   ```

3. **Update before removal:**
   - Replace deprecated methods
   - Test thoroughly
   - Deploy before the removal version

---

## Breaking Changes Policy

### What Constitutes a Breaking Change?

**Breaking changes** require a MAJOR version bump:

❌ **Breaking:**
- Removing public methods or properties
- Changing method signatures (parameters, return types)
- Removing Blade components
- Changing configuration file structure
- Removing helper functions
- Changing command signatures

✅ **Not Breaking:**
- Adding new optional parameters with defaults
- Adding new methods or properties
- Adding new Blade components
- Deprecating features (with warnings)
- Internal refactoring
- Bug fixes that restore intended behavior

### Handling Breaking Changes

When breaking changes are necessary:

1. **Announce in advance** (min 3 months for major features)
2. **Provide migration path** in documentation
3. **Include automated migration tools** when possible
4. **Comprehensive changelog** with upgrade instructions

---

## Module Compatibility

### Module Version Constraints

When creating modules, specify compatible Synapse versions in `module.json`:

```json
{
    "id": "my-module",
    "name": "My Module",
    "dependencies": {
        "composer": {
            "main": {
                "vm-engine/synapse": "^1.0"
            }
        }
    }
}
```

### Compatibility Table

| Synapse Version | Laravel | Livewire | PHP |
|-----------------|---------|----------|-----|
| v1.0.x          | ^12.0   | ^3.0     | >=8.2 |

---

## Rollback Strategy

If you encounter issues after upgrading:

### 1. Rollback Composer

```bash
# Rollback to specific version
composer require vm-engine/synapse:1.0.0

# Rollback using composer.lock
git checkout HEAD~1 composer.lock
composer install
```

### 2. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 3. Revert Config Changes

```bash
# If you modified configs
git checkout HEAD~1 config/
php artisan config:cache
```

### 4. Report Issues

If rollback was necessary, please report:
- Version you upgraded from
- Version you upgraded to
- Error messages
- Steps to reproduce

---

## Getting Help

- **Documentation:** [README.md](README.md)
- **Issue Tracker:** Report bugs and request features
- **Support:** Contact VM Engine development team

---

## Contributing to Upgrades

Help improve the upgrade experience:

1. **Test pre-releases** and report issues
2. **Document edge cases** you encounter
3. **Suggest automated migrations** for breaking changes
4. **Improve this guide** with your experiences
