<?php
namespace Sgpatil\Orientdb\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Sgpatil\Orientdb\Connection as Connection;

class CreateOrientdbMigration extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'orient:create';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'To create class for Orientdb.';
        
        protected $client;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(Connection $connection)
	{       $grammer = new \Sgpatil\Orientdb\Query\Grammars\Grammar();
		parent::__construct();
                $this->client = $connection;
                $this->builder = new \Sgpatil\Orientdb\Query\Builder($connection, $grammer);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
            $node = $this->builder->createClass($this->argument('name'));
            // echo $this->argument('name');
            $this->info('Class created successfully.');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'class name'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('extends', null, InputOption::VALUE_OPTIONAL, 'To extend subclass', null),
		);
	}

}
