<div
    class="syn-password-generator"
    x-data="synapsePasswordGenerator({{ $length }})"
>
    <input
        type="text"
        x-ref="passwordInput"
        readonly
        placeholder="Enter or generate password"
        {{ $attributes->merge(['class' => 'form-input pr-32']) }}
    />

    <div class="syn-password-generator-actions">
        <!-- Generate Password Button -->
        <button
            class="syn-password-generator-btn"
            type="button"
            title="Generate Password"
            @click="generate()"
        >
            <span class="fa-solid fa-refresh"></span>
        </button>

        @if ($model)
            <!-- Set Property Button -->
            <button
                class="syn-password-generator-btn"
                type="button"
                title="Apply Password"
                @click="setProperty()"
            >
                <svg
                    class="syn-password-generator-icon"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                </svg>
            </button>
        @endif

        <!-- Copy Password Button -->
        <button
            class="syn-password-generator-btn"
            type="button"
            title="Copy Password"
            @click="copyPassword()"
        >
            <svg
                class="syn-password-generator-icon"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M16 1H4C2.9 1 2 1.9 2 3V17H4V3H16V1ZM19 5H8C6.9 5 6 5.9 6 7V21C6 22.1 6.9 23 8 23H19C20.1 23 21 22.1 21 21V7C21 5.9 20.1 5 19 5ZM19 21H8V7H19V21Z"
                />
            </svg>
        </button>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('synapsePasswordGenerator', (initialLength) => ({
                showPassword: false,
                passwordLength: initialLength,

                generatePassword(length) {
                    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
                    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    const numbers = '0123456789';
                    const symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
                    const allChars = lowercase + uppercase + numbers + symbols;

                    let password = [
                        lowercase[Math.floor(Math.random() * lowercase.length)],
                        uppercase[Math.floor(Math.random() * uppercase.length)],
                        numbers[Math.floor(Math.random() * numbers.length)],
                        symbols[Math.floor(Math.random() * symbols.length)]
                    ];

                    for (let i = password.length; i < length; i++) {
                        password.push(allChars[Math.floor(Math.random() * allChars.length)]);
                    }

                    for (let i = password.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [password[i], password[j]] = [password[j], password[i]];
                    }

                    return password.join('');
                },

                generate() {
                    const newPassword = this.generatePassword(this.passwordLength);
                    this.$refs.passwordInput.value = newPassword;
                    this.$refs.passwordInput.dispatchEvent(new Event('input'));
                },

                setProperty() {
                    const password = this.$refs.passwordInput.value;
                    if (password) {
                        @if ($model)
                            @if (is_array($model))
                                @foreach ($model as $property)
                                    $wire.set('{{ $property }}', password);
                                @endforeach
                            @else
                                $wire.set('{{ $model }}', password);
                            @endif
                        @endif
                        this.$dispatch('password-set');
                    }
                },

                async copyPassword() {
                    const password = this.$refs.passwordInput.value;
                    if (password) {
                        try {
                            await navigator.clipboard.writeText(password);
                            this.$dispatch('password-copied');
                        } catch (err) {
                            console.error('Failed to copy password:', err);
                        }
                    }
                }
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
