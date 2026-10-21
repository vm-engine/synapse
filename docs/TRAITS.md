# Traits

Synapse provides useful traits to enhance your models and service providers with common functionality.

## AutoRegistersComponents

Auto-register Livewire and Blade components for Synapse modules without manual registration.

Uses Livewire v4's namespace system (`addNamespace()`) for automatic component discovery, supporting nested directories natively.

### Features

- **Uses Livewire v4 namespace system** — single `addNamespace()` call handles all component types
- **Auto-discovers MFC/SFC components** from `resources/views/components/` directory (including nested)
- **Auto-discovers class-based Livewire components** from `src/Livewire/` directory
- **Auto-discovers class-based Blade components** from `src/View/Components/` directory
- **Automatic prefix detection** from module namespace
- **Supports nested components** with kebab-case naming
- **Works for both directory and package modules**
- **Customizable via properties**

### Directory Structure

The trait expects your module to follow this structure:

```
YourModule/
├── src/
│   ├── YourModuleServiceProvider.php
│   ├── Livewire/                       # Class-based Livewire components
│   │   ├── Dashboard.php
│   │   ├── UserProfile.php
│   │   └── Forms/
│   │       └── ContactForm.php
│   └── View/
│       └── Components/                 # Class-based Blade components
│           ├── Alert.php
│           └── Forms/
│               └── Input.php
└── resources/
    └── views/
        ├── components/                 # MFC/SFC Livewire components
        │   ├── todo-form/              # MFC component
        │   │   ├── todo-form.php
        │   │   └── todo-form.blade.php
        │   └── forms/                  # Nested MFC components
        │       └── contact-form/
        │           ├── contact-form.php
        │           └── contact-form.blade.php
        └── livewire/                   # Views for class-based components
            └── dashboard.blade.php
```

### Usage

**Basic Usage:**

```php
<?php

namespace VmEngine\Example;

use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class ExampleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register(): void
    {
        // Auto-register all Livewire and Blade components
        $this->registerComponents();
    }
}
```

That's it! All components are now auto-registered with the correct prefix.

### Component Registration Patterns

All Livewire components (both class-based and MFC/SFC) use **namespace notation** (`prefix::name`):

**Class-based Livewire Components (from `src/Livewire/`):**

| File Path | Usage |
|-----------|-------|
| `src/Livewire/Dashboard.php` | `<livewire:example::dashboard />` |
| `src/Livewire/UserProfile.php` | `<livewire:example::user-profile />` |
| `src/Livewire/Forms/ContactForm.php` | `<livewire:example::forms.contact-form />` |

**MFC Livewire Components (from `resources/views/components/`):**

| File Path | Usage |
|-----------|-------|
| `resources/views/components/todo-form/todo-form.php` | `<livewire:example::todo-form />` |
| `resources/views/components/forms/contact-form/contact-form.php` | `<livewire:example::forms.contact-form />` |

**Blade Components (from `src/View/Components/`):**

| File Path | Usage |
|-----------|-------|
| `src/View/Components/Alert.php` | `<x-example::alert />` |
| `src/View/Components/Forms/Input.php` | `<x-example::forms.input />` |

### Automatic Prefix Detection

The trait automatically detects the module prefix from your ServiceProvider namespace:

| Namespace | Detected Prefix |
|-----------|-----------------|
| `VmEngine\Example\ExampleServiceProvider` | `example` |
| `VmEngine\MyPackage\MyPackageServiceProvider` | `my-package` |
| `SynApps\Modules\Home\HomeServiceProvider` | `home` |
| `SynApps\Modules\UserAuth\UserAuthServiceProvider` | `user-auth` |

### Customization Options

**Custom Prefix:**

```php
class ExampleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    protected ?string $componentPrefix = 'custom-prefix';

    public function register(): void
    {
        $this->registerComponents();
    }
}
```

**Disable Livewire Registration:**

```php
class ExampleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    protected bool $autoRegisterLivewireComponents = false;

    public function register(): void
    {
        // Only registers Blade components
        $this->registerComponents();
    }
}
```

**Disable Blade Components Registration:**

```php
class ExampleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    protected bool $autoRegisterBladeComponents = false;

    public function register(): void
    {
        // Only registers Livewire components
        $this->registerComponents();
    }
}
```

**Selective Registration:**

