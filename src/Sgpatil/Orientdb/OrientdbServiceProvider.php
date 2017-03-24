<?php

namespace Sgpatil\Orientdb;

use Illuminate\Support\ServiceProvider;
use Sgpatil\Orientdb\Connection;
use Sgpatil\Orientdb\Migrations\DatabaseMigrationRepository;

class OrientdbServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {

        $this->app->register('Sgpatil\Orientdb\MigrationServiceProvider');

        $this->app['db']->extend('orientdb', function($config) {
            return new Connection($config);
        });

        $this->app->booting(function() {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Orientdb', 'Sgpatil\Orientdb\Eloquent\Model');
        });

        $this->app->bind('ConnectionResolverInterface', function($app) {
            $databases = $app['config']['database.connections'];
            $defaultConnection = $app['config']['database.default'];
            if($databases[$defaultConnection]['driver']!='orientdb') {
                $defaultConnection = $app['config']['database.default_nosql'];
            }
            $conn = new Connection($databases[$defaultConnection]);
            return new DatabaseManager($app, $conn);
        });

        $this->app->bind('MigrationRepositoryInterface', function($app) {
            $resolver = $this->app->make('ConnectionResolverInterface');
            $table = $app['config']['database.migrations'];
            return new DatabaseMigrationRepository($resolver, $table);
        });

        $this->app->bind('orientdb.database', function($app) {
            $databases = $app['config']['database.connections'];
            $defaultConnection = $app['config']['database.default'];
            if($databases[$defaultConnection]['driver']!='orientdb') {
                $defaultConnection = $app['config']['database.default_nosql'];
            }
            
            if(empty($defaultConnection)){
                throw new \Exception('Invalid database connection type. Please check DB_CONNECTION in database configuration file or in .env file.');
            }
            return new Connection($databases[$defaultConnection]);
        });

        $this->app->bind('CreateOrientdbMigration', function($app) {
            $database = $this->app->make('orientdb.database');
            return new CreateOrientdbMigration($database);
        });


        $CreateOrientdbMigration = $this->app->make('CreateOrientdbMigration');

        $this->commands('CreateOrientdbMigration');
    }

}
