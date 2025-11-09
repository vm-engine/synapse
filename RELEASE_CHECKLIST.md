# Release Checklist

Pre-release checklist for Synapse package releases.

## v1.0.0-beta.1 Release Checklist

### Documentation ✅

- [x] **README.md** - Complete with setup guide and examples
- [x] **CHANGELOG.md** - Comprehensive version history
- [x] **UPGRADE.md** - Upgrade guide and versioning policy
- [x] **TESTING.md** - Testing documentation
- [x] **IDE_HELPER.md** - IDE helper generator docs
- [x] **CLAUDE.md** - Technical documentation for developers

### Code Quality ✅

- [x] **All tests passing** - 90/90 tests (141 assertions)
- [x] **Test coverage** - ~85% target coverage achieved
- [x] **No failing tests** - All critical paths tested
- [x] **Code style** - PSR-12 compliant (use `./vendor/bin/pint`)

### Package Configuration ✅

- [x] **composer.json** - Correct version, dependencies, and autoload
- [x] **.gitignore** - Proper exclusions for development files
- [x] **.gitattributes** - Export-ignore for release optimization
- [x] **Service providers** - Auto-discovery configured
- [x] **Publishable assets** - Config files tagged correctly

### Features Complete ✅

- [x] **Modular architecture** - Directory and package modules
- [x] **Configuration system** - Builder pattern with lifecycle
- [x] **Artisan commands** - All 5 commands working
- [x] **Blade components** - 13 components implemented
- [x] **Helper functions** - All 20+ helpers tested
- [x] **Service providers** - 4 providers registered

### Release Preparation 🔄

- [ ] **Version tagging** - Create git tag `v1.0.0-beta.1`
- [ ] **GitHub release** - Create release with changelog
- [ ] **Packagist update** - Ensure auto-update is working

### Post-Release ⏳

- [ ] **Production testing** - Test in real Laravel 12 application
- [ ] **Gather feedback** - Collect user reports and issues
- [ ] **Monitor issues** - Track bug reports
- [ ] **Plan v1.0.0** - Address any critical issues

---

## v1.0.0 Stable Release Checklist

### Prerequisites (Before Stable Release)

- [ ] **Beta testing period** - Min 2-4 weeks of beta usage
- [ ] **No critical bugs** - All blocker issues resolved
- [ ] **User feedback** - Incorporate major feedback items
- [ ] **Performance testing** - Verify no performance regressions
- [ ] **Security audit** - Review for common vulnerabilities

### Documentation Updates

- [ ] **README.md** - Update with stable version references
- [ ] **CHANGELOG.md** - Move unreleased items to v1.0.0
- [ ] **UPGRADE.md** - Add v1.0.0 upgrade instructions
- [ ] **Version policy** - Confirm stability guarantees

### Final Checks

- [ ] **All tests passing** - 100% pass rate required
- [ ] **Dependencies locked** - Verify Laravel 12.x compatibility
- [ ] **Breaking changes documented** - None for v1.0.0
- [ ] **Migration path clear** - Easy upgrade from beta

### Release Steps

- [ ] Update version in `composer.json` to `1.0.0`
- [ ] Update CHANGELOG.md with release date
- [ ] Create git tag `v1.0.0`
- [ ] Push tag to repository
- [ ] Create GitHub release with full changelog
- [ ] Announce on relevant channels

---

## Release Process Template

### 1. Pre-Release

```bash
# Ensure on correct branch
git checkout master
git pull origin master

# Run full test suite
composer test

# Verify code style
./vendor/bin/pint --test

# Update CHANGELOG.md
# - Move [Unreleased] items to new version section
# - Add release date
# - Add comparison links

# Update version in composer.json (if not using tags)

# Commit changes
git add CHANGELOG.md composer.json
git commit -m "chore: Prepare for vX.Y.Z release"
git push origin master
```

### 2. Create Tag

```bash
# Create annotated tag
git tag -a v1.0.0-beta.1 -m "Release v1.0.0-beta.1

See CHANGELOG.md for details."

# Push tag
git push origin v1.0.0-beta.1
```

### 3. Create GitHub Release

1. Go to repository releases page
2. Click "Draft a new release"
3. Select the tag you just created
4. Release title: `v1.0.0-beta.1`
5. Description: Copy from CHANGELOG.md
6. Check "This is a pre-release" (for beta/rc)
7. Publish release

### 4. Verify Packagist

- Check that Packagist auto-updated (if configured)
- Verify new version is available
- Test installation: `composer require vm-engine/synapse:^1.0.0-beta.1`

### 5. Post-Release

```bash
# Update CHANGELOG.md with [Unreleased] section
# Commit and push
git add CHANGELOG.md
git commit -m "chore: Start next development cycle"
git push origin master
```

---

## Version Numbering Guide

### When to Bump Each Number

**PATCH** (1.0.**X**)
- Bug fixes
- Documentation updates
- Performance improvements (no API changes)
- Internal refactoring

**MINOR** (1.**X**.0)
- New features (backward compatible)
- New Blade components
- New helper functions
- Deprecating features (with warnings)
- New artisan commands

**MAJOR** (**X**.0.0)
- Breaking API changes
- Removing deprecated features
- Changing method signatures
- Removing Blade components
- Laravel version upgrade (if breaking)

### Pre-Release Tags

- **alpha** (1.0.0-alpha.1) - Early development, unstable
- **beta** (1.0.0-beta.1) - Feature complete, testing
- **rc** (1.0.0-rc.1) - Release candidate, final testing

---

## Current Status

**Target Release:** v1.0.0-beta.1
**Status:** ✅ Ready for beta release
**Blockers:** None
**Next Steps:**
1. Create git tag
2. Create GitHub release
3. Start beta testing period

---

## Notes

- Always update CHANGELOG.md before tagging
- Use annotated tags (git tag -a) for releases
- Test installation on fresh Laravel project before release
- Coordinate with team before major releases
- Keep security patches separate from feature releases

---

## Quick Commands

```bash
# View current version
git describe --tags --abbrev=0

# View all tags
git tag -l

# Delete tag (if needed)
git tag -d v1.0.0-beta.1
git push origin :refs/tags/v1.0.0-beta.1

# Create and push tag
git tag -a v1.0.0-beta.1 -m "Release v1.0.0-beta.1"
git push origin v1.0.0-beta.1
```