```php
class ExampleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register(): void
    {
        // Register only Livewire namespace (MFC/SFC + class-based)
        $this->registerLivewireNamespace();

        // Custom logic here...
    }

    public function boot(): void
    {
        // Register only Blade components
        $this->registerBladeComponents();
    }
}
```

### Before/After Comparison

**Before (Manual Registration):**

```php
use Livewire\Livewire;
use VmEngine\Example\Livewire\CategoryForm;
use VmEngine\Example\Livewire\CategoryList;
use VmEngine\Example\Livewire\Components\ButtonSample;
use VmEngine\Example\Livewire\ExampleForm;
use VmEngine\Example\Livewire\ExampleList;

class ExampleServiceProvider extends ServiceProvider
{
    public function register()
    {
        Livewire::component('example::button-sample', ButtonSample::class);
        Livewire::component('example-list', ExampleList::class);
        Livewire::component('example-form', ExampleForm::class);
        Livewire::component('category-list', CategoryList::class);
        Livewire::component('category-form', CategoryForm::class);
    }
}
```

**After (Auto Registration):**

```php
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class ExampleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register()
    {
        $this->registerComponents();
    }
}
```

### Available Methods

| Method | Description |
|--------|-------------|
| `registerComponents()` | Register all components (Livewire + Blade) |
| `registerLivewireNamespace()` | Register Livewire namespace (MFC/SFC + class-based) |
| `registerBladeComponents()` | Register only class-based Blade components |
| `getComponentPrefix()` | Get the detected/custom prefix |

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$componentPrefix` | `?string` | `null` | Custom prefix (auto-detected if null) |
| `$autoRegisterLivewireComponents` | `bool` | `true` | Enable Livewire auto-registration |
| `$autoRegisterBladeComponents` | `bool` | `true` | Enable Blade auto-registration |

### Notes

- The trait uses Livewire v4's `addNamespace()` which handles nested directory discovery natively
- **MFC/SFC components** in `resources/views/components/` are discovered via the Livewire namespace's `viewPath`
- **Class-based components** in `src/Livewire/` are discovered via the Livewire namespace's `classNamespace` and `classPath`
- **Class component views** in `resources/views/livewire/` are discovered via the Livewire namespace's `classViewPath`
- Component names are converted to kebab-case automatically
- The trait safely handles missing directories (passes `null` for non-existent paths)
- All Livewire components use namespace notation: `<livewire:prefix::component-name />`

### When to Use

✅ **Use the trait when:**
- You have multiple Livewire components to register
- You want consistent component naming
- You're building a reusable package module
- You want to reduce boilerplate code

❌ **Skip the trait when:**
- You have only 1-2 components
- You need custom component names that don't follow conventions
- You're using only anonymous Blade components

---

## RemembersQueryParams

A Livewire trait that persists list state (search terms, filters, sort options, pagination) to the session, so users see the same state when they navigate back to a list page.

### Purpose

When users click into a detail view and then navigate back (breadcrumb, browser back button, or direct link), their search/filter state is automatically restored. URL query parameters always take precedence over session values, so bookmarked or shared URLs continue to work correctly.

### Usage

```php
<?php

namespace SynApps\Modules\Blog\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Synapse\Traits\RemembersQueryParams;

class PostList extends Component
{
    use RemembersQueryParams;

    #[Url()] public string $q = '';
    #[Url()] public string $status = '';
    #[Url()] public string $sortBy = 'created_at';
    #[Url()] public string $sortDir = 'desc';

    protected function rememberedParams(): array
    {
        return ['q', 'status', 'sortBy', 'sortDir'];
    }

    public function mount(): void
    {
        $this->restoreRememberedParams();
    }

    public function clearFilters(): void
    {
        $this->q = '';
        $this->status = '';
        $this->forgetRememberedParams();
    }

    // ...
}
```

### How It Works

1. Declare which properties to persist in `rememberedParams()`.
2. Call `$this->restoreRememberedParams()` in `mount()` — it reads session values for any property that wasn't supplied via the URL.
3. Livewire's `#[Url()]` attribute keeps the URL in sync when properties change, which also updates the session automatically.
4. Call `forgetRememberedParams()` in your `clearFilters()` method to wipe the session state.

### Available Methods

| Method | Description |
|--------|-------------|
| `rememberedParams(): array` | Return property names to persist (override this) |
| `rememberKey(): string` | Session key (auto-generated from component class name) |
| `restoreRememberedParams(): void` | Restore properties from session (call in `mount()`) |
| `forgetRememberedParams(): void` | Clear all persisted params from session |
| `getRememberedParamValues(): array` | Return the currently stored session values |

