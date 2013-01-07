<?php
/**
* @author: joseph persie
*
* this is the base model class to configure and extend
* the configuration method must be implemented to set the 
* identifier and the table name. Basic crud operations can be performed
* by this model specified by the driver
*/

abstract class BaseModel {

    private 
        $dbhost = "",
        $dbuser = "",
        $dbpassword = "",
        $dbname = "";

    function __construct($args) {
        $this->_setConnection($args);
        $this->setDriver($args['driver']);
        $this->instantiateDriverModel();
        $this->configure();
    }

    abstract protected function configure();

    private function _instantiateDriverModel() {

        $driverModelName = ucfirst($this->driver) . 'Model';

        $this->driverModel = new $driverModelName($this->dbname,$this->dbhost,$this->dbuser,$this->dbpassword);
    }

    private function _setConnection($args) {

	$array_vars = array('dbname','dbhost','dbuser','dbpassword');

	foreach($array_vars as $av) {
	    if(empty($args[$av]))
	        die("Must provide all 4 paramaters for a db connection");

            $this->$av = $args[$av];
	}
    }

    public function __call($method,$args) {

        $args = implode(', ', $args);
 
        $interfaces = array('Selection','Modification');

        foreach($interfaces as $interface) {
            $methods = get_class_methods($interface);

            if(in_array($method,$methods)) {
                $handler = strtolower($interface) . 'Handler';
                $this->driverModel->$handler->$method($args);
                break;
            }
        }
    }

    public function setDriver() {
        
        $this->driver = $driver;
    }
}
