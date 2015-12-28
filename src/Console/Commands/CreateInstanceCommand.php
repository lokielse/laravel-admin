<?php namespace Lokielse\Console\Console\Commands;

use File;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

class CreateInstanceCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'console:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a console instance.';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $name      = $this->argument('name');
        $force     = $this->option('force');
        $engine    = $this->option('engine');
        $basePath  = realpath(__DIR__ . '/../../..');
        $namespace = trim(config('console.namespace'));

        if ( ! $namespace) {
            $this->error("The config 'namespace' in 'config/console.php' can not be empty, general set it to 'admin'");
            exit;
        }

        if ( ! $name) {
            $this->error('The name can not be empty');
            exit;
        }

        if ( ! config("console.instances.{$name}")) {
            $this->warn("The config of instance '{$name}' is not exist, check the typo or add it to config/console.php first");
            exit;
        }

        if ( ! $engine) {
            $this->warn("The engine option is empty, use --engine");
            exit;
        }

        if ( ! is_dir($basePath . "/engines/{$engine}")) {
            $this->warn("The engine '$engine' is not support.");
            exit;
        }

        /**
         *
         */
        $finder = new Finder();
        $path   = $basePath . "/engines/{$engine}";

        $copies = [ ];
        foreach ($finder->name('*')->in($path) as $file) {
            /**
             * @var \SplFileInfo $file
             */
            if ($file->isFile()) {
                $relativePath = str_replace($basePath . "/engines/{$engine}/", '', $file->getRealPath());
                $dest         = base_path('resources/' . $relativePath);
                $dest         = $this->replaceKeyword($dest);
                if (is_file($dest) && ! $force) {
                    $this->warn(sprintf("The file '%s' exists, use '--force' option to overwrite it.", str_replace(base_path(), '', $dest)));
                    exit;
                }
                $copies[] = [ $file->getRealPath(), $dest ];
            }
        }

        foreach ($copies as $copy) {
            if ( ! is_dir(dirname($copy[1]))) {
                mkdir(dirname($copy[1]), 0755, true);
            }
            File::copy($copy[0], $copy[1]);
        }

        $this->warn("Done! run 'gulp default && gulp watch' to generate assets");
    }


    protected function replaceKeyword($content)
    {
        $namespace = trim(config('console.namespace'));
        $name      = strtolower($this->argument('name'));

        $replaced = preg_replace([
            '#_namespace_#',
            '#_name_#',
        ], [
            $namespace,
            $name,
        ], $content);

        return $replaced;
    }


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [ 'name', InputArgument::REQUIRED, 'The app name.' ],
        ];
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [ 'dir', 'd', InputOption::VALUE_OPTIONAL, 'The root directory of templates' ],
            [ 'force', 'f', InputOption::VALUE_NONE, 'Overwrite the exist files' ],
            [ 'engine', 'e', InputOption::VALUE_REQUIRED, "'sb-admin' or 'admin-lte'" ]
        ];
    }

}
