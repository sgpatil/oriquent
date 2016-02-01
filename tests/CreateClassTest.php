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
        
    }
    public function testCreateClass() {
        $connection = $this->getConnection('orientdb'); 
        $query = new Builder($connection);
        $query->create('testusers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
		});
    }
    
}
