# Excel Import/Export Utility Classes for `vm-engine/synapse`

## Overview

Add memory-efficient Excel import/export utility classes to the synapse package with support for background processing, progress tracking, resumability, and system notifications.

**Library:** `openspout/openspout` ^4.0 (streaming, constant memory)
**Library:** `phpoffice/phpspreadsheet` ^2.0 (template-based exports)

---

## File Structure

```
src/
├── Services/Excel/
│   ├── ExcelImporter.php            # Import builder (main API)
│   ├── ExcelExporter.php            # Export builder (main API)
│   ├── Contracts/
│   │   ├── ReaderInterface.php      # Reader contract
│   │   ├── WriterInterface.php      # Writer contract
│   │   ├── ProcessesImport.php      # Import processor contract (for background jobs)
│   │   ├── ExportQuery.php          # Export query contract (for background jobs)
│   │   └── ColumnTransformer.php    # Column transformer contract
│   ├── Transformers/
│   │   ├── DateFormatTransformer.php
│   │   ├── NumberFormatTransformer.php
│   │   ├── BooleanTransformer.php
│   │   ├── DefaultValueTransformer.php
│   │   └── UpperCaseTransformer.php
│   ├── Readers/
│   │   └── SpoutReader.php          # OpenSpout streaming reader
│   ├── Writers/
│   │   ├── SpoutWriter.php          # OpenSpout streaming writer
│   │   ├── StyledWriter.php         # PhpSpreadsheet styled writer
│   │   └── TemplateWriter.php       # PhpSpreadsheet template writer
│   └── Support/
│       ├── ColumnMapper.php         # Column mapping logic
│       └── ChunkIterator.php        # Chunked iteration helper
├── Console/Commands/
│   └── SynapseExcelCleanup.php      # Mark stale tasks as failed
├── Enums/
│   └── ExcelTaskStatus.php          # Pending, Processing, Completed, Failed, Cancelled
├── Events/
│   ├── ExcelImportCompleted.php
│   ├── ExcelImportFailed.php
│   ├── ExcelExportCompleted.php
│   └── ExcelExportFailed.php
├── Jobs/
│   ├── ProcessExcelImport.php       # Queued import job
│   └── ProcessExcelExport.php       # Queued export job
├── Models/
│   ├── ExcelImport.php              # Import task tracking model
│   └── ExcelExport.php              # Export task tracking model

database/migrations/
├── 2026_01_28_000001_create_excel_imports_table.php
├── 2026_01_28_000002_create_excel_exports_table.php
└── 2026_01_31_000001_add_reference_to_excel_tables.php

tests/
├── Unit/Services/Excel/
│   ├── ExcelImporterTest.php
│   ├── ExcelExporterTest.php
│   ├── ColumnTransformerTest.php
│   ├── StyledWriterTest.php
│   ├── ColumnMapperTest.php
│   └── ChunkIteratorTest.php
├── Feature/Excel/
│   ├── ExcelImportTest.php
│   ├── ExcelExportTest.php
│   └── ExcelJobTest.php
└── fixtures/
    ├── sample.csv
    ├── sample.xlsx
    └── template.xlsx
```

---

## Database Tables

### `excel_imports`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| status | string(20) | ExcelTaskStatus enum |
| file_path | string | Source file path |
| user_id | unsignedBigInteger nullable | Who initiated |
| reference | string nullable indexed | Module-defined tracking identifier |
| processor_class | string nullable | FQCN of ProcessesImport implementation |
| total_rows | unsignedInteger nullable | Total rows in file |
| processed_rows | unsignedInteger default(0) | Rows processed so far |
| failed_at_row | unsignedInteger nullable | Row where failure occurred |
| options | json nullable | Serialized config (mapping, starting_row, chunk_size) |
| error_message | text nullable | Error details on failure |
| started_at | timestamp nullable | |
| completed_at | timestamp nullable | |
| last_heartbeat_at | timestamp nullable | Updated after each chunk for stale detection |
| timestamps | | created_at, updated_at |

