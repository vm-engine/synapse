# Blade Components

Synapse provides pre-built Blade components for common UI patterns. All components use the `synapse::` namespace.

## Form Components

### Checkboxes

Multiple checkbox inputs for array-based selections:

```blade
<x-synapse-checkboxes
    name="permissions"
    :options="['read' => 'Read', 'write' => 'Write', 'delete' => 'Delete']"
    :model="$selectedPermissions" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (array, default: []) - Selected values

---

### Radio Buttons

Radio button group for single selection:

```blade
<x-synapse-radio-buttons
    name="status"
    :options="['active' => 'Active', 'inactive' => 'Inactive']"
    :model="$currentStatus" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (string, default: '') - Selected value

---

### Select Dropdown

Standard dropdown select:

```blade
<x-synapse-select
    :options="['option1' => 'Option 1', 'option2' => 'Option 2']" />
```

**Props:**
- `options` (array, required) - Select options

---

### Multi-Select

Multiple selection dropdown:

```blade
<x-synapse-multiselect
    name="tags"
    :options="$availableTags"
    :model="$selectedTags" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (array, default: []) - Selected values

---

### Password Input

Password field with show/hide toggle:

```blade
<x-synapse-password />
```

---

### Password Generator

Read-only password generator with separate generate and apply actions:

```blade
{{-- Single property --}}
<x-synapse::password-generator
    model="password"
    :length="12"
/>

{{-- Multiple properties (e.g., password + confirmation) --}}
<x-synapse::password-generator
    :model="['password', 'password_confirmation']"
    :length="16"
/>

{{-- Display only (no apply button) --}}
<x-synapse::password-generator :length="20" />
```

**Props:**
- `model` (string|array, optional) - Livewire property name(s) to set when Apply button is clicked. Accepts single string or array of strings
- `length` (int, default: 12) - Password length (minimum: 8)
- `...attributes` - Any additional HTML input attributes

**Features:**
- **Read-only field**: Password is always visible, cannot be manually edited
- **Separate actions**: Generate creates password, Apply sets Livewire properties
- Generate secure passwords with guaranteed character variety (lowercase, uppercase, numbers, symbols)
- Copy generated password to clipboard
- Set multiple Livewire properties at once (perfect for password + confirmation fields)
- Generate multiple times before applying
- Alpine.js powered with no wire:model dependency

**Buttons:**
- **Generate** (refresh icon) - Creates new password and displays in field
- **Apply** (checkmark icon) - Sets current password to Livewire properties (only shown if `model` is provided)
- **Copy** (copy icon) - Copies password to clipboard

**Events:**
- `password-set` - Dispatched when Apply button is clicked
- `password-copied` - Dispatched when Copy button is clicked

**See:** [Complete Usage Examples](COMPONENTS_PASSWORD_GENERATOR_EXAMPLE.md)

---

### Currency Input

Currency input with formatting:

```blade
<x-synapse-currency currency="$" />
<x-synapse-currency currency="Rp." />
```

**Props:**
- `currency` (string, default: 'Rp.') - Currency symbol

---

### Datepicker

Date picker using Flatpickr:

```blade
<x-synapse-datepicker />

<!-- With custom Flatpickr config -->
<x-synapse-datepicker
    :flatpickr='{"dateFormat": "Y-m-d", "enableTime": true}' />
```

**Props:**
- `flatpickr` (string, default: '{}') - JSON configuration for Flatpickr

---

## UI Components

### Confirm Dialog

Modal confirmation for destructive actions:

```blade
<x-synapse-confirm-dialog
    title="Delete Item"
    message="This action cannot be undone. Are you sure?"
    confirmText="Delete"
    cancelText="Cancel"
    confirmColor="danger"
    icon="fa-solid fa-trash" />
```

**Props:**
- `title` (string, default: 'Confirm Action')
- `message` (string, default: 'Are you sure you want to proceed?')
- `confirmText` (string, default: 'Confirm')
- `cancelText` (string, default: 'Cancel')
- `confirmColor` (string, default: 'danger')
- `icon` (string, default: 'fa-solid fa-circle-exclamation')

---

### Drawer

Slide-out side panel:

```blade
<x-synapse-drawer title="Settings">
    <!-- Drawer content here -->
    <form>
        <!-- Form fields -->
    </form>
</x-synapse-drawer>
```

**Props:**
- `title` (string, required) - Drawer header title

---

### Toastr Notifications

Toast notifications for user feedback:

```blade
<x-synapse-toastr />
```

Place this component in your layout file to enable toast notifications throughout your application.

---

### Alert

Flexible alert component with dismissible functionality:

```blade
<!-- Basic alert -->
<x-synapse-alert type="success" message="Operation completed successfully!" />

<!-- Alert with title -->
<x-synapse-alert
    type="warning"
    title="Warning"
    message="Please review your changes before submitting." />

<!-- Non-dismissible alert -->
<x-synapse-alert
    type="danger"
    :dismissible="false"
    message="This is a critical error." />

<!-- Border accent style -->
<x-synapse-alert
    type="info"
    :border="true"
    message="Information with border accent" />

<!-- Solid background style -->
<x-synapse-alert
    type="success"
    :solid="true"
    message="Solid background variant" />

<!-- Custom HTML content using slot -->
<x-synapse-alert type="info" title="Getting Started">
    <p>Welcome! Check out <a href="/docs" class="underline">our documentation</a>.</p>
    <ul class="list-disc list-inside mt-2">
        <li>Feature 1</li>
        <li>Feature 2</li>
    </ul>
</x-synapse-alert>

<!-- Custom icon -->
<x-synapse-alert
    type="success"
    icon="fa-solid fa-check-double"
    message="Verified successfully!" />
