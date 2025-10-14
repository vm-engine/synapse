# Synapse Package Tests

This directory contains comprehensive tests for the vm-engine/synapse package using PestPHP.

## Test Structure

```
tests/
├── Feature/                    # Feature tests (integration tests)
│   └── Commands/              # Artisan command tests
│       └── SynAppsCommandsTest.php
├── Unit/                      # Unit tests
│   ├── Components/           # Blade component tests
│   │   └── BladeComponentsTest.php
│   ├── Services/             # Service class tests
│   │   ├── Helper/
│   │   │   └── BreadcrumbsTest.php
│   │   ├── ConfigGenerator/
│   │   │   ├── BuilderTest.php
│   │   │   └── BuilderBaseTest.php
│   │   └── SynapseUtilsTest.php
│   ├── HelperFunctionsTest.php    # Global helper function tests
│   └── ServiceProvidersTest.php   # Service provider tests
├── Pest.php                   # Pest configuration
├── TestCase.php              # Base test case class
└── README.md                 # This file
```

## Running Tests

### All Tests
```bash
composer test
# or
./vendor/bin/pest
```

### Specific Test Suite
```bash
# Unit tests only
./vendor/bin/pest tests/Unit

# Feature tests only
./vendor/bin/pest tests/Feature
```

### Specific Test File
```bash
./vendor/bin/pest tests/Unit/HelperFunctionsTest.php
```

### Specific Test
```bash
./vendor/bin/pest --filter="test_name"
```

### With Coverage
```bash
composer test-coverage
# or
./vendor/bin/pest --coverage
```

## Test Coverage

### Unit Tests

#### Helper Functions (`tests/Unit/HelperFunctionsTest.php`)
Tests for all global helper functions defined in `src/helper.php`:
- ✓ `synapps_path()` - Path generation
- ✓ `recuresive_array_merge()` - Recursive array merging
- ✓ `array_is_list()` - Array type checking
- ✓ `route_api_opt()` - API route option generation
- ✓ `route_web_opt()` - Web route option generation
- ✓ `pagination_format()` - Pagination data formatting
- ✓ `pagination_convert_link()` - Pagination link conversion
- ✓ `is_decimal()` - Decimal value detection
- ✓ `clean_number_format()` - Number formatting
- ✓ `template_assets()` - Template asset URL generation

#### Breadcrumbs Service (`tests/Unit/Services/Helper/BreadcrumbsTest.php`)
Tests for the `VmEngine\Synapse\Services\Helper\Breadcrumbs` class:
- ✓ Static factory method (`make()`)
- ✓ Adding breadcrumb items
- ✓ Method chaining
- ✓ Data storage and retrieval
- ✓ Item counting
- ✓ Icon handling
- ✓ Special character handling

#### SynapseUtils Service (`tests/Unit/Services/SynapseUtilsTest.php`)
Tests for the `VmEngine\Synapse\Services\SynapseUtils` class:
- ✓ `copyDirectory()` - Directory copying functionality
  - Simple directories
  - Nested structures
  - Trailing slashes
  - Empty directories
  - Multiple files
- ✓ `isSynAppsAutoloaded()` - Autoload detection
- ✓ `listModuleNamespaces()` - Module namespace listing
  - With/without prefix
  - Multiple prefixes
  - Snake case conversion
- ✓ `listModuleResources()` - Module resource scanning
  - Resource path filtering
  - Custom callbacks
  - Default path return

#### ConfigGenerator Builder (`tests/Unit/Services/ConfigGenerator/`)
Tests for the configuration builder system:

**BuilderBase** (`BuilderBaseTest.php`):
- ✓ Instantiation and synapps reference storage
- ✓ Lifecycle methods (init, preLoop, loop, postLoop, finalize)
- ✓ Default method implementations

**Builder** (`BuilderTest.php`):
- ✓ Main builder instantiation
- ✓ Property initialization (synappsPath, mainConfig, config, configHash)
- ✓ Config structure creation
- ✓ `generate()` method execution
- ✓ Config file loading

#### Blade Components (`tests/Unit/Components/BladeComponentsTest.php`)
Tests for all Blade components in `src/Components/`:
- ✓ **Checkboxes** - Multiple checkbox inputs
- ✓ **RadioButtons** - Radio button groups
- ✓ **Select** - Dropdown selects
- ✓ **MultiSelect** - Multi-selection dropdowns
- ✓ **Password** - Password input with toggle
- ✓ **Currency** - Currency input formatting
- ✓ **Datepicker** - Date picker with Flatpickr
- ✓ **ConfirmDialog** - Confirmation modals
- ✓ **Drawer** - Slide-out panels
- ✓ **Toastr** - Toast notifications

