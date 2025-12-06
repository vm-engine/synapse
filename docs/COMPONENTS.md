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
- ✅ Preloaded default values
- ✅ Optional "live" syncing with Livewire
- ✅ Works with Livewire SPA navigation

### Props

| Prop | Type | Default | Description |
|------|------|----------|-------------|
| `wireModel` | `string` | — | The Livewire property to bind to. |
| `searchMethod` | `string\|null` | `null` | Name of a Livewire method for remote searching. |
| `multiple` | `bool` | `false` | Enables multiple selection mode. |
| `placeholder` | `string` | `'Select an option'` | Placeholder text shown in the input. |
| `labelField` | `string` | `'label'` | The field name used for option labels. |
| `valueField` | `string` | `'value'` | The field name used for option values. |
| `preload` | `array` | `[]` | Preselected options (for edit forms). |
| `options` | `array` | `[]` | Static options for client-side use. |
| `minSearchLength` | `int` | `2` | Minimum characters before triggering remote search. |
| `live` | `bool` | `false` | If `true`, updates Livewire instantly as user selects. |

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

#### Preloaded Values (Edit Forms)

```blade
<x-adv-select
    wire-model="category_id"
    :options="$categories"
    :preload="[
        ['value' => $product->category_id, 'label' => $product->category->name]
    ]"
/>
```
