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
│   │   └── ExportQuery.php          # Export query contract (for background jobs)
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
└── 2026_01_28_000002_create_excel_exports_table.php

tests/
├── Unit/Services/Excel/
│   ├── ExcelImporterTest.php
│   ├── ExcelExporterTest.php
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