### `excel_exports`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| status | string(20) | ExcelTaskStatus enum |
| output_path | string nullable | Generated file path |
| user_id | unsignedBigInteger nullable | Who initiated |
| reference | string nullable indexed | Module-defined tracking identifier |
| query_class | string nullable | FQCN of ExportQuery implementation |
| total_rows | unsignedInteger nullable | |
| processed_rows | unsignedInteger default(0) | |
| options | json nullable | Serialized config (columns, starting_row, template, sheet_name, column_styles, header_style, filters) |
| error_message | text nullable | |
| started_at | timestamp nullable | |
| completed_at | timestamp nullable | |
| last_heartbeat_at | timestamp nullable | Updated after each chunk for stale detection |
| timestamps | | |

---

## Contracts

### `ProcessesImport` (for background import jobs)

```php
interface ProcessesImport
{
    /**
     * Process a chunk of imported rows.
     *
     * @param Collection<int, array<string, mixed>> $rows
     * @param int $chunkIndex 0-based chunk counter
     */
    public function process(Collection $rows, int $chunkIndex): void;
}
```

Usage example:
```php
class UserImportProcessor implements ProcessesImport
{
    public function process(Collection $rows, int $chunkIndex): void
    {
        User::insert($rows->toArray());
    }
}
```

### `ExportQuery` (for background export jobs)

```php
interface ExportQuery
{
    /**
     * Build and return the query for export.
     */
    public function __invoke(): EloquentBuilder|QueryBuilder;
}
```

Usage example:
```php
class ActiveUsersExport implements ExportQuery
{
    public function __invoke(): EloquentBuilder
    {
        return User::query()->where('active', true)->with('department');
    }
}
```

### `ExportQuery` with Filters

For dynamic filtering based on user input, define constructor parameters in your `ExportQuery` class:

```php
class YearlyReportExport implements ExportQuery
{
    public function __construct(
        public readonly ?int $year = null,
        public readonly ?string $status = null,
        public readonly ?int $departmentId = null,
    ) {}

    public function __invoke(): EloquentBuilder
    {
        return Report::query()
            ->when($this->year, fn($q) => $q->whereYear('created_at', $this->year))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId));
    }
}

// Usage with filters
$task = ExcelExporter::make(YearlyReportExport::class)
    ->columns(['Title' => 'title', 'Status' => 'status', 'Amount' => 'amount'])
    ->withFilters([
        'year' => 2025,
        'status' => 'approved',
        'departmentId' => 5,
    ])
    ->forUser(auth()->id())
    ->dispatch('/path/to/yearly-report.xlsx');
```

The filters are stored in `options['filters']` and passed to the `ExportQuery` constructor via Laravel's container when the job runs.

---

## API Design

### ExcelImporter

```php
// Sync import - returns all rows
$rows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)                          // skip header row
    ->mapColumns(['name', 'email', 'phone'])  // positional mapping
    ->import();                               // Returns Collection

// Sync import - associative mapping
$rows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name' => 0, 'email' => 2, 'phone' => 4])  // explicit column index
    ->import();

// Sync import - closure mapping
$rows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(fn(array $row, int $i) => [
        'full_name' => $row[0] . ' ' . $row[1],
        'email' => strtolower($row[2]),
    ])
    ->import();

// Chunked import (memory efficient for large files)
ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email'])
    ->chunk(1000, function (Collection $rows, int $chunkIndex) {
        User::insert($rows->toArray());
    });

// Background import (dispatches to queue)
$importTask = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email'])
    ->forUser(auth()->id())
    ->onQueue('imports')
    ->dispatch(UserImportProcessor::class, chunkSize: 1000);
// Returns ExcelImport model

// Resume a failed import
ExcelImporter::resume($importTask);
```

### ExcelExporter