```

**Props:**
- `type` (string, default: 'info') - Alert type: `'success'`, `'warning'`, `'danger'`, `'error'`, `'info'`
- `title` (string, optional) - Alert title
- `message` (string, optional) - Alert message (can also use slot for HTML)
- `dismissible` (boolean, default: true) - Whether alert can be closed
- `icon` (string, auto-assigned) - FontAwesome icon class (auto-assigned based on type)
- `border` (boolean, default: false) - Show left border accent
- `solid` (boolean, default: false) - Use solid background instead of light variant

**Features:**
- 4 alert types with auto-assigned icons (success, warning, danger, info)
- Dismissible with Alpine.js smooth animations
- Dark mode support
- Supports both props and slot content
- Border and solid style variants
- Accessible with ARIA attributes

**Default Icons:**
- Success: `fa-solid fa-circle-check`
- Warning: `fa-solid fa-triangle-exclamation`
- Danger/Error: `fa-solid fa-circle-exclamation`
- Info: `fa-solid fa-circle-info`

**Common Use Cases:**

```blade
<!-- Flash messages -->
@if(session('success'))
    <x-synapse-alert type="success" :message="session('success')" />
@endif

@if(session('error'))
    <x-synapse-alert type="danger" :message="session('error')" />
@endif

<!-- Validation errors -->
@if($errors->any())
    <x-synapse-alert type="danger" title="Validation Errors">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-synapse-alert>
@endif

<!-- Multiple alerts -->
<div class="space-y-4">
    <x-synapse-alert type="success" message="Payment received." />
    <x-synapse-alert type="warning" message="Subscription expires in 3 days." />
    <x-synapse-alert type="info" message="New features available." />
</div>
```

---

## Advanced Select

A fully featured, reusable **Livewire + Alpine.js select component**.

Supports:

- ✅ Single or multiple selection
- ✅ Searchable dropdown (static or remote)
- ✅ Static or dynamic data
- ✅ Auto-loads selected values from wireModel (with AJAX support)
- ✅ Preloaded default values
- ✅ Create new options on-the-fly
- ✅ Optional "live" syncing with Livewire
- ✅ Works with Livewire SPA navigation

### Props

| Prop | Type | Default | Description |
|------|------|----------|-------------|
| `wireModel` | `string` | — | The Livewire property to bind to. |
| `searchMethod` | `string\|null` | `null` | Name of a Livewire method for remote searching. |
| `loadMethod` | `string\|null` | `null` | Name of a Livewire method to load initial values by ID(s). |
| `multiple` | `bool` | `false` | Enables multiple selection mode. |
| `placeholder` | `string` | `'Select an option'` | Placeholder text shown in the input. |
| `labelField` | `string` | `'label'` | The field name used for option labels. |
| `valueField` | `string` | `'value'` | The field name used for option values. |
| `preload` | `array` | `[]` | Preselected options (for edit forms). |
| `options` | `array` | `[]` | Static options for client-side use. |
| `minSearchLength` | `int` | `2` | Minimum characters before triggering remote search. |
| `live` | `bool` | `false` | If `true`, updates Livewire instantly as user selects. |
| `allowCreate` | `bool` | `false` | If `true`, allows creating new options by pressing Enter. |
| `autoLoad` | `bool` | `true` | If `true`, auto-loads selected values from wireModel on init. |

### Usage Examples

#### Static Single Select

```blade
<x-adv-select
    wire-model="role"
    :options="[
        ['value' => 'admin', 'label' => 'Administrator'],
        ['value' => 'editor', 'label' => 'Editor'],
        ['value' => 'user', 'label' => 'User'],
    ]"
/>
```

#### Static Multiple Select

```blade
<x-adv-select
    wire-model="permissions"
    :multiple="true"
    :options="[
        ['value' => 'create', 'label' => 'Create'],
        ['value' => 'read', 'label' => 'Read'],
        ['value' => 'update', 'label' => 'Update'],
        ['value' => 'delete', 'label' => 'Delete'],
    ]"
/>
```

#### Remote Search

```blade
<x-adv-select
    wire-model="user_id"
    search-method="searchUsers"
    placeholder="Search users..."
/>
```

In your Livewire component:

```php
public function searchUsers($query)
{
    return User::where('name', 'like', "%{$query}%")
        ->take(10)
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
        ]);
}
```

#### Auto-Load with AJAX (Edit Forms)

**New in v1.5:** The component now automatically loads selected values from the wireModel!

```blade
{{-- Static options: auto-finds labels from options --}}
<x-adv-select
    wire-model="category_id"
    :options="$categories"
/>

{{-- AJAX mode: uses loadMethod to fetch label by ID --}}
<x-adv-select
    wire-model="user_id"
    search-method="searchUsers"
    load-method="loadUser"
    placeholder="Select user..."
/>

{{-- Multiple with auto-load --}}
<x-adv-select
    wire-model="tags"
    :multiple="true"
    :allow-create="true"
/>
```

In your Livewire component:

```php
public $user_id = 5; // Component auto-loads user #5 on init

// Load method for fetching initial values by ID
public function loadUser(int|array $ids): array
{
    $query = User::query();

    if (is_array($ids)) {
        $query->whereIn('id', $ids);
    } else {
        $query->where('id', $ids);
    }

    return $query->get(['id as value', 'name as label'])->toArray();
}
```

**How it works:**
1. Component reads the current value from `wireModel`
2. Tries to find labels in static `options` first
3. If `loadMethod` is provided, calls it to fetch full objects
4. Falls back to using value as label if neither works

#### Manual Preload (Legacy)

You can still manually pass preloaded values:

```blade
<x-adv-select
    wire-model="category_id"
    :options="$categories"
    :preload="[
        ['value' => $product->category_id, 'label' => $product->category->name]
    ]"
/>
```
