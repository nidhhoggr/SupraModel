<?php 

class MysqlSelection implements Selection {

    private 
        $querySql,
        $sqlFields,
        $sqlConditions;

    public function __construct(MysqlModel $model) {

        $this->model = $model;
    }

    private function _sqlizeFields($fields) {

        $this->sqlFields = (is_array($fields)) ? implode(', ',$fields) : $fields;
    }

    private function _sqlizeConditions($conditions=null) {

        if(!empty($conditions)) {

            if(is_array($conditions))
                $conditions = implode(" AND ", $conditions);

            $this->sqlConditions = " WHERE $conditions";
        }
    }

    public function getQuery() {
   
        return $this->querySql;
    }

    public function find($table, $fields="*", $order = null, $fetchArray = true) {

        return $this->findBy($table, $fields, null, $order, $fetchArray);
    }

    public function findBy($table, $fields = "*", $conditions = null, $order = null, $fetchArray = true) {

        $this->_sqlizeFields($fields);
        
        $this->_sqlizeConditions($conditions);        

        $this->querySql = "SELECT ". $this->sqlFields . " " . $this->_sqlConditions . " FROM $table  $order";

        if($fetchArray) return $this->_fetchArrayFromQuery();
    }

    public function findOneBy($table, $fields, $conditions=null, $order = null) {
                     
        $this->findBy($table, $fields, $conditions, $order, false);

        return $this->model->queryUniqueValue($this->querySql, $this->model->getDebugMode());
    }

    private function _getResultFromQuery() {

        return $this->model->query($this->querySql, $this->model->getDebugMode());
    }

    private function _fetchArrayFromQuery() {

       $result = $this->_getResultFromQuery();

       $all = array();

       $fields = $this->sqlFields;

        while($row = mysql_fetch_assoc($result)) {
            $values = null;

            if($fields == "*") {
                $all[] = $row;
            }
            else if(is_array($fields)) {
                foreach($fields as $field) {
                    $values[$field] = $row[$field];
                }

                $all[] = $values;
            }
            else {
                $all[] = $row[$fields];
            }
        }

        return $all;
    }
}
