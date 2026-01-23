# Password Generator - Workflow Guide

## Component Structure

```
┌─────────────────────────────────────────────────────────────────┐
│ [Readonly Field: aB3$xY7z]  [🔄] [✓] [📋]                        │
└─────────────────────────────────────────────────────────────────┘
                                  │    │    └────── Copy to clipboard
                                  │    └─────────── Apply to Livewire
                                  └──────────────── Generate password
```

**Note:** Password is always visible in plain text (readonly field)

## Button Behavior

### 1. Generate Button (🔄 Refresh Icon)
**Action:** Creates and displays a new password in readonly field
**Result:**
- Generates secure password with required character types (lowercase, uppercase, numbers, symbols)
- Updates readonly field value (always visible as plain text)
- Does NOT set Livewire properties
- Can be clicked multiple times
- Each click replaces the previous password

```blade
<x-synapse::password-generator :length="16" />
```

**Example Flow:**
```
Click 🔄 → "aB3$xY7z"  displayed in readonly field (visible)
Click 🔄 → "K9@mPq2$"  displayed in readonly field (replaces previous)
Click 🔄 → "L7%nRt4!" displayed in readonly field (replaces previous)
```

---

### 2. Apply Button (✓ Checkmark Icon)
**Action:** Sets current displayed password to Livewire properties
**Result:**
- Reads current readonly field value (generated password)
- Sets value to Livewire property/properties
- Dispatches `password-set` event
- Only visible when `model` prop is provided

```blade
<x-synapse::password-generator model="password" />
{{-- or --}}
<x-synapse::password-generator :model="['password', 'password_confirmation']" />
```

**Example Flow:**
```
Click 🔄 → "aB3$xY7z"  displayed in readonly field
Click ✓ → $wire.password = "aB3$xY7z" AND
          $wire.password_confirmation = "aB3$xY7z"
```

---

### 3. Copy Button (📋 Copy Icon)
**Action:** Copies displayed password to clipboard
**Result:**
- Reads current readonly field value
- Copies to system clipboard
- Dispatches `password-copied` event
- Only works with generated passwords

**Example Flow:**
```
Click 🔄 → "aB3$xY7z" displayed in readonly field
Click 📋 → "aB3$xY7z" copied to clipboard
```

---

## Common Workflows

### Workflow 1: Generate and Apply
**Use Case:** Generate password for registration form

```
1. User clicks 🔄 (Generate)
   → Password "K9@mPq2$" appears in readonly field (visible)

2. User clicks 🔄 again (not satisfied)
   → New password "L7%nRt4!" replaces previous

3. User clicks 🔄 one more time
   → New password "aB3$xY7z" replaces previous

4. User clicks ✓ (Apply)
   → $wire.password = "aB3$xY7z"
   → $wire.password_confirmation = "aB3$xY7z"

5. User clicks 📋 (Copy) to save password
   → "aB3$xY7z" copied to clipboard
```

### Workflow 2: Generate Only (No Livewire)
**Use Case:** Display password suggestions without binding

```blade
<x-synapse::password-generator :length="20" />
```

```
1. User clicks 🔄 (Generate)
   → Password appears in readonly field (visible)

2. User clicks 📋 (Copy)
   → Password copied to clipboard

Note: No ✓ (Apply) button shown because no model prop
```

### Workflow 3: Generate with Multiple Properties
**Use Case:** Set password and confirmation simultaneously

```blade
<x-synapse::password-generator
    :model="['password', 'password_confirmation', 'temp_password']"
    :length="16"
/>
```

```
1. User clicks 🔄 (Generate)
   → Password "K9@mPq2$nL5x" appears (16 chars)

2. User clicks ✓ (Apply)
   → $wire.password = "K9@mPq2$nL5x"
   → $wire.password_confirmation = "K9@mPq2$nL5x"
   → $wire.temp_password = "K9@mPq2$nL5x"
```

### Workflow 4: Event Handling
**Use Case:** Show feedback when password is applied

```blade
<div
    x-data="{ applied: false }"
    @password-set.window="applied = true; setTimeout(() => applied = false, 2000)"
>
    <x-synapse::password-generator
        :model="['password', 'password_confirmation']"
    />

    <div x-show="applied" class="text-green-600 mt-2">
        ✓ Password applied successfully!
    </div>
</div>
```

---

## Props Reference

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `model` | `string\|array\|null` | `null` | Livewire property/properties to set when Apply button clicked |
| `length` | `int` | `12` | Password length (minimum: 8) |
| `...attributes` | `mixed` | — | HTML input attributes (class, id, name, etc.) |

---

## Events Reference

| Event | Trigger | Payload | Use Case |
|-------|---------|---------|----------|
| `password-set` | Apply button clicked | None | Show confirmation, trigger validation |
| `password-copied` | Copy button clicked | None | Show "copied" message |

---

## Important Notes

### ✅ Generate is Independent
- Generate button only creates and displays password
- Does NOT modify Livewire properties
- Can be clicked multiple times safely
- Perfect for "trying different passwords"
- Each click replaces the previous password

### ✅ Apply is Explicit
- Apply button is required to set Livewire properties
- Only appears when `model` prop is provided
- Works with generated passwords only (field is readonly)
- Gives users full control over when to commit

### ✅ Read-Only by Design
- Component field is `readonly` - no manual typing allowed
- Password is always visible as plain text (type="text")
- This ensures only secure, generated passwords are used
- Prevents weak user-created passwords

### ✅ No Auto-Sync
- Component does NOT use `wire:model`
- No automatic sync to Livewire
- This is by design for explicit control
- Use separate `<input wire:model>` if you need manual password entry

---

## Comparison with Standard Password Input

### Standard Password Input
```blade
<input type="password" wire:model.live="password" />
```
- ❌ No generation capability
- ✅ Auto-syncs to Livewire
- ❌ No copy functionality
- ❌ User must create password manually
- ✅ User can type custom password
- ✅ Password is masked

### Password Generator Component
```blade
<x-synapse::password-generator model="password" />
```
- ✅ Built-in secure generation
- ❌ Manual sync via Apply button
- ✅ Copy to clipboard
- ✅ Set multiple properties at once
- ✅ User can generate multiple options before applying
- ❌ No manual password entry (readonly)
- ❌ Password always visible (plain text)
- ✅ Enforces strong, secure passwords