### Session Key

The session key is derived from the component's class name (snake_case), e.g.:
- `SynApps\Modules\Blog\Livewire\PostList` → `post_list_params`

Override `rememberKey()` to use a custom key:

```php
protected function rememberKey(): string
{
    return 'my_custom_key_params';
}
```

### Notes

- URL query parameters (via `#[Url()]`) always override session values — shared/bookmarked URLs work correctly.
- The trait works alongside Livewire's `#[Url()]` attribute.
- Only call `restoreRememberedParams()` in `mount()`, not in `render()`.
- Call `forgetRememberedParams()` in `clearFilters()` to reset state cleanly.

---

## WithDeleteToken

A security trait that generates encrypted delete tokens for models to prevent unauthorized delete operations.

### Purpose

The `WithDeleteToken` trait protects against CSRF-like attacks on delete operations by:
- Generating unique encrypted tokens tied to the user's session
- Validating tokens before allowing deletions
- Using session-based salt for additional security
- Preventing token reuse across different sessions

### Usage

#### 1. Add Trait to Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use VmEngine\Synapse\Traits\WithDeleteToken;

class Post extends Model
{
    use WithDeleteToken;

    // Your model code...
}
```

#### 2. Access Delete Token

The trait automatically appends a `delete_token` attribute to your model:

```php
$post = Post::find(1);

// Get the delete token
$token = $post->delete_token;

// Token is automatically available when retrieving models
echo $post->delete_token; // "eyJpdiI6I..."
```

#### 3. Use in Blade Views

```blade
<form action="{{ route('posts.destroy', $post) }}" method="POST">
    @csrf
    @method('DELETE')

    <!-- Include delete token as hidden input -->
    <input type="hidden" name="delete_token" value="{{ $post->delete_token }}">

    <button type="submit" class="btn-danger">Delete</button>
</form>
```

Or with a confirm dialog:

```blade
<button
    wire:click="delete({{ $post->id }}, '{{ $post->delete_token }}')"
    class="btn-danger">
    Delete
</button>
```

#### 4. Validate Token in Controller/Livewire

```php
use App\Models\Post;

class PostController extends Controller
{
    public function destroy(Request $request, Post $post)
    {
        // Validate the delete token
        $id = Post::validateDeleteToken($request->delete_token);

        if ($id === false) {
            return back()->with('error', 'Invalid or expired delete token');
        }

        if ($id != $post->id) {
            return back()->with('error', 'Token does not match the resource');
        }

        $post->delete();

        return redirect()->route('posts.index')
            ->with('success', 'Post deleted successfully');
    }
}
```

#### Livewire Example

```php
<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;

class PostList extends Component
{
    public function delete($id, $token)
    {
        // Validate delete token
        $validatedId = Post::validateDeleteToken($token);

        if ($validatedId === false) {
            session()->flash('error', 'Invalid or expired delete token');
            return;
        }

        if ($validatedId != $id) {
            session()->flash('error', 'Token does not match the resource');
            return;
        }

        $post = Post::findOrFail($id);
        $post->delete();

        session()->flash('success', 'Post deleted successfully');
    }

    public function render()
    {
        return view('livewire.post-list', [
            'posts' => Post::all()
        ]);
    }
}
```

### Available Methods

#### `getDeleteTokenAttribute(): string`

Auto-appended accessor that returns the encrypted delete token.

```php
$token = $model->delete_token;
```

**Returns:** Encrypted token string

---

#### `generateDeleteToken($id): string`

Generates an encrypted delete token for a given ID.

```php
$token = $model->generateDeleteToken($model->id);
```

**Parameters:**
- `$id` (mixed) - The model ID to encrypt

**Returns:** Encrypted token string

---

#### `validateDeleteToken($token): string|false`

Static method to validate and decrypt a delete token.

```php
$id = ModelClass::validateDeleteToken($token);