Each component test covers:
- Instantiation
- Property storage
- Default values
- Custom values
- View rendering

#### Service Providers (`tests/Unit/ServiceProvidersTest.php`)
Tests for all package service providers:
- ✓ **SynapseServiceProvider** - Main provider registration
  - Config publishing
  - Command registration
- ✓ **SynapseLangServiceProvider** - Language file management
- ✓ **SynapseRouteServiceProvider** - Route registration
- ✓ **SynapseViewServiceProvider** - View and component registration
  - Blade component aliases
- ✓ Package auto-discovery

### Feature Tests

#### Artisan Commands (`tests/Feature/Commands/SynAppsCommandsTest.php`)
Integration tests for package commands:
- ✓ `synapps:patch-resources` - Resource file patching
  - JS file patching
  - CSS file patching
  - Idempotent execution
  - Success status
- ⏭ `synapps:discover` - Module discovery (requires mocking)
- ⏭ `synapps:setup` - Interactive setup (requires prompt mocking)

## Test Scenarios Covered

### 1. Path and File Operations
- Path generation with various inputs
- Directory copying (simple, nested, empty)
- File existence checking
- Resource scanning

### 2. Array Operations
- Simple and nested array merging
- Duplicate value handling
- Array type detection
- Deep nested structures

### 3. Route and URL Management
- API route option generation
- Web route option generation
- Domain differences
- Path prefix handling
- URL parsing with ports

### 4. Pagination
- Pagination data formatting
- Page-to-offset conversion
- Query parameter preservation
- Edge cases (zero limit, first page)

### 5. Number Formatting
- Decimal detection
- Clean number formatting
- Custom separators
- Large numbers
- Negative numbers

### 6. Configuration Management
- Config structure initialization
- Config hash generation
- Builder lifecycle execution
- Sub-builder orchestration

### 7. Component Functionality
- Property binding
- Default values
- Custom configurations
- View rendering
- Method chaining (where applicable)

### 8. Service Provider Integration
- Provider registration
- Command availability
- Component registration
- Auto-discovery

## Writing New Tests

### Test Naming Convention
- Use descriptive names in natural language
- Start with action verbs (can, stores, handles, accepts, etc.)
- Example: `it('stores breadcrumb data correctly', function () { ... })`

### Test Organization
- Group related tests using `describe()` blocks
- Use `beforeEach()` for setup
- Use `afterEach()` for cleanup
- Keep tests focused on a single behavior

### Example Test Structure
```php
describe('ComponentName', function () {
    beforeEach(function () {
        // Setup code
        $this->component = new ComponentName();
    });

    afterEach(function () {
        // Cleanup code
    });

    it('does something specific', function () {
        // Test code
        expect($this->component->property)->toBe('expected');
    });
});
```

### Best Practices
1. **Isolation**: Each test should be independent
2. **Clarity**: Test one thing at a time
3. **Completeness**: Cover happy paths, edge cases, and errors
4. **Cleanup**: Always clean up created files/directories
5. **Fast**: Keep tests fast by mocking external dependencies

## Test Data

### TestCase Helper Methods
The base `TestCase` class provides helper methods:
- `createTestModule($name, $config)` - Create test module structure
- `cleanupTestModules()` - Remove test modules
- `deleteDirectory($dir)` - Recursively delete directories

### Using Test Helpers
```php
it('tests module functionality', function () {
    $modulePath = $this->createTestModule('test_module', [
        'version' => '1.0.0',
    ]);

    // Test code here

    $this->cleanupTestModules();
});
```

## Continuous Integration

These tests are designed to run in CI environments:
- No external dependencies required (except composer packages)
- Use in-memory SQLite database
- Fast execution
- Deterministic results

## Coverage Goals

Current coverage targets:
- Helper functions: 100%
- Services: 90%+
- Components: 90%+
- Commands: 70%+ (limited by interactive nature)
- Service Providers: 80%+

## Known Limitations

1. **Interactive Commands**: Commands using Laravel Prompts require mocking
2. **Composer/NPM Operations**: Package manager operations are skipped in tests
3. **File System**: Tests create temporary files that must be cleaned up
4. **Database**: Tests use in-memory SQLite, some features may differ from production

## Contributing

When adding new features to the package:
1. Write tests first (TDD approach recommended)
2. Ensure all tests pass
3. Aim for >80% code coverage
4. Update this README if adding new test files

## Troubleshooting

### Tests Failing on Fresh Install
Run `composer install` in the package directory first.

### Memory Issues
Increase PHP memory limit: `php -d memory_limit=512M vendor/bin/pest`

### Slow Tests
Use `--parallel` flag: `pest --parallel`

### File Permission Issues
Ensure test directories have write permissions.
