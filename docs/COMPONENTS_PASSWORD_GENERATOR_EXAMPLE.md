# Password Generator Component - Usage Examples

The `<x-synapse::password-generator>` component provides a secure password input with generation, copy, and visibility toggle features.

## Features

- ✅ **Read-only password field** - Password is always visible, cannot be manually edited
- ✅ Generate secure passwords with configurable length (minimum 8 characters)
- ✅ **Separate generate and apply actions** - Generate shows password, Apply sets Livewire properties
- ✅ Copy generated password to clipboard
- ✅ Set multiple Livewire properties at once
- ✅ Ensures password contains lowercase, uppercase, numbers, and symbols
- ✅ Alpine.js powered (no wire:model, manual control)
- ✅ Generate multiple times before applying

## How It Works

The component provides **three action buttons**:

1. **Generate Button** (refresh icon) - Generates a new password and displays it in the readonly field
2. **Apply Button** (checkmark icon) - Sets the current password to Livewire properties (only shown if `model` prop is provided)
3. **Copy Button** (copy icon) - Copies the current password to clipboard

### Workflow

```
1. Click Generate → Password appears in readonly field (always visible)
2. Click Generate again (optional) → New password replaces old one
3. Click Apply → Password is set to Livewire property/properties
4. Click Copy (optional) → Password copied to clipboard
```

This separation allows you to:
- Generate multiple passwords until you find one you like
- Review the password before applying it (always visible in plain text)
- Apply the same password to multiple properties simultaneously

## Basic Usage

### Single Property

```blade
<x-synapse::password-generator
    model="password"
    :length="12"
/>
```

### Multiple Properties (Password + Confirmation)

Perfect for registration or password change forms where you need to set both `password` and `password_confirmation`:

```blade
<x-synapse::password-generator
    :model="['password', 'password_confirmation']"
    :length="16"
/>
```

### Custom Length

```blade
{{-- Minimum length is enforced at 8 characters --}}
<x-synapse::password-generator
    model="password"
    :length="20"
/>
```

### With Form Input Attributes

```blade
<x-synapse::password-generator
    :model="['new_password', 'new_password_confirmation']"
    :length="16"
    class="w-full"
    id="password-field"
    name="password"
    required
/>
```

## Complete Livewire Form Example

```php
<?php

use Livewire\Component;
use Livewire\Attributes\Validate;

class RegisterForm extends Component
{
    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|same:password')]
    public string $password_confirmation = '';

    public function register()
    {
        $this->validate();

        // Create user with validated password
        User::create([
            'password' => Hash::make($this->password),
            // ...
        ]);

        session()->flash('success', 'Account created successfully!');
    }

    public function render()
    {
        return view('livewire.register-form');
    }
}
```

```blade
<form wire:submit="register">
    <div class="mb-4">
        <label for="password" class="block mb-2">Password</label>
        <x-synapse::password-generator
            :model="['password', 'password_confirmation']"
            :length="12"
            id="password"
            name="password"
        />
        @error('password')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
    </div>

    <button type="submit">Register</button>
</form>
```

## Password Change Form Example

```blade
<form wire:submit="changePassword">
    <div class="space-y-4">
        {{-- Current Password --}}
        <div>
            <label class="block mb-2">Current Password</label>
            <x-synapse::password
                wire:model="current_password"
            />
        </div>

        {{-- New Password with Generator --}}
        <div>
            <label class="block mb-2">New Password</label>
            <x-synapse::password-generator
                :model="['new_password', 'new_password_confirmation']"
                :length="16"
            />
            <p class="text-xs text-gray-500 mt-1">
                Generate (refresh icon), review (always visible), then apply (checkmark icon)
            </p>
        </div>

        <button type="submit">Change Password</button>
    </div>
</form>
```

## Props Reference

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `model` | `string\|array\|null` | `null` | Livewire property name(s) to set when Apply button is clicked. Can be a single string or array of strings. |
| `length` | `int` | `12` | Password length (minimum: 8). |
| `...attributes` | `mixed` | — | Any additional HTML attributes (class, id, name, etc.). Note: Field is always `readonly` and `type="text"`. |

## Important Notes

### Generate vs Apply