if ($id === false) {
    // Invalid token
} else {
    // $id contains the decrypted ID
}
```

**Parameters:**
- `$token` (string) - The encrypted token to validate

**Returns:**
- `string` - The decrypted ID if valid
- `false` - If token is invalid or expired

### How It Works

1. **Token Generation:**
   - Creates a random 16-byte salt and stores it in the session
   - Combines the issuing model class, the model ID and the session salt: `"{class}|{id}|{salt}"`
   - Encrypts the combined string using Laravel's encryption
   - Returns the encrypted token

2. **Token Validation:**
   - Decrypts the token
   - Extracts the class, ID and salt from the decrypted string
   - Compares the salt with the current session's salt
   - Checks the token's class is the validating model or a subclass of it (`is_a`)
   - Returns the ID if valid, `false` otherwise

3. **Automatic Appending:**
   - The trait uses a model event listener (`retrieved`)
   - Automatically appends the `delete_token` attribute to all retrieved models
   - No need to manually call `generateDeleteToken()` for display purposes

### Security Features

- **Session-bound:** Tokens are tied to the user's session via a unique salt
- **Model-bound:** A token only validates on the model that issued it (or a parent class), so a `Client` token cannot delete a `Category` with the same ID
- **Encrypted:** Uses Laravel's encryption (AES-256-CBC by default)
- **One-time use:** Each page load generates a fresh salt (configurable)
- **CSRF protection:** Works alongside Laravel's CSRF protection
- **No database required:** All validation happens in-memory using sessions

### Best Practices

1. **Always validate tokens** before performing delete operations
2. **Check ID match** - Validate that the decrypted ID matches the resource being deleted
3. **Handle validation errors** gracefully with user feedback
4. **Use with CSRF protection** - Don't replace CSRF tokens, use both
5. **Clear session on logout** to invalidate all delete tokens

### Example: Complete Implementation

```php
// Model
class Article extends Model
{
    use WithDeleteToken;
}

// Livewire Component
class ArticleManager extends Component
{
    public function confirmDelete($id, $token)
    {
        $this->dispatch('confirm-delete', id: $id, token: $token);
    }

    public function delete($id, $token)
    {
        // Validate token
        $validatedId = Article::validateDeleteToken($token);

        if ($validatedId === false || $validatedId != $id) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Invalid delete token. Please refresh the page.'
            ]);
            return;
        }

        Article::findOrFail($id)->delete();

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Article deleted successfully'
        ]);
    }

    public function render()
    {
        return view('livewire.article-manager', [
            'articles' => Article::all()
        ]);
    }
}
```

```blade
<!-- livewire/article-manager.blade.php -->
<div>
    @foreach($articles as $article)
        <div class="article-item">
            <h3>{{ $article->title }}</h3>

            <button
                wire:click="confirmDelete({{ $article->id }}, '{{ $article->delete_token }}')"
                class="btn-danger">
                Delete
            </button>
        </div>
    @endforeach

    <!-- Confirmation Dialog -->
    <x-synapse-confirm-dialog
        wire:on="confirm-delete"
        wire:on-confirm="delete($event.detail.id, $event.detail.token)"
        title="Delete Article"
        message="Are you sure you want to delete this article?"
    />
</div>
```

### Troubleshooting

**Issue:** "Invalid delete token" error

**Solutions:**
- Ensure session is started
- Check that the user hasn't opened multiple tabs (session salt may have changed)
- Verify token is being passed correctly from frontend to backend
- Check that encryption key is set in `.env` (`APP_KEY`)

**Issue:** Token works in one tab but not another

**Cause:** The session salt changes when generating new tokens in different tabs

**Solution:** This is expected behavior for security. Users should complete delete operations in the same tab where they initiated them.

---

## RemembersQueryParams

Persists declared Livewire properties (filters, search terms, pagination) to the session so list pages automatically restore state when the user navigates back via breadcrumbs or browser history.

URL query parameters always take precedence over session values, so bookmarked or shared URLs work as expected.

### Usage

```php
<?php

namespace SynApps\Modules\Blog\Livewire;

use Livewire\Component;
use VmEngine\Synapse\Traits\RemembersQueryParams;

class PostList extends Component
{
    use RemembersQueryParams;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 20;

    protected function rememberedParams(): array
    {
        return ['search', 'status', 'perPage'];
    }

