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

// Flush the in-memory cache and re-load the current language from the database
DbLang::reload();
```

> `reload()` is for long-running processes (Octane workers, queue jobs) that need to pick up translation changes made mid-process, since the in-memory cache would otherwise persist stale values for the process lifetime.

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

## API Request Logging

Opt-in, per-route-group middleware (`api.log` / `LogApiRequest`) that records every API request/response into an `api_request_logs` table — method, URL, status code, actor, IP, user agent, duration, and redacted request/response bodies. It is **never applied globally**; attach it explicitly to the route groups you want audited.

### Attaching the Middleware

```php
Route::middleware(['auth:sanctum', 'api.auth', 'api.token.expiry', 'throttle:api', 'api.log'])
    ->group(function () {
        // audited routes
    });
```

Logging failures are caught internally and logged via `Log::error()` — they never break the actual API response.

### What Gets Redacted

`SensitiveDataRedactor` recursively walks the request headers, request body, and (for JSON responses) the response body:

- Keys matching `config('synapps.apps.logging.sensitive_keys')` (case-insensitive) are replaced with a self-describing `[REDACTED:{key}]` placeholder (array values keep their shape, e.g. single-value header arrays)
- `UploadedFile` instances are replaced with `[FILE:{field}:{original_name}]` so multipart uploads don't break JSON serialization
- If the redacted, JSON-encoded payload exceeds `config('synapps.apps.logging.max_body_size')` (default 65536 bytes), it's replaced with a truncated envelope: `{ "_truncated": true, "original_size": ..., "preview": "..." }`

See [Security & Logging Configuration](CONFIGURATION.md#logging) to customize `sensitive_keys` / `max_body_size`.

### ApiRequestLog Model

```php
use VmEngine\Synapse\Models\ApiRequestLog;

ApiRequestLog::latest()->where('status_code', '>=', 500)->get();
```

| Column | Type | Description |
|--------|------|-------------|
| `method` | string | HTTP method |
| `url` | string | Full request URL |
| `status_code` | int | Response HTTP status code |
| `actor_type` | `ApiLogActorType` | `guest` \| `user` \| `api_user` |
| `actor_id` | int, nullable | Authenticated user/API user ID |
| `actor_name` | string, nullable | Authenticated user/API user name |
| `ip_address` | string, nullable | Client IP |
| `user_agent` | string, nullable | Client user agent |
| `duration_ms` | int | Request handling duration |
| `request_headers` | array, nullable | Redacted request headers |
| `request_body` | array, nullable | Redacted request body |
| `response_body` | array, nullable | Redacted response body (JSON responses only) |
| `created_at` | timestamp | No `updated_at` — logs are write-once |

`ApiLogActorType` is resolved from the authenticated user: `Guest` if unauthenticated, `ApiUser` if `$user->is_api_user` is truthy, otherwise `User`.

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

## Excel Import/Export Service

Memory-efficient Excel import/export utility classes with support for background processing, progress tracking, resumability, and streaming.

**Libraries:** `openspout/openspout` ^4.0 (streaming, constant memory), `phpoffice/phpspreadsheet` ^2.0 (template-based exports)

### Importing Files

```php
use VmEngine\Synapse\Services\Excel\ExcelImporter;

// Sync import - returns all rows
$rows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)                          // skip header row
    ->mapColumns(['name', 'email', 'phone'])  // positional mapping
    ->import();                               // Returns Collection

// Associative mapping (explicit column index)
$rows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name' => 0, 'email' => 2, 'phone' => 4])
    ->import();

// Closure mapping (custom transformation)
$rows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(fn(array $row, int $i) => [
        'full_name' => $row[0] . ' ' . $row[1],
        'email' => strtolower($row[2]),
    ])
    ->import();

// Chunked import (memory efficient for large files)
$totalRows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email'])
    ->chunk(1000, function (Collection $rows, int $chunkIndex) {
        User::insert($rows->toArray());
    });
```

### Exporting Files

```php
use VmEngine\Synapse\Services\Excel\ExcelExporter;

// Sync export - simple columns
ExcelExporter::make(User::query()->where('active', true))
    ->columns(['name', 'email', 'created_at'])
    ->export('/path/to/output.xlsx');

// Associative columns with closures
ExcelExporter::make(User::query()->with('department'))
    ->columns([
        'Full Name' => 'name',
        'Email' => 'email',
        'Department' => fn($user) => $user->department?->name ?? 'N/A',
        'Joined' => fn($user) => $user->created_at->format('Y-m-d'),
    ])
    ->sheet('Users Report')
    ->export('/path/to/output.xlsx');