```php
// Sync export - simple columns
ExcelExporter::make(User::query()->where('active', true))
    ->columns(['name', 'email', 'created_at'])   // attribute names = header labels
    ->startingRow(1)
    ->export('/path/to/output.xlsx');

// Sync export - associative columns with closures
ExcelExporter::make(User::query()->with('department'))
    ->columns([
        'Full Name' => 'name',
        'Email' => 'email',
        'Department' => fn($user) => $user->department?->name ?? 'N/A',
        'Joined' => fn($user) => $user->created_at->format('Y-m-d'),
    ])
    ->sheet('Users Report')
    ->export('/path/to/output.xlsx');

// Sync export - with template
ExcelExporter::make(Invoice::query())
    ->columns(['invoice_number', 'client_name', 'total'])
    ->startingRow(5)                              // data starts at row 5 in template
    ->template('/path/to/template.xlsx')          // requires phpspreadsheet
    ->export('/path/to/output.xlsx');

// Sync export - download response
return ExcelExporter::make(User::query())
    ->columns(['name', 'email'])
    ->download('users-export.xlsx');

// Background export
$exportTask = ExcelExporter::make(ActiveUsersExport::class)  // ExportQuery class
    ->columns(['Name' => 'name', 'Email' => 'email'])
    ->forUser(auth()->id())
    ->onQueue('exports')
    ->dispatch('/path/to/output.xlsx');
// Returns ExcelExport model

// Resume a failed export (re-runs from scratch since exports are idempotent)
ExcelExporter::resume($exportTask);
```

Note: `ExcelExporter::make()` accepts both a query builder (for sync) and a string class name implementing `ExportQuery` (required for background dispatch).

---

## Background Processing

### Import Job (`ProcessExcelImport`)

1. Load `ExcelImport` model by ID
2. Check if cancelled, skip if so
3. Reconstruct `ExcelImporter` from stored options
4. Resume from `failed_at_row` if resuming
5. Process in chunks using the `ProcessesImport` class
6. Update `processed_rows` after each chunk
7. On completion: mark completed, dispatch `ExcelImportCompleted` event
8. On failure: record `failed_at_row` and error, dispatch `ExcelImportFailed` event
9. `$tries = 3`, `$timeout = 3600` (1 hour), `$backoff = [30, 60, 120]`

### Export Job (`ProcessExcelExport`)

1. Load `ExcelExport` model by ID
2. Instantiate `ExportQuery` class, get query builder
3. Select writer (StyledWriter if styles present, TemplateWriter if template set, SpoutWriter otherwise)
4. Use `cursor()` for memory-efficient row iteration
5. Write rows, update progress periodically
6. On completion: mark completed, dispatch `ExcelExportCompleted` event
7. On failure: record error, dispatch `ExcelExportFailed` event
8. Resume = re-run entire export (idempotent, file is overwritten)

### Events

All events carry the task model (`ExcelImport` or `ExcelExport`):
- `ExcelImportCompleted` - task completed successfully
- `ExcelImportFailed` - task failed (includes `$exception`)
- `ExcelExportCompleted` - task completed successfully
- `ExcelExportFailed` - task failed (includes `$exception`)

Consuming modules listen to these events and decide how to notify users (mail, database notification, broadcast, etc.).

---

## Resume Strategy

| | Import | Export |
|---|---|---|
| On failure | Records `failed_at_row` | Records error |
| On resume | Skips to `failed_at_row + 1`, continues processing | Re-runs entire export from scratch |
| Why | Imports have side effects (DB inserts) - can't re-process | Exports are idempotent - same query = same output |
| Auto-retry | Yes, via `$tries = 3` with backoff | Yes, via `$tries = 3` with backoff |
| Manual resume | `ExcelImporter::resume($task)` | `ExcelExporter::resume($task)` |

### Handling Killed Processes (OOM, server crash, user kill)

When a job process is killed without a chance to execute `failed()`, the task remains stuck in `processing` status. Three mechanisms handle this:

1. **Heartbeat tracking** - Both tables have a `last_heartbeat_at` column, updated after every chunk. A task is considered "stale" if it's been `processing` but `last_heartbeat_at` is older than the configured timeout (default: job `$timeout` + 5 minutes buffer).

2. **Stale detection in `resume()`** - The `resume()` method accepts both `failed` and stale `processing` tasks. It checks `last_heartbeat_at` against the timeout threshold. If stale, it marks the task as `failed` first, then re-dispatches from the last checkpoint.

