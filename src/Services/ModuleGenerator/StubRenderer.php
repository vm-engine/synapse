<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ModuleGenerator;

use Illuminate\Filesystem\Filesystem;

class StubRenderer
{
    protected Filesystem $files;

    protected string $stubsPath;

    public function __construct()
    {
        $this->files = new Filesystem;
        $this->stubsPath = __DIR__.'/../../../stubs/module-generator';
    }

    /**
     * Render stub with variables
     */
    public function render(string $stubName, array $variables): string
    {
        $content = $this->getStub($stubName);

        return $this->replaceVariables($content, $variables);
    }

    /**
     * Get stub content
     */
    protected function getStub(string $name): string
    {
        // Add .stub extension if not present
        if (! str_ends_with($name, '.stub')) {
            $name .= '.stub';
        }

        $stubPath = $this->stubsPath.'/'.$name;

        if (! $this->files->exists($stubPath)) {
            throw new \InvalidArgumentException("Stub file not found: {$stubPath}");
        }

        return $this->files->get($stubPath);
    }

    /**
     * Replace variables in stub content
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Handle both {{ key }} and {{key}} patterns
            // Skip non-scalar values or boolean values
            if (! is_scalar($value) || is_bool($value)) {
                continue;
            }
            $content = str_replace(
                ['{{ '.$key.' }}', '{{'.$key.'}}'],
                (string) $value,
                $content
            );
        }

        return $content;
    }

    /**
     * Get list of available stubs
     */
    public function getAvailableStubs(): array
    {
        if (! $this->files->isDirectory($this->stubsPath)) {
            return [];
        }

        $stubs = [];
        $files = $this->files->allFiles($this->stubsPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'stub') {
                $stubs[] = str_replace($this->stubsPath.'/', '', $file->getPathname());
            }
        }

        return $stubs;
    }

    /**
     * Check if a stub exists
     */
    public function stubExists(string $name): bool
    {
        if (! str_ends_with($name, '.stub')) {
            $name .= '.stub';
        }

        return $this->files->exists($this->stubsPath.'/'.$name);
    }

    /**
     * Get stubs path
     */
    public function getStubsPath(): string
    {
        return $this->stubsPath;
    }

    /**
     * Set custom stubs path (useful for testing)
     */
    public function setStubsPath(string $path): self
    {
        $this->stubsPath = $path;

        return $this;
    }
}