// Template export (preserves formatting)
ExcelExporter::make(Invoice::query())
    ->columns(['invoice_number', 'client_name', 'total'])
    ->startingRow(5)
    ->template('/path/to/template.xlsx')
    ->export('/path/to/output.xlsx');

// Styled export with column widths and formatting
ExcelExporter::make(User::query())
    ->columns([
        'Full Name' => 'name',
        'Email' => 'email',
        'Salary' => 'salary',
    ])
    ->columnStyles([
        'Full Name' => ['font' => ['bold' => true], 'width' => 30],
        'Email' => ['width' => 40, 'alignment' => ['horizontal' => 'center']],
        'Salary' => [
            'numberFormat' => ['formatCode' => '#,##0.00'],
            'alignment' => ['horizontal' => 'right'],
            'width' => 15,
        ],
    ])
    ->headerStyle([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
    ])
    ->export('/path/to/output.xlsx');

// Download response
return ExcelExporter::make(User::query())
    ->columns(['name', 'email'])
    ->download('users-export.xlsx');
```

### Column Styling

Apply professional formatting to exports using `columnStyles()` and `headerStyle()`. Styles are PhpSpreadsheet-compatible arrays:

```php
->columnStyles([
    'Column Label' => [
        'width' => 30,                                    // Column width
        'autoSize' => true,                               // Auto-fit width
        'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
        'alignment' => ['horizontal' => 'center'],
        'numberFormat' => ['formatCode' => '#,##0.00'],   // Number/date formatting
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EEEEEE']],
    ],
])
->headerStyle([
    'font' => ['bold' => true],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
])
```

When styles are set, the header row is automatically frozen. Styles work with both sync and background exports. See [docs/EXCEL.md](EXCEL.md#column-styling) for complete style reference.

### Background Processing

For large files, dispatch imports/exports to the queue:

```php
use VmEngine\Synapse\Services\Excel\Contracts\ProcessesImport;
use VmEngine\Synapse\Services\Excel\Contracts\ExportQuery;

// Background import - requires ProcessesImport implementation
class UserImportProcessor implements ProcessesImport
{
    public function process(Collection $rows, int $chunkIndex): void
    {
        DB::transaction(fn() => User::insert($rows->toArray()));
    }
}

$importTask = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email'])
    ->forUser(auth()->id())
    ->onQueue('imports')
    ->dispatch(UserImportProcessor::class, chunkSize: 1000);
// Returns ExcelImport model

// Background export - requires ExportQuery implementation
class ActiveUsersExport implements ExportQuery
{
    public function __invoke(): EloquentBuilder
    {
        return User::query()->where('active', true);
    }
}

$exportTask = ExcelExporter::make(ActiveUsersExport::class)
    ->columns(['Name' => 'name', 'Email' => 'email'])
    ->forUser(auth()->id())
    ->onQueue('exports')
    ->dispatch('/path/to/output.xlsx');
// Returns ExcelExport model

// Background export with user-defined filters
class YearlyReportExport implements ExportQuery
{
    public function __construct(
        public readonly ?int $year = null,
        public readonly ?string $status = null,
    ) {}

    public function __invoke(): EloquentBuilder
    {
        return Report::query()
            ->when($this->year, fn($q) => $q->whereYear('created_at', $this->year))
            ->when($this->status, fn($q) => $q->where('status', $this->status));
    }
}

$exportTask = ExcelExporter::make(YearlyReportExport::class)
    ->columns(['Title' => 'title', 'Amount' => 'amount'])
    ->withFilters(['year' => 2025, 'status' => 'approved'])
    ->forUser(auth()->id())
    ->dispatch('/path/to/report.xlsx');
// Filters are passed to the ExportQuery constructor
```

### Resume Failed Tasks

```php
// Resume import from failed_at_row
ExcelImporter::resume($importTask);

// Resume export (re-runs from scratch - idempotent)
ExcelExporter::resume($exportTask);
```

### Reference Tracking

Assign a reference string to look up tasks across page refreshes:

```php
$task = ExcelExporter::make(InvoiceExport::class)
    ->columns(['Invoice #' => 'number', 'Total' => 'total'])
    ->reference('pm-invoice-export-42')
    ->forUser(auth()->id())
    ->dispatch('/path/to/output.xlsx');

