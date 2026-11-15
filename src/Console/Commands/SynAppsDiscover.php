<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use UnexpectedValueException;

class SynAppsDiscover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover modules and dependencies, update composer.json/package.json autoload entries';

    /** @var array<string, mixed> */
    protected array $packageJson = [];

    /** @var array<string, mixed> */
    protected array $composerJson = [];

    /** @var array<string, string> */
    protected array $namespaces = [];

    protected int $npmDeps = 0;

    protected int $composerDeps = 0;

    protected int $npmDevDeps = 0;

    protected int $composerDevDeps = 0;

    /**
     * Composer packages whose constraint this run added or raised.
     *
     * @var list<string>
     */
    protected array $changedComposerPackages = [];

    /**
     * Shell commands from this run that exited non-zero.
     *
     * @var list<string>
     */
    protected array $failedCommands = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->packageJson = read_json_file(base_path('package.json'));
        $this->composerJson = read_json_file(base_path('composer.json'));

        $this->discoverSynapseModules();

        $this->discoverModuleDeps();

        $this->discoverSynAppsDeps();

        $this->informDiscoveries();

        $this->updateComposer();

        $this->updateNpm();

        $this->call('synapps:config');

        if ($this->npmDeps || $this->npmDevDeps) {
            $this->components->info('Compiling assets');
            $this->runShell(['npm', 'run', 'build']);
        }

        return $this->reportFailures();
    }

    private function discoverSynapseModules(): void
    {
        $moduleConfigPaths = glob(synapps_path('modules'.DS.'*'.DS.'module.json')) ?: [];

        foreach ($moduleConfigPaths as $moduleConfigPath) {
            $moduleConfig = read_json_file($moduleConfigPath);
            $modulePath = ltrim(str_replace(
                base_path(),
                '',
                dirname($moduleConfigPath)
            ));
            $moduleNamespace = 'SynApps\\Modules\\'.$moduleConfig['id_case']['pascal'].'\\';
            $dbNamespace = 'SynDB\\Modules\\'.$moduleConfig['id_case']['pascal'].'\\';
            // Always '/': Composer accepts it on every OS, so a composer.json committed from
            // Windows still autoloads (and is still discovered by synapps:config) on Linux.
            $autoloadBase = trim(str_replace('\\', '/', $modulePath), '/');
            $this->namespaces[$moduleNamespace] = $autoloadBase.'/src/';
            $this->namespaces[$dbNamespace.'Seeders\\'] = $autoloadBase.'/database/seeders/';
            $this->namespaces[$dbNamespace.'Factories\\'] = $autoloadBase.'/database/factories/';
            $this->components->info("Discovered Module: {$moduleConfig['name']} at {$modulePath}");

            $this->compareDependencies($moduleConfig['dependencies']);
        }
    }

    private function discoverModuleDeps(): void
    {
        $modulePackages = array_merge(
            glob(base_path('vendor/*/synmod-*/module.json')) ?: [],
            glob(base_path('vendor/*/synmodule-*/module.json')) ?: [],
            glob(base_path('vendor/*/synapps-*/module.json')) ?: [],
        );
        foreach ($modulePackages as $packageConfigPath) {
            $moduleConfig = read_json_file($packageConfigPath);

            $this->compareDependencies($moduleConfig['dependencies']);
        }
    }

    private function discoverSynAppsDeps(): void
    {
        $appsConfig = read_json_file(synapps_path('config/apps.json'));
        $this->compareDependencies($appsConfig['dependencies']);

        if (! file_exists(synapps_path('config/apps.dev.json'))) {
            file_put_contents(synapps_path('config/apps.dev.json'), '{}');
        }
        $appsDevConfig = read_json_file(synapps_path('config/apps.dev.json'));
        if (isset($appsDevConfig['dependencies'])) {
            $this->compareDependencies($appsDevConfig['dependencies']);
        }
    }

    private function informDiscoveries(): void
    {
        $this->components->info('Added '.count($this->namespaces).' Namespaces');
        if ($this->npmDeps) {
            $this->components->info('Discovered '.$this->npmDeps.' NPM Dependencies');
        }
        if ($this->composerDeps) {
            $this->components->info('Discovered '.$this->composerDeps.' Composer Dependencies');
        }
        if ($this->npmDevDeps) {
            $this->components->info('Discovered '.$this->npmDevDeps.' NPM Dev Dependencies');
        }
        if ($this->composerDevDeps) {
            $this->components->info('Discovered '.$this->composerDevDeps.' Composer Dev Dependencies');
        }
    }

    private function updateComposer(): void
    {
        // Clean up deleted module namespaces
        $existingAutoload = $this->composerJson['autoload']['psr-4'] ?? [];
        $cleanedAutoload = [];
        $removedCount = 0;

        foreach ($existingAutoload as $namespace => $path) {
            // Check if this is a SynApps or SynDB module namespace
            if (
                preg_match('/^SynApps\\\\Modules\\\\/', $namespace) ||
                preg_match('/^SynDB\\\\Modules\\\\/', $namespace)
            ) {
                // Check if this namespace is still in our discovered namespaces
                if (! isset($this->namespaces[$namespace])) {
                    // Module was deleted, skip this namespace
                    $this->components->warn("Removing deleted module namespace: {$namespace}");
                    $removedCount++;

                    continue;
                }
            }

            // Keep this namespace
            $cleanedAutoload[$namespace] = $path;
        }

        // Merge discovered namespaces with cleaned autoload
        $this->composerJson['autoload']['psr-4'] = array_merge(
            $cleanedAutoload,
            $this->namespaces
        );

        if ($removedCount > 0) {
            $this->components->info("Removed {$removedCount} namespace(s) from deleted modules");
        }

        $this->components->info('Updating composer.json');
        file_put_contents(
            base_path('composer.json'),
            json_encode($this->composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($this->changedComposerPackages !== []) {
            $packages = array_values(array_unique($this->changedComposerPackages));

            // Only the packages this run touched. A bare `composer update`
            // re-resolves the whole project, so an unrelated lock
            // inconsistency fails the run after Composer has already started
            // deleting packages out of vendor/.
            $this->runShell(array_merge(['composer', 'update'], $packages, ['--with-all-dependencies']));

            return;
        }

        $this->runShell(['composer', 'dump-autoload']);
    }

    /**
     * Run a command in the project root, streaming its output, and remember a
     * non-zero exit so reportFailures() can surface it at the end of the run.
     *
     * Using the Process facade rather than exec() keeps the exit code and the
     * output: exec() discarded both, which is how a failed `composer update`
     * used to pass unnoticed.
     *
     * @param  list<string>  $command
     */
    private function runShell(array $command): int
    {
        $printable = implode(' ', $command);

        $this->components->info('Running '.$printable);

        $result = Process::path(base_path())
            ->forever()
            ->run($command, function (string $type, string $output): void {
                $this->output->write($output);
            });

        $exitCode = $result->exitCode() ?? 1;

        if ($exitCode !== 0) {
            $this->failedCommands[] = $printable;
            $this->components->error('`'.$printable.'` exited with code '.$exitCode);
        }

        return $exitCode;
    }

    /**
     * Summarise any shell command that exited non-zero.
     *
     * The manifests have already been rewritten by the time this runs, so the
     * report names the failing command and says how to put an incomplete
     * vendor/ back.
     */
    private function reportFailures(): int
    {
        if ($this->failedCommands === []) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->error('synapps:discover finished with errors');

        foreach ($this->failedCommands as $failedCommand) {
            $this->line('  failed: '.$failedCommand);
        }

        $this->components->warn(
            'composer.json and package.json were already rewritten. If vendor/ is now incomplete, run '
            .'`composer install --no-scripts`, then re-link any vendor package you keep as a symlink.'
        );

        return self::FAILURE;
    }

    private function updateNpm(): void
    {
        if (empty($this->packageJson['dependencies'])) {
            $this->packageJson['dependencies'] = new \stdClass;
        }
        if (empty($this->packageJson['devDependencies'])) {
            $this->packageJson['devDependencies'] = new \stdClass;
        }

        if ($this->npmDeps || $this->npmDevDeps) {
            $this->components->info('Updating package.json');
            file_put_contents(
                base_path('package.json'),
                json_encode($this->packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->runShell(['npm', 'install']);
        }
    }

    /** @param  array<string, mixed>  $moduleDependecies */
    private function compareDependencies(array $moduleDependecies): void
    {
        $this->compareDeps('packageJson', 'dependencies', $moduleDependecies['npm']['main'] ?? [], 'npmDeps');
        $this->compareDeps('packageJson', 'devDependencies', $moduleDependecies['npm']['dev'] ?? [], 'npmDevDeps');
        $this->compareDeps('composerJson', 'require', $moduleDependecies['composer']['main'] ?? [], 'composerDeps', true);
        $this->compareDeps('composerJson', 'require-dev', $moduleDependecies['composer']['dev'] ?? [], 'composerDevDeps', true);
    }

    /** @param  array<string, mixed>  $toLoop */
    private function compareDeps(
        string $thisVar,
        string $thisVarKey,
        array $toLoop,
        string $counterVar,
        bool $isComposer = false,
    ): void {
        $collection = $this->{$thisVar}[$thisVarKey] ?? [];

        foreach ($toLoop as $package => $version) {
            $package = (string) $package;
            $version = (string) $version;

            if (isset($collection[$package])) {
                $wins = $isComposer
                    ? $this->composerConstraintWins($package, $version, (string) $collection[$package])
                    : Comparator::greaterThan($version, $collection[$package]);

                if (! $wins) {
                    continue;
                }
            }

            $collection[$package] = $version;
            $this->{$counterVar}++;

            if ($isComposer) {
                $this->changedComposerPackages[] = $package;
            }

            $this->components->info('Discovered Dependency: '.$package.' - '.$version);
        }

        $this->{$thisVar}[$thisVarKey] = $collection;
    }

    /**
     * Decide whether a module's Composer constraint should replace the one the
     * root manifest already declares.
     *
     * Constraints are compared on their parsed lower bound, so compound forms
     * (">=1.0", "^1.0|^2.0") order correctly instead of being compared as
     * strings. A branch constraint ("dev-main", "@dev", "1.x-dev") never
     * replaces a different constraint and is never replaced by one: moving a
     * package between a stable release and a branch is the host project's
     * decision, not a module's.
     */
    private function composerConstraintWins(string $package, string $moduleConstraint, string $rootConstraint): bool
    {
        if ($moduleConstraint === $rootConstraint) {
            return false;
        }

        if ($this->isDevConstraint($moduleConstraint) || $this->isDevConstraint($rootConstraint)) {
            $this->components->warn(
                $package.': module wants '.$moduleConstraint.', root requires '.$rootConstraint
                .'; leaving the root constraint alone because one of them is a branch constraint.'
            );

            return false;
        }

        $parser = new VersionParser;

        try {
            $moduleLowerBound = $parser->parseConstraints($moduleConstraint)->getLowerBound()->getVersion();
            $rootLowerBound = $parser->parseConstraints($rootConstraint)->getLowerBound()->getVersion();
        } catch (UnexpectedValueException) {
            $this->components->warn(
                $package.': cannot compare '.$moduleConstraint.' with the root\'s '.$rootConstraint
                .'; leaving the root constraint alone.'
            );

            return false;
        }

        return Comparator::greaterThan($moduleLowerBound, $rootLowerBound);
    }

    /**
     * Whether a constraint asks for a branch/dev version rather than a release.
     */
    private function isDevConstraint(string $constraint): bool
    {
        return str_contains($constraint, 'dev');
    }
}
