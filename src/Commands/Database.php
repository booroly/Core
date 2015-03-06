<?php
namespace TypiCMS\Commands;

use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Schema;
use Symfony\Component\Console\Input\InputArgument;

class Database extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'typicms:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set database credentials in .env file";

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new key generator command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $contents = $this->getKeyFile();

        $dbName = $this->argument('database');
        $dbUserName = $this->ask('What is your MySQL username?');
        $dbPassword = $this->secret('What is your MySQL password?');

        // Update DB credentials in .env file.
        $search = [
            '/(' . preg_quote('DB_DATABASE=') . ')(.*)/',
            '/(' . preg_quote('DB_USERNAME=') . ')(.*)/',
            '/(' . preg_quote('DB_PASSWORD=') . ')(.*)/',
        ];
        $replace = [
            '$1' . $dbName,
            '$1' . $dbUserName,
            '$1' . $dbPassword,
        ];
        $contents = preg_replace($search, $replace, $contents);

        if (! $contents) {
            throw new Exception('Error while writing credentials to .env file.');
        }

        // Set DB username and password in config
        $this->laravel['config']['database.connections.mysql.username'] = $dbUserName;
        $this->laravel['config']['database.connections.mysql.password'] = $dbPassword;

        // Clear DB name in config
        $this->laravel['config']['database.connections.mysql.database'] = '';

        // Create database if not exists
        DB::connection()->getPdo()->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '`');
        DB::connection()->getPdo()->exec('USE `' . $dbName . '`');

        // Set DB name in config
        $this->laravel['config']['database.connections.mysql.database'] = $dbName;
        
        $this->error($this->laravel['config']['local.database.connections.mysql.database']);

        // Migrate DB
        if (Schema::hasTable('migrations')) {
            $this->error('A migrations table was found in database ['.$dbName.'], no migration and seed were done.');
        } else {
            $this->call('migrate');
            $this->call('db:seed');
        }

        // Write to .env
        $this->files->put('.env', $contents);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('database', InputArgument::REQUIRED, 'The database name'),
        );
    }

    /**
     * Get the key file and contents.
     *
     * @return string
     */
    protected function getKeyFile()
    {
        return $this->files->get('.env');
    }
}