// Later, look up the task by reference
$task = ExcelExport::findByReference('pm-invoice-export-42');
$tasks = ExcelExport::forReference('pm-invoice-export-42')->get();
```

### Dot Notation for Relationships

Resolve nested attributes via dotted paths:

```php
ExcelExporter::make(Invoice::query()->with('client.company'))
    ->columns([
        'Invoice #' => 'invoice_number',
        'Client' => 'client.name',
        'Company' => 'client.company.name',
    ])
    ->export('/path/to/output.xlsx');
```

### Column Transformers

Serializable value formatters that work in both sync and background exports:

```php
use VmEngine\Synapse\Services\Excel\Transformers\DateFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\BooleanTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\NumberFormatTransformer;

ExcelExporter::make($query)
    ->columns([
        'Name' => 'name',
        'Joined' => [
            'field' => 'created_at',
            'transformer' => DateFormatTransformer::class,
            'config' => ['format' => 'd/m/Y'],
        ],
        'Active' => [
            'field' => 'is_active',
            'transformer' => BooleanTransformer::class,
        ],
        'Salary' => [
            'field' => 'salary',
            'transformer' => NumberFormatTransformer::class,
            'config' => ['decimals' => 0],
        ],
    ])
    ->export('/path/to/output.xlsx');
```

Built-in transformers: `DateFormatTransformer`, `NumberFormatTransformer`, `BooleanTransformer`, `DefaultValueTransformer`, `UpperCaseTransformer`.

See [EXCEL.md](EXCEL.md) for the full transformer API and custom transformer guide.

### Events

Listen for completion/failure in your consuming modules:

- `ExcelImportCompleted` - import task completed successfully
- `ExcelImportFailed` - import task failed (includes exception)
- `ExcelExportCompleted` - export task completed successfully
- `ExcelExportFailed` - export task failed (includes exception)

### Stale Task Detection

Tasks stuck in `processing` status (due to OOM, crash, etc.) are detected via heartbeat:

```bash
# Mark stale tasks as failed (default timeout: 3900 seconds)
php artisan synapse:excel-cleanup

# Custom timeout
php artisan synapse:excel-cleanup --timeout=7200

# Schedule cleanup
Schedule::command('synapse:excel-cleanup')->everyFiveMinutes();
```

### Database Tables

The `excel_imports` and `excel_exports` tables track task status, progress, and heartbeat. Run migrations after installation:

```bash
php artisan migrate
```

See [EXCEL.md](EXCEL.md) for complete API documentation including resume strategy, template exports, and background job details.

---

## Running Migrations

DbConfig, DbLang, and Excel services require database tables. Run migrations after installation:

```bash
php artisan migrate
```

This creates the `configs`, `languages`, `excel_imports`, and `excel_exports` tables automatically.

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

## Interactive Testing with Tinker

You can test all Synapse services interactively using Laravel tinker and Laravel Boost's `tinker` tool:

### DbConfig Service
```php
// Test getting and setting configs
tinker: dbconf(['app.site_name' => 'My Laravel App']);
tinker: dbconf('app.site_name');
tinker: dbconf('!app.site_name'); // Bypass cache

// Using facade
tinker: use VmEngine\Synapse\Facades\DbConfig;
tinker: DbConfig::set('app', 'maintenance_mode', true, 'Maintenance Mode');
tinker: DbConfig::get('app', 'maintenance_mode', false);
```

### DbLang Service
```php
// Test translations
tinker: ___('welcome.title');
tinker: ___('welcome.title', 'id');

// Using facade
tinker: use VmEngine\Synapse\Facades\DbLang;
tinker: DbLang::set('welcome.title', 'Welcome!', 'Welcome Title');
tinker: DbLang::setLanguage('id');
tinker: DbLang::getLanguage();
```

### Breadcrumbs Service
```php
// Test breadcrumbs
tinker: use VmEngine\Synapse\Services\Helper\Breadcrumbs;
tinker: $breadcrumbs = Breadcrumbs::make('Home', '/')->add('Users', '/users');
tinker: $breadcrumbs->items();
tinker: $breadcrumbs->count();
```

For more Laravel Boost integration examples, see the [Laravel Boost Integration Guide](LARAVEL_BOOST.md).

## Database Queries

You can also inspect service data using Laravel Boost's `database-query` tool:

```sql
-- View all configurations
SELECT * FROM configs;

-- Find specific config
SELECT * FROM configs WHERE `group` = 'app' AND `key` = 'site_name';

-- View all translations
SELECT * FROM languages;

-- Find translation by language
SELECT * FROM languages WHERE language_code = 'en' AND `key` = 'welcome.title';
```
