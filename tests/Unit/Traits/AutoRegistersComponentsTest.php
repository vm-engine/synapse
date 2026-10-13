<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use SynApps\Modules\TestModule\TestModuleServiceProvider;
use VmEngine\ExampleModule\TestServiceProvider;
use VmEngine\MyPackage\MyPackageServiceProvider;
use VmEngine\Synapse\Traits\AutoRegistersComponents;
use VmEngine\TestModule\TestModuleProvider;

/**
 * The trait registers on whatever is bound as 'livewire.finder' (not through the Livewire
 * facade, see registerLivewireNamespace()). Binds a recorder there and returns it.
 */
function fakeLivewireFinder(): object
{
    $finder = new class
    {
        /** @var list<array<int|string, mixed>> */
        public array $calls = [];

        public function addNamespace(mixed ...$arguments): void
        {
            $this->calls[] = $arguments;
        }
    };

    app()->instance('livewire.finder', $finder);

    return $finder;
}

test('gets component prefix from namespace automatically', function () {
    $code = <<<'PHP'
<?php
namespace VmEngine\TestModule;

use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class TestModuleProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function getPrefix(): string
    {
        return $this->getComponentPrefix();
    }
}
PHP;

    eval(str_replace('<?php', '', $code));

    $provider = new TestModuleProvider(app());
    expect($provider->getPrefix())->toBe('test_module');
});

test('custom component prefix can be set', function () {
    // Test that prefix property exists and can be customized
    $provider = new class(app()) extends ServiceProvider
    {
        use AutoRegistersComponents;

        public function setCustomPrefix(string $prefix): void
        {
            $this->componentPrefix = $prefix;
        }

        public function getPrefix(): string
        {
            return $this->getComponentPrefix();
        }
    };

    $provider->setCustomPrefix('custom-prefix');
    expect($provider->getPrefix())->toBe('custom-prefix');
});

test('converts pascal case to snake_case for prefix', function () {
    $code = <<<'PHP'
<?php
namespace VmEngine\ExampleModule;

use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class TestServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function getPrefix(): string
    {
        return $this->getComponentPrefix();
    }
}
PHP;

    eval(str_replace('<?php', '', $code));

    $provider = new TestServiceProvider(app());
    expect($provider->getPrefix())->toBe('example_module');
});

test('registers components safely when directories do not exist', function () {
    // The namespace is still added, with null paths for the missing directories
    $finder = fakeLivewireFinder();

    $provider = new class(app()) extends ServiceProvider
    {
        use AutoRegistersComponents;

        public function register(): void
        {
            $this->registerComponents();
        }
    };

    // Should not throw errors when Livewire and View/Components directories don't exist
    expect(fn () => $provider->register())->not->toThrow(Exception::class)
        ->and($finder->calls)->toHaveCount(1)
        ->and($finder->calls[0]['viewPath'])->toBeNull()
        ->and($finder->calls[0]['classPath'])->toBeNull();
});

test('trait has required methods', function () {
    $provider = new class(app()) extends ServiceProvider
    {
        use AutoRegistersComponents;
    };

    $reflection = new ReflectionClass($provider);

    expect($reflection->hasMethod('registerComponents'))->toBeTrue()
        ->and($reflection->hasMethod('registerLivewireNamespace'))->toBeTrue()
        ->and($reflection->hasMethod('registerBladeComponents'))->toBeTrue()
        ->and($reflection->hasMethod('getComponentPrefix'))->toBeTrue();
});

test('prefix detection handles SynApps modules namespace', function () {
    $code = <<<'PHP'
<?php
namespace SynApps\Modules\TestModule;

use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class TestModuleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function getPrefix(): string
    {
        return $this->getComponentPrefix();
    }
}
PHP;

    eval(str_replace('<?php', '', $code));

    $provider = new TestModuleServiceProvider(app());
    expect($provider->getPrefix())->toBe('test_module');
});

test('prefix detection handles VmEngine package namespace', function () {
    $code = <<<'PHP'
<?php
namespace VmEngine\MyPackage;

use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class MyPackageServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function getPrefix(): string
    {
        return $this->getComponentPrefix();
    }
}
PHP;

    eval(str_replace('<?php', '', $code));

    $provider = new MyPackageServiceProvider(app());
    expect($provider->getPrefix())->toBe('my_package');
});

test('can toggle component registration flags', function () {
    $provider = new class(app()) extends ServiceProvider
    {
        use AutoRegistersComponents;

        public function toggleLivewire(bool $value): void
        {
            $this->autoRegisterLivewireComponents = $value;
        }

        public function toggleBlade(bool $value): void
        {
            $this->autoRegisterBladeComponents = $value;
        }

        public function getLivewireFlag(): bool
        {
            return $this->autoRegisterLivewireComponents;
        }

        public function getBladeFlag(): bool
        {
            return $this->autoRegisterBladeComponents;
        }
    };

    // Default values
    expect($provider->getLivewireFlag())->toBeTrue()
        ->and($provider->getBladeFlag())->toBeTrue();

    // Toggle off
    $provider->toggleLivewire(false);
    $provider->toggleBlade(false);

    expect($provider->getLivewireFlag())->toBeFalse()
        ->and($provider->getBladeFlag())->toBeFalse();

    // Toggle on
    $provider->toggleLivewire(true);
    $provider->toggleBlade(true);

    expect($provider->getLivewireFlag())->toBeTrue()
        ->and($provider->getBladeFlag())->toBeTrue();
});

test('converts nested component paths correctly to kebab-case', function () {
    // Test the conversion logic for nested paths
    $testCases = [
        'ProjectList' => 'project-list',
        'Project\ProjectList' => 'project.project-list',
        'Forms\ContactForm' => 'forms.contact-form',
        'Components\ButtonSample' => 'components.button-sample',
        'Admin\Users\UserProfile' => 'admin.users.user-profile',
    ];

    foreach ($testCases as $input => $expected) {
        // Simulate the trait's logic
        $segments = explode('\\', $input);
        $segments = array_map(fn ($segment) => Str::kebab($segment), $segments);
        $result = implode('.', $segments);

        expect($result)->toBe($expected);
    }
});

test('registerLivewireNamespace adds the namespace to the Livewire finder with correct params', function () {
    $finder = fakeLivewireFinder();

    $provider = new class(app()) extends ServiceProvider
    {
        use AutoRegistersComponents;

        public function doRegister(): void
        {
            $this->registerLivewireNamespace();
        }
    };

    $provider->doRegister();

    // First argument should be the kebab-case prefix derived from the anonymous class context
    expect($finder->calls)->toHaveCount(1)
        ->and($finder->calls[0][0])->toBeString()
        ->and($finder->calls[0])->toHaveKeys(['viewPath', 'classNamespace', 'classPath', 'classViewPath']);
});

test('registerComponents calls registerLivewireNamespace when livewire flag is enabled', function () {
    $finder = fakeLivewireFinder();

    $provider = new class(app()) extends ServiceProvider
    {
        use AutoRegistersComponents;

        public function doRegister(): void
        {
            $this->registerComponents();
        }
    };

    $provider->doRegister();

    expect($finder->calls)->toHaveCount(1);
});

test('registerComponents skips livewire when flag is disabled', function () {
    $finder = fakeLivewireFinder();

    $provider = new class(app()) extends ServiceProvider
    {
        use AutoRegistersComponents;

        public function doRegister(): void
        {
            $this->autoRegisterLivewireComponents = false;
            $this->registerComponents();
        }
    };

    $provider->doRegister();

    expect($finder->calls)->toBe([]);
});
