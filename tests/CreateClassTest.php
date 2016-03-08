<?php
namespace Sgpatil\Orientdb;
use Sgpatil\Orientdb\Schema\Builder;
use Sgpatil\Orientdb\Schema\Blueprint;

/**
 * Description of CreateClassTest
 *
 * @author sumit
 */
class CreateClassTest extends BaseTest {
    public function __construct() {
        parent::__construct();
        $this->con  = $this->getConnection('orientdb');
        $this->query = new Builder($this->con);
        
    }
    public function testCreateClass() {
        $res = $this->query->create('testusers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
		});
    }
    
    public function testDropClass() {
        $this->query->drop('testusers');
    }
    
}