    // ...
}
```

That's it. When the user leaves the page and returns, `$search`, `$status`, and `$perPage` are automatically restored from the session (unless URL query params override them).

### How It Works

1. **On mount** (`mountRemembersQueryParams`) — restores session values for any `rememberedParams()` property that is **not** present in the current URL query string
2. **On every response** (`dehydrateRemembersQueryParams`) — saves current property values back to the session
3. **URL wins** — if a property appears in the URL query string it is never overridden by the session

### Livewire Integration

The trait uses Livewire's lifecycle hook naming conventions:

| Hook | Method | Trigger |
|------|--------|---------|
| `mount` | `mountRemembersQueryParams()` | Restore session values on component mount |
| `dehydrate` | `dehydrateRemembersQueryParams()` | Persist values to session after each request |

No additional wiring is required — Livewire calls these automatically.

### Customization

**Override which params are remembered:**

```php
protected function rememberedParams(): array
{
    return ['search', 'sortBy', 'sortDir'];
}
```

**Override the session key** (useful when multiple components use the same class name):

```php
protected function rememberKey(): string
{
    return 'blog:posts:list';
}
```

### Available Methods

| Method | Description |
|--------|-------------|
| `rememberedParams()` | Returns list of property names to persist (override in component) |
| `rememberKey()` | Returns session key (defaults to `synapse:query_params:{ClassName}`) |
| `forgetRememberedParams()` | Clears session storage — call from a "reset filters" action |
| `getRememberedParamValues()` | Returns stored values without modifying properties |

### Example: Reset Filters

```php
public function resetFilters(): void
{
    $this->search = '';
    $this->status = 'all';
    $this->forgetRememberedParams();
}
```

### Notes

- The trait is safe to use on any Livewire component
- Only properties declared in `rememberedParams()` are persisted — other properties are unaffected
- Uses Livewire's Referer header detection for AJAX requests so the URL check works correctly during Livewire requests

---

## WithReturnUrl

Provides a safe return-URL mechanism for Livewire form components, letting list pages pass a `?back=` parameter to forms so the form can redirect back to the correct page after saving.

Prevents open-redirect attacks by validating that the return URL belongs to the same application.

### Usage

#### 1. Add trait to form component

```php
<?php

namespace SynApps\Modules\Blog\Livewire;

use Livewire\Component;
use VmEngine\Synapse\Traits\WithReturnUrl;

class PostForm extends Component
{
    use WithReturnUrl;

    // $backUrl is automatically bound from ?back= query parameter
    // public string $backUrl = '';  ← declared by the trait

    public function save(): void
    {
        // ... save logic ...

        // Redirects to $backUrl if safe, or falls back to the named route
        $this->redirectBack('blog.posts.index');
    }
}
```

#### 2. Generate back-URL links from list pages

Use the `$withBack()` Alpine magic (backed by the global `window.withBack()` helper, both injected by `synapps:patch`) to append the current URL as the `back` parameter:

```blade
<!-- Blade view (list page) -->
<a :href="$withBack('{{ route('blog.posts.create') }}')"
   wire:navigate>
    Create Post
</a>

<!-- Or for edit links in a loop -->
@foreach($posts as $post)
    <a :href="$withBack('{{ route('blog.posts.edit', $post) }}')"
       wire:navigate>
        Edit
    </a>
@endforeach
```

The `withBack()` helper appends `?back=<current-url>` (or `&back=` if the URL already has query params). Use `$withBack()` (the Alpine magic) inside `x-`/`:`/`@`-bound expressions — Alpine's CSP-safe evaluator doesn't resolve arbitrary `window.*` globals like bare `withBack()`.

### How It Works

1. `$backUrl` is a public Livewire property decorated with `#[Url(as: 'back')]`, so Livewire automatically binds it from the `?back=` query parameter
2. `redirectBack()` validates that the URL starts with the application's base URL to prevent open-redirect attacks
3. If validation fails or `$backUrl` is empty, `redirectBack()` falls back to the provided named route

### Available Methods

#### `redirectBack(string $defaultRoute, array $parameters = []): void`

Redirect to `$backUrl` if safe, otherwise to the named route.

```php
// Simple fallback
$this->redirectBack('posts.index');

// With route parameters
$this->redirectBack('posts.show', ['post' => $post->id]);
```

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$backUrl` | `string` | `''` | Bound automatically from `?back=` query parameter via `#[Url]` |

### Security

- URLs are validated with `isSafeReturnUrl()` which checks `str_starts_with($url, url('/'))` — only same-origin URLs are accepted
- External URLs are silently ignored and the default route is used instead

### See Also

- [`window.withBack()` helper](HELPERS.md#withback) — JavaScript helper for generating back URLs in Blade views
