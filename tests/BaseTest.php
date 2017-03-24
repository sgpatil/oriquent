<?php

namespace Sgpatil\Orientdb;

use Sgpatil\Orientdb\Connection;

class BaseTest extends \PHPUnit_Framework_TestCase {

    public function __construct()
    {
        parent::__construct();

        // load custom configuration file
        $this->dbConfig = require 'config/database.php';
    }
    
     public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }
    
    protected function getConnection($config = null) {
        $connection = is_null($config) ? $this->dbConfig['connections']['default'] :
                $this->dbConfig['connections'][$config];

        $client =  new Connection($connection); // Create Connection object
        $client->getClient()->getServerInfo(); // Check If Server is connected or not
        return $client;
    }

    public function testConnection()
    {
       
    }
}
