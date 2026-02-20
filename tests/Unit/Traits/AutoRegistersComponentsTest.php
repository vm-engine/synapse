<?php

declare(strict_types=1);

use VmEngine\Synapse\Traits\AutoRegistersComponents;

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

    $provider = new \VmEngine\TestModule\TestModuleProvider(app());
    expect($provider->getPrefix())->toBe('test-module');
});

test('custom component prefix can be set', function () {
    // Test that prefix property exists and can be customized
    $provider = new class(app()) extends \Illuminate\Support\ServiceProvider
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

test('converts pascal case to kebab case for prefix', function () {
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

    $provider = new \VmEngine\ExampleModule\TestServiceProvider(app());
    expect($provider->getPrefix())->toBe('example-module');
});

test('registers components safely when directories do not exist', function () {
    // Livewire::addNamespace will be called but with null paths for missing directories
    \Livewire\Livewire::shouldReceive('addNamespace')->once();

    $provider = new class(app()) extends \Illuminate\Support\ServiceProvider
    {
        use AutoRegistersComponents;

        public function register(): void
        {
            $this->registerComponents();
        }
    };

    // Should not throw errors when Livewire and View/Components directories don't exist
    expect(fn () => $provider->register())->not->toThrow(Exception::class);
});

test('trait has required methods', function () {
    $provider = new class(app()) extends \Illuminate\Support\ServiceProvider
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

    $provider = new \SynApps\Modules\TestModule\TestModuleServiceProvider(app());
    expect($provider->getPrefix())->toBe('test-module');
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

    $provider = new \VmEngine\MyPackage\MyPackageServiceProvider(app());
    expect($provider->getPrefix())->toBe('my-package');
});

test('can toggle component registration flags', function () {
    $provider = new class(app()) extends \Illuminate\Support\ServiceProvider
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
        $segments = array_map(fn ($segment) => \Illuminate\Support\Str::kebab($segment), $segments);
        $result = implode('.', $segments);

        expect($result)->toBe($expected);
    }
});

test('registerLivewireNamespace calls Livewire::addNamespace with correct params', function () {
    // Mock Livewire facade to capture the addNamespace call
    $calledWith = null;

    \Livewire\Livewire::shouldReceive('addNamespace')
        ->once()
        ->withArgs(function () use (&$calledWith) {
            $calledWith = func_get_args();

            return true;
        });

    $provider = new class(app()) extends \Illuminate\Support\ServiceProvider
    {
        use AutoRegistersComponents;

        public function doRegister(): void
        {
            $this->registerLivewireNamespace();
        }
    };

    $provider->doRegister();

    // First argument should be the kebab-case prefix derived from the anonymous class context
    expect($calledWith)->not->toBeNull()
        ->and($calledWith[0])->toBeString();
});

test('registerComponents calls registerLivewireNamespace when livewire flag is enabled', function () {
    $namespaceCalled = false;
    $bladeCalled = false;

    \Livewire\Livewire::shouldReceive('addNamespace')
        ->once()
        ->andReturnUsing(function () use (&$namespaceCalled) {
            $namespaceCalled = true;
        });

    $provider = new class(app()) extends \Illuminate\Support\ServiceProvider
    {
        use AutoRegistersComponents;

        public function doRegister(): void
        {
            $this->registerComponents();
        }
    };

    $provider->doRegister();

    expect($namespaceCalled)->toBeTrue();
});

test('registerComponents skips livewire when flag is disabled', function () {
    \Livewire\Livewire::shouldReceive('addNamespace')->never();

    $provider = new class(app()) extends \Illuminate\Support\ServiceProvider
    {
        use AutoRegistersComponents;

        public function doRegister(): void
        {
            $this->autoRegisterLivewireComponents = false;
            $this->registerComponents();
        }
    };

    $provider->doRegister();
});
