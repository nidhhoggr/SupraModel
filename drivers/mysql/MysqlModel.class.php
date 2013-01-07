<?php

require_once(dirname(__FILE__) . "/../../interfaces/DriverModel.class.php");
require_once(dirname(__FILE__) . "/MysqlDB.class.php");

class MysqlModel extends MysqlDB implements DriverModel {

    private 
        $debugMode = false,
        $table_identifier = "id";

    public function __construct($base, $server, $user, $pass) {

        parent::__construct($base, $server, $user, $pass);
        $this->_generateHandlers();
    }

    private function _generateHandlers() {

        $interfaces = array('Selection','Modification');

        foreach($interfaces as $interface) {

            $handlerVar = strtolower($interface) . 'Handler';
            $handlerClass = 'Mysql' . $interface;
            require_once(dirname(__FILE__) . "/../../interfaces/$interface.class.php");
            require_once(dirname(__FILE__) . "/$handlerClass.class.php");
            $this->$handlerVar = new $handlerClass($this);
        }
    }

    public function setDebugMode($mode) {

        $this->debugMode = $mode;
    }

    public function getDebugMode() {

        return $this->debugMode;
    }

    public function setTable($table) {

        $this->table = $table;
    }

    public function getTable() {
    
        return $this->table;
    }

    public function setTableIdentifier($id) {

        $this->table_identifier = $id;
    }
  
    public function getTableIdentifier() {

        return $this->table_identifier;
    }
}
