<?php 
namespace Sgpatil\Orientdb;

use Sgpatil\Orientphp\Exception as OrientException;

class QueryException extends OrientException {

    public function __construct($query, $bindings = array(), $exception = null)
    {
        if ($exception instanceof OrientException)
        {
            $message = $this->formatMessage($exception);

            parent::__construct($message);
        }

        elseif ($exception instanceof \Exception)
        {
            throw $exception;
        }
        else
        {
            parent::__construct($query);
        }
    }

    /**
     * Format the message that should be printed out for devs.
     *
     * @param  Sgpatil\Orientphp\Exception $exception
     * @return string
     */
    protected function formatMessage(OrientException $exception)
    {
        $data = $exception->getData();
        $exceptionName = isset($data['exception']) ? $data['exception'] .': ' : '';
        $message = isset($data['message']) ? $data['message'] : $exception->getMessage();
        return $exceptionName.$message;
    }
}