3. **Cleanup artisan command** - `synapse:excel-cleanup` scans for stale tasks and marks them as `failed`. Can be run manually or scheduled.

---

## Template Exports

When `->template('path.xlsx')` is called:
1. Check `class_exists(PhpOffice\PhpSpreadsheet\Spreadsheet::class)`
2. If available: use `TemplateWriter` (loads template, writes data into cells starting at `startingRow`)
3. If not installed: throw `RuntimeException` with install instructions
4. Template preserves all formatting, formulas, and charts from the original file

---

## Column Styling

The `ExcelExporter` supports column and header styling via `columnStyles()` and `headerStyle()` methods. When styles are set, the exporter automatically uses `StyledWriter` (PhpSpreadsheet-based) instead of `SpoutWriter`.

### Basic Usage

```php
ExcelExporter::make($query)
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
        'alignment' => ['horizontal' => 'center'],
    ])
    ->export('output.xlsx');
```

### Style Keys Reference

Styles are arrays compatible with PhpSpreadsheet's `applyFromArray()` method. Two special keys are handled separately:

| Key | Type | Description |
|-----|------|-------------|
| `width` | float | Column width in characters |
| `autoSize` | bool | Enable auto-sizing for the column |

All other keys are passed directly to PhpSpreadsheet:

#### Font Styles
```php
'font' => [
    'bold' => true,
    'italic' => true,
    'underline' => true,
    'strikethrough' => true,
    'color' => ['rgb' => 'FF0000'],  // Red text
    'size' => 12,
    'name' => 'Arial',
]
```

#### Fill (Background) Styles
```php
'fill' => [
    'fillType' => 'solid',  // solid, linear, path, darkDown, darkGray, etc.
    'startColor' => ['rgb' => '4472C4'],
    'endColor' => ['rgb' => 'FFFFFF'],  // For gradients
]
```

#### Alignment Styles
```php
'alignment' => [
    'horizontal' => 'center',  // left, center, right, justify
    'vertical' => 'center',    // top, center, bottom
    'wrapText' => true,
    'textRotation' => 45,
]
```

#### Border Styles
```php
'borders' => [
    'allBorders' => [
        'borderStyle' => 'thin',  // thin, medium, thick, dashed, dotted
        'color' => ['rgb' => '000000'],
    ],
    'outline' => [
        'borderStyle' => 'medium',
        'color' => ['rgb' => '333333'],
    ],
]
```

#### Number Format
```php
'numberFormat' => [
    'formatCode' => '#,##0.00',      // Numbers with 2 decimals
    // 'formatCode' => '$#,##0.00',  // Currency
    // 'formatCode' => '0%',          // Percentage
    // 'formatCode' => 'yyyy-mm-dd', // Date
]
```

### columnStyles()

Applies styles to **data cells** (not the header row). Styles are keyed by header label (must match keys in `columns()`).

```php
->columnStyles([
    'Full Name' => [
        'width' => 30,
        'font' => ['bold' => true],
    ],
    'Salary' => [
        'width' => 15,
        'numberFormat' => ['formatCode' => '#,##0.00'],
        'alignment' => ['horizontal' => 'right'],
    ],
])
```

### headerStyle()

Applies a uniform style to **all header cells** in the first row.

```php
->headerStyle([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
])
```

### Freeze Pane

When header styles are applied, the header row is automatically frozen (freeze pane at row 2). This allows users to scroll through data while keeping headers visible.

### Combining with Templates

Styles can be combined with template exports:

```php
ExcelExporter::make($query)
    ->columns(['name', 'email', 'phone'])
    ->startingRow(5)
    ->template('/path/to/template.xlsx')
    ->columnStyles([
        'name' => ['font' => ['bold' => true]],
    ])
    ->export('/path/to/output.xlsx');
```

Note: When using templates, the header row freeze is disabled since templates typically have their own formatting.

### Background Export with Styles

Styles are fully serializable and work with background exports:

```php
$task = ExcelExporter::make(ActiveUsersExport::class)
    ->columns(['Name' => 'name', 'Email' => 'email'])
    ->columnStyles([
        'Name' => ['width' => 30],
        'Email' => ['width' => 50],
    ])
    ->headerStyle([
        'font' => ['bold' => true],
    ])
    ->forUser(auth()->id())
    ->dispatch('/path/to/output.xlsx');
```

The `column_styles` and `header_style` are stored in the task's `options` JSON and applied during job execution.

### Format Limitations

- **XLSX only** - Styling is only supported for XLSX format. Attempting to apply styles to CSV exports will throw a `RuntimeException`.
- **PhpSpreadsheet required** - When styles are set, PhpSpreadsheet is used instead of OpenSpout.

### Writer Selection Logic

| Condition | Writer Used |
|-----------|-------------|
| Custom writer via `useWriter()` | Custom writer |
| Styles set (`columnStyles` or `headerStyle`) | `StyledWriter` |
| Template set (no styles) | `TemplateWriter` |
| Default (no template, no styles) | `SpoutWriter` |

---

## Reference Tracking

Both `ExcelExport` and `ExcelImport` models support a `reference` column — a module-defined string identifier for looking up tasks across page refreshes without storing the task ID in the session.

### Setting a Reference

```php
// Export with reference
$task = ExcelExporter::make(InvoiceExport::class)
    ->columns(['Invoice #' => 'number', 'Total' => 'total'])
    ->reference('pm-invoice-export-42')
    ->forUser(auth()->id())
    ->dispatch('/path/to/output.xlsx');

// Import with reference
$task = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email'])
    ->reference('pm-user-import-99')
    ->forUser(auth()->id())
    ->dispatch(UserImportProcessor::class);
```

### Looking Up by Reference

```php
// Find the latest task with this reference
$task = ExcelExport::findByReference('pm-invoice-export-42');
$task = ExcelImport::findByReference('pm-user-import-99');

// Query scope for multiple tasks
$tasks = ExcelExport::forReference('pm-invoice-export-42')->get();
$tasks = ExcelImport::forReference('pm-user-import-99')->get();
```

### Reference Convention

References are freeform strings. A recommended pattern is `{module}-{entity}-{action}-{id}`:

```
pm-invoice-export-42
pm-user-import-batch-7
hr-payroll-report-2025-01
```

---

## Dot Notation for Relationships

Column definitions support dotted paths to resolve nested relationships without manual joins. Values are resolved via `data_get()`.

```php
ExcelExporter::make(Invoice::query()->with('client.company'))
    ->columns([
        'Invoice #' => 'invoice_number',
        'Client'    => 'client.name',
        'Company'   => 'client.company.name',
    ])
    ->export('/path/to/output.xlsx');
```

### How It Works

- Plain strings (no dot): `$record->getAttribute('name')` (existing behavior)
- Dotted strings: `data_get($record, 'client.name')` (traverses relationships)

### Background Export Compatibility

Dot notation strings are plain strings — fully serializable. They work in both sync and background exports without any special handling.

> **Important:** Your `ExportQuery` class must eager-load the relationships used in dot notation to avoid N+1 queries:
> ```php
> class InvoiceExport implements ExportQuery
> {
>     public function __invoke(): EloquentBuilder
>     {
>         return Invoice::query()->with('client.company');
>     }
> }
> ```

---

## Column Transformers

Column transformers are serializable value formatters that work in both sync and background exports — unlike closures which are lost during serialization.

### ColumnTransformer Contract

```php
interface ColumnTransformer
{
    public function transform(mixed $value, mixed $record): mixed;
}
```

### Column Definition Format

Transformers are specified as arrays in the `columns()` definition:

