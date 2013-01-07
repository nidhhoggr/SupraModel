<?php
/**
* @author: joseph persie
*
* this is the base model class to configure and extend
* the configuration method must be implemented to set the 
* identifier and the table name. Basic crud operations can be performed
* by this model specified by the driver
*/

abstract class SupraModel {

    private 
        $dbhost = "",
        $dbuser = "",
        $dbpassword = "",
        $dbname = "";

    function __construct($args) {
        $this->_setConnection($args);
        $this->setDriver($args['driver']);
        $this->_instantiateDriverModel();
        $this->configure();
    }

    abstract public function configure();

    private function _instantiateDriverModel() {

        $driverModelName = ucfirst($this->driver) . 'Model';

        require_once(dirname(__FILE__) . '/drivers/' . $this->driver . "/$driverModelName.class.php");

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

        $callResult = null;

        $interfaces = array('Selection','Modification');

        foreach($interfaces as $interface) {
            
            $methods = $this->getInterfaceMethods($interface);

            if(in_array($method,$methods)) {
                $handler = strtolower($interface) . 'Handler';
                $callResult = $this->driverModel->$handler->$method($args[0]);
                break;
            }
        }

        return $callResult;
    }

    private function getInterfaceMethods($interfaceName) {

        $methods = array(
            'Modification'=>array(
                'save'
            ),
            'Selection'=>array(
                'find',
                'findBy',
                'findOneBy',
                'getQuery'
            ),
            'DriverModel'=>array(
                'setDebugMode',
                'getDebugMode',
                'setTable',
                'getTable',
                'setTableIndentifier',
                'getTableIndentifier'
            )
        );

        return $methods[$interfaceName];
    }

    public function setDriver($driver) {
        
        $this->driver = $driver;
    }
}