**Generate Button:**
- Creates a new secure password
- Displays it in the readonly text field (always visible)
- Does NOT set Livewire properties
- Can be clicked multiple times
- Each click replaces the previous password

**Apply Button (checkmark):**
- Sets the current displayed password to Livewire property/properties
- Only appears when `model` prop is provided
- Dispatches `password-set` event

### Read-Only Field

The password field is **read-only** and always displays passwords as plain text:
- Users cannot manually type or edit passwords
- Password is always visible (not masked)
- Only way to change it is clicking the Generate button
- This ensures only secure, generated passwords are used

### Recommended Usage Patterns

**For password generation with control:**
```blade
{{-- User generates, reviews (visible), then applies --}}
<x-synapse::password-generator
    :model="['password', 'password_confirmation']"
    :length="16"
/>
```

**Without Livewire binding (display only):**
```blade
{{-- No apply button shown, pure generation for display/copy --}}
<x-synapse::password-generator :length="20" />
```

**With separate manual password input:**
```blade
{{-- Label for generated password --}}
<label>Generated Password (Suggestion)</label>
<x-synapse::password-generator
    :length="16"
/>

{{-- Separate field for manual entry with live sync --}}
<label>Or Enter Your Own Password</label>
<input
    type="password"
    wire:model.live="password"
    class="form-input w-full"
    placeholder="Type your own password"
/>
```

## Events

The component dispatches two custom events:

### `password-set`
Dispatched when the Apply button is clicked and password is set to Livewire properties.

```blade
<div
    x-data
    @password-set.window="
        alert('Password applied successfully!')
    "
>
    <x-synapse::password-generator model="password" />
</div>
```

### `password-copied`
Dispatched when the Copy button is clicked and password is copied to clipboard.

```blade
<div
    x-data
    @password-copied.window="
        alert('Password copied to clipboard!')
    "
>
    <x-synapse::password-generator model="password" />
</div>
```

### Multiple Events

```blade
<div
    x-data="{
        message: '',
        show: false
    }"
    @password-set.window="
        message = 'Password applied!';
        show = true;
        setTimeout(() => show = false, 2000)
    "
    @password-copied.window="
        message = 'Password copied!';
        show = true;
        setTimeout(() => show = false, 2000)
    "
>
    <x-synapse::password-generator
        :model="['password', 'password_confirmation']"
    />

    <div x-show="show" x-transition class="alert">
        <span x-text="message"></span>
    </div>
</div>
```

## Password Generation Algorithm

The component generates secure passwords using this approach:

1. **Character Sets**: Uses lowercase, uppercase, numbers, and symbols
2. **Guaranteed Variety**: Ensures at least one character from each set
3. **Randomization**: Fills remaining positions with random characters
4. **Shuffling**: Randomizes the final password array to avoid patterns

Example generated password: `K7@mPq2$nL9x` (12 characters)

## Styling

The component uses Tailwind CSS classes and respects dark mode. The readonly input automatically includes right padding (`pr-32`) to accommodate the action buttons:

- **Generate button** (refresh icon) - Creates new password
- **Apply button** (checkmark icon) - Sets Livewire properties (only if `model` provided)
- **Copy button** (copy icon) - Copies to clipboard

You can customize the input styling by passing additional classes:

```blade
<x-synapse::password-generator
    model="password"
    class="border-blue-500 focus:ring-blue-500 bg-gray-50"
/>
```

**Note:** The field is always `readonly` and displays as plain text (not masked).

## Security Considerations

- **Read-only enforcement**: Field is readonly, preventing weak user-created passwords
- **Always visible**: Password shown as plain text for easy verification
- **Client-side generation**: Uses JavaScript's `Math.random()` for password generation
- **Character variety**: Ensures lowercase, uppercase, numbers, and symbols are included
- **Server validation**: Always validate password strength on the server side
- **Use Laravel validation**: Leverage Laravel's built-in password validation rules

## Browser Compatibility

- **Copy to Clipboard**: Requires modern browser with Clipboard API support
- **Alpine.js**: Component requires Alpine.js (included with Livewire 3.x)
- **FontAwesome Icons**: Generate button uses FontAwesome icon `fa-solid fa-refresh`
- **SVG Icons**: Apply and Copy buttons use inline SVG for compatibility
