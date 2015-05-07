<?php

namespace SupraModel;

/**
 * SupraModel 
 * 
 * this is the base model class to configure and extend
 * the configuration method must be implemented to set the 
 * identifier and the table name. Basic crud operations can be performed
 * by this model specified by the driver
 *
 * @abstract
 * @package 
 * @version $id$
 * @copyright 
 * @author Joseph Persie <joseph@supraliminalsolutions.com> 
 * @license 
 */
abstract class SupraModel {

    private
        $dbhost = "",
        $dbuser = "",
        $dbpassword = "",
        $dbname = "";

    function __construct($args = array()) {

        if(!count($args))
        {
            $yaml_settings_file = dirname(__FILE__) . '/../config/config.yml';
            
            $json_settings_file = dirname(__FILE__) . '/../config/config.json';

            if(function_exists('yaml_parse_file') && file_exists($yaml_settings_file))
            {
                $args = yaml_parse_file($yaml_settings_file);
            }
            else if(file_exists($json_settings_file))
            {
                $args = json_decode(file_get_contents($json_settings_file),  true);
            }        
        }

        $this->_setConnection($args);
        
        $this->setDriver($args['driver']);
        
        $this->_instantiateDriverModel();
        
        $this->configure();
    }

    abstract public function configure();

    private function _instantiateDriverModel() {

        $driverName = ucfirst($this->driver);

        $driverModelName = $driverName . 'Model';

        $driverClassName = "SupraModel\\Drivers\\$driverName\\$driverModelName";

        $this->driverModel = new $driverClassName($this->dbname, $this->dbhost, $this->dbuser, $this->dbpassword);
    }

    private function _setConnection($args) {

        $array_vars = array('dbname','dbhost','dbuser','dbpassword');

        foreach($array_vars as $av) {
            if(!isset($args[$av]))
                die("Must provide all 4 paramaters for a db connection");

            $this->$av = $args[$av];
        }
    }

    public function __call($method,$args = array()) {

        $this->driverModel->reinitialize($this);

        $callResult = null;

        $methodFound = false;

        $interfaces = array('Selection','Modification');

        foreach($interfaces as $interface) {
            
            $methods = $this->getInterfaceMethods($interface);

            if(in_array($method,$methods)) {
                $methodFound = true;
                $handler = strtolower($interface) . 'Handler';
                $callResult = $this->_makeCall($this->driverModel->$handler, $method, $args);
                break;
            }
        }

        if(!$methodFound)
            $callResult = $this->_makeCall($this->driverModel, $method, $args);

        return $callResult;
    }

    private function _makeCall($obj, $method, $args) { 

      if(count($args) == 0)
        $callResult = $obj->$method();
      else if(count($args) == 1)
        $callResult = $obj->$method($args[0]);
      else if(count($args) == 2)
        $callResult = $obj->$method($args[0], $args[1]);
      else 
        Throw new Exception("callable proxy methods can obtain no more than 2 args");

      return $callResult;
    }


    public function __set($name,$value) {

        $driver_vars = array(
            'dbname',
            'dbhost',
            'dbuser',
            'dbpassword',
            'driver',
            'driverModel'
        );

        if(!in_array($name,$driver_vars)) {
            $this->driverModel->$name = $value;
        }
        else {
            $this->$name = $value;
        }
    }

    private function getInterfaceMethods($interfaceName) {

        $methods = array(
            'Modification'=>array(
                'save',
                'delete',
                'update',
                'insert'
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
                'getTableIndentifier',
                'bindObject'
            )
        );

        return $methods[$interfaceName];
    }

    public function setDriver($driver) {
        
        $this->driver = $driver;
    }
 
    public function getDatabase() {
      $this->driverModel->getDatabase(); 
    }
}
