<?php

namespace JayDeeIO\CoreuiLaravel\Console;

use RuntimeException;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coreui:install {--pro : Indicates that CoreUI "pro" version should be installed}
                                           {--no-users : Indicates if users support should NOT be installed}
                                           {--admin : Indicates if admin users support should be installed}
                                           {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the CoreUI (Pro) / Vue.js components and resources';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if ($this->option('admin') and $this->option('no-users')) {
            $this->components->error('Invalid options. \'--admin\' option depends on user support (remove \'--no-users\' flag)');

            return 1;
        }

        // Publish...
        $this->publish([
            'coreui',
        ]);

        // Patch some files for 'pro' version
        $this->patchProVersion([
            resource_path('js/app.js'),
            resource_path('js/Pages/Welcome.vue'),
            base_path('vite.config.js'),
        ]);

        // Cleanup
        $this->cleanupFilesAndDirectories([
            resource_path('css'),
            resource_path('views/welcome.blade.php'),
        ]);

        $this->installInertiaStack();

        $this->line('');
        $this->components->info('JayDee\'s scaffolding installed successfully.');
    }

    /**
     * Publish all specified tags
     *
     * @param  array  $tags
     * @return bool
     */
    protected function publish(array $tags) {
        foreach ($tags as $tag) {
            $this->callSilent('vendor:publish', ['--tag' => $tag, '--force' => true]);
        }
    }

    /**
     * Remove files and directories
     *
     * @param  array  $files  Files to remove
     * @return bool
     */
    protected function cleanupFilesAndDirectories(array $files) {
        $filesytem = new Filesystem;
        foreach ($files as $file) {
            if($filesytem->isDirectory($file))
                $filesytem->deleteDirectory($file);
            else
                $filesytem->delete($file);
        }

    }

    /**
     * Install the Inertia stack into the application.
     *
     * @return bool
     */
    protected function installInertiaStack()
    {
        // Install Inertia...
        if (! $this->requireComposerPackages('inertiajs/inertia-laravel', 'tightenco/ziggy')) {
            return false;
        }

        // Install NPM Development packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@inertiajs/vue3' => '*',
                '@vitejs/plugin-vue' => '*',
                'vue' => '*',
                'sass' => '*',
            ] + $packages;
        }, true);

        // Install NPM packages...
        $devSuffix = $this->option('pro') ? "-pro" : "";
        $this->updateNodePackages(function ($packages) use ($devSuffix) {
            return [
                '@coreui/coreui'.$devSuffix => '*',
                '@coreui/vue'.$devSuffix => '*',
                '@coreui/icons' => '*',
                '@coreui/icons-vue' => '*',
                '@coreui/vue-chartjs' => '*',
            ] + $packages;
        }, false);

        // Middleware...
        (new Process([$this->phpBinary(), 'artisan', 'inertia:middleware', 'HandleInertiaRequests', '--force'], base_path()))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            });

        $this->installMiddleware([
            '\App\Http\Middleware\HandleInertiaRequests::class',
            '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class',
        ]);

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install']);
        } else {
        $this->runCommands(['npm install']);
        }

        return true;
    }

    /**
     * Install the given middleware names into the application.
     *
     * @param  array|string  $name
     * @param  string  $group
     * @param  string  $modifier
     * @return void
     */
    protected function installMiddleware($names, $group = 'web', $modifier = 'append')
    {
        $bootstrapApp = file_get_contents(base_path('bootstrap/app.php'));

        $names = collect(Arr::wrap($names))
            ->filter(fn ($name) => ! Str::contains($bootstrapApp, $name))
            ->whenNotEmpty(function ($names) use ($bootstrapApp, $group, $modifier) {
                $names = $names->map(fn ($name) => "$name")->implode(','.PHP_EOL.'            ');

                $bootstrapApp = str_replace(
                    '->withMiddleware(function (Middleware $middleware) {',
                    '->withMiddleware(function (Middleware $middleware) {'
                        .PHP_EOL."        \$middleware->$group($modifier: ["
                        .PHP_EOL."            $names,"
                        .PHP_EOL.'        ]);'
                        .PHP_EOL,
                    $bootstrapApp,
                );

                file_put_contents(base_path('bootstrap/app.php'), $bootstrapApp);
            });
    }

    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  mixed  $packages
     * @return bool
     */
    protected function requireComposerPackages($packages)
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = [$this->phpBinary(), $composer, 'require'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require'],
            is_array($packages) ? $packages : func_get_args()
        );

        return ! (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            });
    }

    /**
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param  bool  $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, $dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $path
     * @return void
     */
    protected function replaceInFile($search, $replace, $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Get the path to the appropriate PHP binary.
     *
     * @return string
     */
    protected function phpBinary()
    {
        return (new PhpExecutableFinder())->find(false) ?: 'php';
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function runCommands($commands)
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }
    /**
     * Patch files to remove "-pro" if '-pro' option is not specified
     *
     * @param  array  $files
     * @return void
     */
    protected function patchProVersion(array $files)
    {
        if($this->option('pro'))
            return;

        foreach ($files as $file) {
            (new Filesystem)->replaceInFile("-pro", "", $file);
        }
    }
}
