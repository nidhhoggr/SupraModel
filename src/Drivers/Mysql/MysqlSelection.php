<?php 
namespace SupraModel\Drivers\Mysql;

use SupraModel\Interfaces\SelectionInterface;

class MysqlSelection implements SelectionInterface {

    private 
        $querySql,
        $sqlFields,
        $sqlizeFields,
        $sqlConditions;

    public function __construct(MysqlModel $model) {

        $this->model = $model;
    }

    private function _sqlizeFields($fields) {

        $this->sqlFields = $fields;
        $this->sqlizedFields = (is_array($fields)) ? implode(', ',$fields) : $fields;
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

    public function find($args = array()) {

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

        $this->querySql = "SELECT ". $this->sqlizedFields . " FROM " . $this->model->getTable();

        $tableAlias = @ $this->model->getTableAlias();

        if(!empty($tableAlias))
        {   
            $this->querySql .= " $tableAlias";
        }   

        $joinClauses = array(
            'join',
            'leftjoin',
            'rightjoin',
            'innerjoin',
            'left_outerjoin',
            'right_outerjoin'
        );

        foreach($joinClauses as $joinClause)
        {
            $joinType = str_replace('join', '', $joinClause);

            $joinType = str_replace('_', ' ', $joinType);

            if(isset($$joinClause)) 
            {
                foreach($$joinClause as $table=>$criteria)
                {
                    $this->querySql .= " {$joinType} JOIN {$table} ON {$criteria} ";
                }
            }
        }

        $this->querySql .= ' ' . $this->sqlConditions;

        if(isset($order))
            $this->querySql .= " $order";

        if($fetchArray) return $this->_fetchObjectFromQuery();
    }

    public function findOneBy($args) {

        $args = array_merge((array)$args,array('fetchArray'=>false));

        if(isset($args['order']))
            if(!stristr($args['order'],'limit')) $args['order'] .= " LIMIT 1";

        $this->findBy($args);

        $result = $this->_fetchObjectFromQuery();

        if(count($result))
        {
            $this->model->mergeObjects($this->model, $result[0]);

            return $result[0];
        }
        else
        {
            return false;
        }
    }

    private function _getResultFromQuery() {

        return $this->model->query($this->querySql, $this->model->getDebugMode());
    }

    private function _fetchObjectFromQuery() {

        $result = $this->_getResultFromQuery();

        $all = array();

        $fields = $this->sqlFields;

        while($row = mysqli_fetch_object($result)) {

            $sm = new \stdClass();

            if($fields == "*" || is_array($fields)) {
                foreach($row as $k=>$col) {

                    try {
                        $col = $this->model->unserializeArray($col);
                    } catch(Exception $e) {
                        $col = false;
                        $this->_catchException($e, $row);
                    }

                    $sm->$k = $col;
                }

                $all[] = $sm;
            }
            else {

                $val = $row->$fields;

                try { 
                    $val = $this->model->unserializeArray($val);
                } catch(Exception $e) { 
                    $val = false;
                    $this->_catchException($e, $row);
                }

                $sm->$fields = $val;

                $all[] = $sm;
            }
        }

        return $all;
    }

    private function _catchException($e, $values) {

        $err = "Problem unpacking object of id: " . $values->{$this->model->getTableIdentifier()};

        $this->errors[] = $err . " " .$e->getMessage();
    }

    public function getErrors() {
        return $this->errors;
    }
}
