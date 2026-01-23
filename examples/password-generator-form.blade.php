{{--
    Password Generator Component - Example Usage

    This example demonstrates using the password-generator component
    in a typical registration or password change form.
--}}

<div class="max-w-md mx-auto p-6">
    <h2 class="text-2xl font-bold mb-6">Create Account</h2>

    <form wire:submit="register">
        {{-- Email Field --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email Address
            </label>
            <input
                type="email"
                id="email"
                wire:model="email"
                class="form-input w-full"
                required
            />
            @error('email')
                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>

        {{-- Password Generator - Multiple Properties Example --}}
        <div class="mb-6">
            <label for="password-generator" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Generate Secure Password
            </label>
            <x-synapse::password-generator
                :model="['password', 'password_confirmation']"
                :length="16"
                id="password-generator"
                class="w-full"
            />
            <p class="text-xs text-gray-500 mt-1">
                Click refresh to generate, checkmark to apply, copy icon to save.
                Password is readonly and always visible.
            </p>
            @error('password')
                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>

        {{-- Optional: Manual Password Input --}}
        <div class="mb-6">
            <label for="manual-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Or Type Your Own Password
            </label>
            <input
                type="password"
                id="manual-password"
                wire:model.live="password"
                class="form-input w-full"
                placeholder="Enter your own password"
            />
            <p class="text-xs text-gray-500 mt-1">
                Typing here will override the generated password
            </p>
        </div>

        {{-- Submit Button --}}
        <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition-colors"
        >
            Create Account
        </button>
    </form>

    {{-- Success Notifications (using password events) --}}
    <div
        x-data="{ show: false, message: '' }"
        @password-set.window="
            show = true;
            message = 'Password applied successfully!';
            setTimeout(() => show = false, 3000)
        "
        @password-copied.window="
            show = true;
            message = 'Password copied to clipboard!';
            setTimeout(() => show = false, 3000)
        "
        x-show="show"
        x-transition
        class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg"
    >
        <span x-text="message"></span>
    </div>
</div>

{{--
    Corresponding Livewire Component:

    <?php

    namespace App\Livewire;

    use Livewire\Component;
    use Livewire\Attributes\Validate;
    use Illuminate\Support\Facades\Hash;
    use App\Models\User;

    class RegisterForm extends Component
    {
        #[Validate('required|email|unique:users')]
        public string $email = '';

        #[Validate('required|string|min:8|max:255')]
        public string $password = '';

        #[Validate('required|same:password')]
        public string $password_confirmation = '';

        public function register()
        {
            $this->validate();

            User::create([
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            session()->flash('success', 'Account created successfully!');

            return redirect()->route('login');
        }

        public function render()
        {
            return view('livewire.register-form');
        }
    }
--}}
