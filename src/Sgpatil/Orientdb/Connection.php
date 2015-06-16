<?php

namespace Sgpatil\Orientdb;

use DateTime,
    Closure;
use Sgpatil\Orientdb\Query\Builder;
use Sgpatil\Orientdb\QueryException;
use Sgpatil\Orientphp\Cypher\Query as CypherQuery;
use Sgpatil\Orientphp\Batch\Query as BatchQuery;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Doctrine\OrientDB\Binding\HttpBinding as Binding;
use Doctrine\OrientDB\Binding\BindingParameters as BindingParameters;
use Doctrine\DBAL\DriverManager;

use Sgpatil\Orientphp\Client as OriClient;
/**
 * Description of Connection
 *
 * @author sumit
 */
class Connection extends IlluminateConnection {

    /**
     * The Orientdb Doctrine client connection
     */
    protected $orient;

    /**
     * The Orientdb database transaction
     *
     * @var
     */
    protected $transaction;

    protected $schemaGrammar="pathofschemagrammar";
    /**
     * Default connection configuration parameters
     *
     * @var array
     */
    protected $defaults = array(
        'host' => 'localhost',
        'port' => 2480,
        'database' => 'demo',
        'username' => null,
        'password' => null
    );

    /**
     * The driver name
     *
     * @var string
     */
    protected $driverName = 'orientdb';

    /**
     * Create a new database connection instance
     *
     * @param array $config The database connection configuration
     */
    public function __construct(array $config = array()) {

        $this->config = $config;

        // activate and set the database client connection
        $this->orient = $this->createConnection();
    }

    /**
     * Create a new Orientdb client
     *
     * @return
     */
    public function createConnection() {

        //exit('testing');
        /*
        // below code is used to create connection usinf Orientdb-odm
        $parameters = BindingParameters::create($this->config);
        $orient = new Binding($parameters);
        return $orient;
         */
        $client = new OriClient($this->getHost(), $this->getPort(), $this->getDatabase());
        $client->getTransport()->setAuth($this->getUsername(), $this->getPassword());
        return $client;
    }
    
    public function make(array $config, $name = null)
	{
        $this->createConnection();
        return $this;
	}

    /**
     * Get the currenty active database client
     *
     * @return
     */
    public function getClient() {
        return $this->orient;
    }

    /**
     * Set the client responsible for the
     * database communication
     *
     * @param
     */
    public function setClient(NeoClient $client) {
        $this->orient = $client;
    }

    /**
     * Get the connection host
     *
     * @return string
     */
    public function getHost() {
        return $this->getConfig('host', $this->defaults['host']);
    }

    /**
     * Get the connection port
     *
     * @return int|string
     */
    public function getPort() {
        return $this->getConfig('port', $this->defaults['port']);
    }

    /**
     * Get the connection username
     * @return int|string
     */
    public function getUsername() {
        return $this->getConfig('username', $this->defaults['username']);
    }

    /**
     * Get the connection password
     * @return int|strings
     */
    public function getPassword() {
        return $this->getConfig('password', $this->defaults['password']);
    }
    
    /**
     * Get the database name
     * @return strings
     */
    public function getDatabase() {
        return $this->getConfig('database', $this->defaults['database']);
    }

    /**
     * Get an option from the configuration options.
     *
     * @param  string   $option
     * @param  mixed    $default
     * @return mixed
     */
    public function getConfig($option, $default = null) {
        return array_get($this->config, $option, $default);
    }