```php
ExcelExporter::make($query)
    ->columns([
        'Name'    => 'name',                                        // string (existing)
        'Client'  => 'client.name',                                 // dot notation
        'Notes'   => fn($r) => $r->notes ?? 'None',                // closure (sync only)
        'Joined'  => [                                              // transformer (new)
            'field' => 'created_at',
            'transformer' => DateFormatTransformer::class,
            'config' => ['format' => 'Y-m-d'],
        ],
        'Active'  => [
            'field' => 'is_active',
            'transformer' => BooleanTransformer::class,
            'config' => ['trueLabel' => 'Active', 'falseLabel' => 'Inactive'],
        ],
    ])
    ->export('/path/to/output.xlsx');
```

**Array keys:**

| Key | Required | Description |
|-----|----------|-------------|
| `field` | Yes | Attribute name (supports dot notation) |
| `transformer` | No | FQCN of a `ColumnTransformer` class |
| `config` | No | Array of constructor arguments for the transformer |

If `transformer` is omitted, the field value is returned as-is (useful for dot notation extraction via array syntax).

### Built-in Transformers

| Transformer | Config | Default | Purpose |
|------------|--------|---------|---------|
| `DateFormatTransformer` | `format` | `'Y-m-d'` | Format dates |
| `NumberFormatTransformer` | `decimals`, `decimalSeparator`, `thousandsSeparator` | `2`, `'.'`, `','` | Format numbers |
| `BooleanTransformer` | `trueLabel`, `falseLabel` | `'Yes'`, `'No'` | Bool to label |
| `DefaultValueTransformer` | `default` | `'-'` | Replace null/empty |
| `UpperCaseTransformer` | *(none)* | | Uppercase strings |

### Usage Examples

```php
use VmEngine\Synapse\Services\Excel\Transformers\DateFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\NumberFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\BooleanTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\DefaultValueTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\UpperCaseTransformer;

ExcelExporter::make(Employee::query()->with('department'))
    ->columns([
        'Name' => [
            'field' => 'name',
            'transformer' => UpperCaseTransformer::class,
        ],
        'Department' => 'department.name',
        'Salary' => [
            'field' => 'salary',
            'transformer' => NumberFormatTransformer::class,
            'config' => ['decimals' => 0, 'thousandsSeparator' => '.'],
        ],
        'Active' => [
            'field' => 'is_active',
            'transformer' => BooleanTransformer::class,
        ],
        'Hired' => [
            'field' => 'hired_at',
            'transformer' => DateFormatTransformer::class,
            'config' => ['format' => 'd/m/Y'],
        ],
        'Notes' => [
            'field' => 'notes',
            'transformer' => DefaultValueTransformer::class,
            'config' => ['default' => 'N/A'],
        ],
    ])
    ->export('/path/to/output.xlsx');
```

### Custom Transformers

Create your own by implementing `ColumnTransformer`:

```php
use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;

class CurrencyTransformer implements ColumnTransformer
{
    public function __construct(
        public readonly string $prefix = 'Rp. ',
        public readonly int $decimals = 0,
    ) {}

    public function transform(mixed $value, mixed $record): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->prefix . number_format((float) $value, $this->decimals, ',', '.');
    }
}

// Usage
->columns([
    'Total' => [
        'field' => 'total',
        'transformer' => CurrencyTransformer::class,
        'config' => ['prefix' => 'Rp. ', 'decimals' => 0],
    ],
])
```

### Background Export Compatibility

Transformer arrays are JSON-serializable and work in background exports:

```php
$task = ExcelExporter::make(InvoiceExport::class)
    ->columns([
        'Date' => [
            'field' => 'created_at',
            'transformer' => DateFormatTransformer::class,
            'config' => ['format' => 'd M Y'],
        ],
        'Amount' => [
            'field' => 'amount',
            'transformer' => NumberFormatTransformer::class,
            'config' => ['decimals' => 2],
        ],
    ])
    ->forUser(auth()->id())
    ->dispatch('/path/to/output.xlsx');
```

### Serialization Behavior

| Column Type | Sync Export | Background Export |
|-------------|-----------|-------------------|
| `string` | Resolved directly | Resolved directly |
| `string` (dotted) | `data_get()` | `data_get()` |
| `Closure` | Invoked | Serialized as `null` (skipped) |
| `array` (transformer) | Field resolved + transformer applied | Field resolved + transformer applied |
