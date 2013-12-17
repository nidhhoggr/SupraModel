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

    public function find($args) {

        $args = array_merge(array('fields'=>'*','fetchArray'=>true),(array)$args);

        extract($args);

        return $this->findBy(compact('fields','order','fetchArray'));
    }

    public function findBy($args) {

        $args = array_merge(array('fields'=>'*','fetchArray'=>true),(array)$args);

        extract($args);

        if(isset($fields))
            $this->_sqlizeFields($fields);
        
        if(isset($conditions))
            $this->_sqlizeConditions($conditions);        

        $this->querySql = "SELECT ". $this->sqlFields . " FROM " . $this->model->getTable() 
                           . ' ' . $this->sqlConditions;

        if(isset($order))
            $this->querySql .= " $order";

        if($fetchArray) return $this->_fetchArrayFromQuery();
    }

    public function findOneBy($args) {
                    
        $args = array_merge((array)$args,array('fetchArray'=>false));

     
        if(isset($args['order']))
            if(!stristr($args['order'],'limit')) $args['order'] .= " LIMIT 1";
 
        $this->findBy($args);

        $result = $this->_fetchArrayFromQuery();

        return $result[0];
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
              foreach($row as $k=>$col) {

                $col = $this->model->unserializeArray($col);

                $values[$k] = $col;
              }

              $all[] = $values;
            }
            else if(is_array($fields)) {
                foreach($fields as $field) {
                  $val = $row[$field];

                  $val = $this->model->unserializeArray($val);

                  $values[$field] = $val;
                }

                $all[] = $values;
            }
            else {

                $val = $row[$fields];

                $val = $this->model->unserializeArray($val);

                $all[] = $val;
            }
        }

        return $all;
    }
}