    /**
     * Get the  driver name.
     *
     * @return string
     */
    public function getDriverName() {
        return $this->driverName;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function select($query, $bindings = array()) {
        return $this->run($query, $bindings, function(self $me, $query, array $bindings) {
                    if ($me->pretending())
                        return array();
                    // For select statements, we'll simply execute the query and return an array
                    // of the database result set. Each element in the array will be a single
                    // node from the database, and will either be an array or objects.
                     $statement = $me->getBatchQuery($query, $bindings);
                    return $statement->getResultSet();
                    
                    // $statement = $me->getCypherQuery($query, $bindings);
                    // return $statement->getResultSet();

                });
    }
    
    
    /**
     * Run a insert statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function insert($query, $bindings = array()) {

        return $this->run($query, $bindings, function(self $me, $query, array $bindings) {
                    if ($me->pretending())
                        return array();

                    // For select statements, we'll simply execute the query and return an array
                    // of the database result set. Each element in the array will be a single
                    // node from the database, and will either be an array or objects.
                    $statement = $me->getBatchQuery($query, $bindings);
                    return $statement->getResultSet();

                });
    }

    /**
     * Run a Cypher statement and get the number of nodes affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = array()) {
        return $this->run($query, $bindings, function(self $me, $query, array $bindings) {
                    if ($me->pretending())
                        return 0;

                    // For update or delete statements, we want to get the number of rows affected
                    // by the statement and return that back to the developer. We'll first need
                    // to execute the statement and then we'll use CypherQuery to fetch the affected.
                    $statement = $me->getCypherQuery($query, $bindings);

                    return $statement->getResultSet();
                });
    }

    /**
     * Execute a Cypher statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return
     */
    public function statement($query, $bindings = array(), $rawResults = false) {
        return $this->run($query, $bindings, function(self $me, $query, array $bindings) use($rawResults) {
                    if ($me->pretending())
                        return true;
                    $statement = $me->getCypherQuery($query, $bindings);

                    $result = $statement->getResultSet();

                    return ($rawResults === true) ? $result : $result instanceof ResultSet;
                });
    }

    /**
     * Make a query out of a Cypher statement
     * and the bindings values
     *
     * @param  string  $query
     * @param  array  $bindings
     */
    public function getCypherQuery($query, array $bindings) {
        return new CypherQuery($this->getClient(), $query, $this->prepareBindings($bindings));
    }
    
    /**
     * Make a query out of a Batch statement
     * and the bindings values
     *
     * @param  string  $query
     * @param  array  $bindings
     */
    public function getBatchQuery($query, array $bindings) {
        return new BatchQuery($this->getClient(), $query, $this->prepareBindings($bindings));
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings) {
        $grammar = $this->getQueryGrammar();

        $prepared = array();

        foreach ($bindings as $key => $binding) {
            // The bindings are collected in a little bit different way than
            // Eloquent, we will need the key name in order to know where to replace
            // the value using the Orientdb client.
            $value = $binding;

            // We need to get the array value of the binding
            // if it were mapped
            if (is_array($value)) {
                // There are different ways to handle multiple
                // bindings vs. single bindings as values.
                $value = array_values($value);
            }

            // We need to transform all instances of the DateTime class into an actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.

            if ($value instanceof DateTime) {
                $binding = $value->format($grammar->getDateFormat());
            }

            $property = is_array($binding) ? key($binding) : $key;

            // We will set the binding key and value, then
            // we replace the binding property of the id (if found)
            // with a _nodeId instead since the client
            // will not accept replacing "id(n)" with a value
            // which have been previously processed by the grammar
            // to be _nodeId instead.
            if (!is_array($binding)) {
                $binding = [$binding];
            }

            foreach ($binding as $property => $real) {
                // We should not pass any numeric key-value items since the Orientdb client expects
                // a JSON map parameters.
                if (is_numeric($property)) {
                    $property = (!is_numeric($key)) ? $key : 'id';
                }

                if ($property == 'id')
                    $property = $grammar->getIdReplacement($property);

                $prepared[$property] = $real;
            }
        }

        return $prepared;
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \Sgpatil\Orientdb\Query\Grammars\CypherGrammar
     */
    public function getQueryGrammar() {
        if (!$this->queryGrammar) {
            $this->useDefaultQueryGrammar();
        }

        return $this->queryGrammar;
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Sgpatil\Orientdb\Query\Grammars\CypherGrammar
     */
    protected function getDefaultQueryGrammar() {
        return new Query\Grammars\CypherGrammar;
    }

    /**
     * A binding should always be in an associative
     * form of a key=>value, otherwise we will not be able to
     * consider it a valid binding and replace its values in the query.
     * This function validates whether the binding is valid to be used.
     *
     * @param  array $binding
     * @return boolean
     */
    public function isBinding(array $binding) {
        if (!empty($binding)) {
            // A binding is valid only when the key is not a number
            $keys = array_keys($binding);

            return !is_numeric(reset($keys));
        }

        return false;
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction() {
        ++$this->transactions;

        if ($this->transactions == 1) {
            $this->transaction = $this->orient->beginTransaction();
        }

        $this->fireConnectionEvent('beganTransaction');
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit() {
        if ($this->transactions == 1)
            $this->transaction->commit();

        --$this->transactions;

        $this->fireConnectionEvent('committed');
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack() {
        if ($this->transactions == 1) {
            $this->transactions = 0;

            $this->transaction->rollBack();
        } else {
            --$this->transactions;
        }

        $this->fireConnectionEvent('rollingBack');
    }

    /**
     * Begin a fluent query against a database table.
     * In Orientdb's terminologies this is a node.
     *
     * @param  string  $table
     * @return \Sgpatil\Orientdb\Query\Builder
     */
    public function table($table) {
        $query = new Builder($this, $this->getQueryGrammar());

        return $query->from($table);
    }

    /**
     * Run a Cypher statement and log its execution context.
     *
     * @param  string   $query
     * @param  array    $bindings
     * @param  Closure  $callback
     * @return mixed
     *
     * @throws QueryException
     */
    protected function run($query, $bindings, Closure $callback) {
        $start = microtime(true);

        // To execute the statement, we'll simply call the callback, which will actually
        // run the Cypher against the Orientdb connection. Then we can calculate the time it
        // took to execute and log the query Cypher, bindings and time in our memory.
        try {
            $result = $callback($this, $query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with Cypher, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (\Exception $e) {
            throw new QueryException($query, $bindings, $e);
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $time = $this->getElapsedTime($start);

        $this->logQuery($query, $bindings, $time);

        return $result;
    }
    
    /**
	 * Get a schema builder instance for the connection.
	 *
	 * @return \Illuminate\Database\Schema\Builder
	 */
	public function getSchemaBuilder()
	{
		if (is_null($this->schemaGrammar)) { $this->useDefaultSchemaGrammar(); }

		return new Schema\OrientdbBuilder($this);
	}
        
        public function getSchemaGrammar()
	{
		 return new Schema\Grammars\OrientdbGrammar();
	}


}
