<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use VmEngine\Synapse\Services\ModuleGenerator\ModuleGeneratorService;
use VmEngine\Synapse\Services\ModuleGenerator\NamespaceResolver;
use VmEngine\Synapse\Services\ModuleGenerator\StubRenderer;

beforeEach(function () {
    $this->modulePath = sys_get_temp_dir().'/synapse-observer-test-'.uniqid();
    mkdir($this->modulePath.'/blog/src/Observers', 0755, true);

    $this->generator = new ModuleGeneratorService(new NamespaceResolver, new StubRenderer);
    $this->generator->setBasePath($this->modulePath);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->modulePath);
});

test('observer stub has no unreplaced placeholders and imports the given model', function () {
    $path = $this->generator->generateComponent('observer', 'PostObserver', [
        'module' => 'blog',
        'model' => 'Post',
    ]);

    $content = file_get_contents($path);

    expect($content)->not->toContain('{{')
        ->and($content)->toContain('use SynApps\Modules\Blog\Models\Post;')
        ->and($content)->toContain('class PostObserver')
        ->and($content)->toContain('function created(Post $post): void');
});

test('observer stub falls back to the base Eloquent Model when no model is given', function () {
    $path = $this->generator->generateComponent('observer', 'AuditObserver', [
        'module' => 'blog',
    ]);

    $content = file_get_contents($path);

    expect($content)->not->toContain('{{')
        ->and($content)->toContain('use Illuminate\Database\Eloquent\Model;')
        ->and($content)->toContain('function created(Model $model): void');
});
