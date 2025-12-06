# Traits

Synapse provides useful traits to enhance your models with common functionality.

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
   - Combines the model ID with the session salt: `"{id}|{salt}"`
   - Encrypts the combined string using Laravel's encryption
   - Returns the encrypted token

2. **Token Validation:**
   - Decrypts the token
   - Extracts the ID and salt from the decrypted string
   - Compares the salt with the current session's salt
   - Returns the ID if valid, `false` otherwise

3. **Automatic Appending:**
   - The trait uses a model event listener (`retrieved`)
   - Automatically appends the `delete_token` attribute to all retrieved models
   - No need to manually call `generateDeleteToken()` for display purposes

### Security Features

- **Session-bound:** Tokens are tied to the user's session via a unique salt
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
