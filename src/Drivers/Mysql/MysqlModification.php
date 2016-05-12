<?php
namespace SupraModel\Drivers\Mysql;

use SupraModel\Interfaces\ModificationInterface;

class MysqlModification implements ModificationInterface {

    public function __construct(MysqlModel $model) {

        $this->model = $model; 
    }

    public function save($args = array()) {

        //for join tables
        if(array_key_exists('hasIdentifier',$args) && !$args['hasIdentifier']) {
            $this->_insert();
            return true; //assume it works
        }

        $identifier = $this->model->getTableIdentifier();
        $attributes = $this->_getAttributes();
        $conditions = null;

        if(!empty($attributes[$identifier]))
            $conditions = array($identifier . ' = "' . $attributes[$identifier] . '"');

        //the record already exists by the specified identifier
        if(!empty($conditions) && $this->model->selectionHandler->findOneBy(compact('conditions'))) {

            $this->_setAttributes($attributes);    
            
            $this->_update();

            //return the modified id
            return $attributes[$identifier];
        }
        //the record doesnt exist yet create a new one
        else {
            $this->_insert();
            //return the last insertion id
            return $this->model->lastInsertedId();
        }

    }

    //just like save with one less query
    //beware this does not check if the value doesnt exist
    public function update() {

        $identifier = $this->model->getTableIdentifier();
        $attributes = $this->_getAttributes();
        $conditions = null;

        if(!empty($attributes[$identifier]))
            $conditions = $identifier . ' = "' . $attributes[$identifier] . '"';

        $this->_update();
        
        $this->_refresh();

            //return the modified id
        return $attributes[$identifier];
    }

    //just like save with one less query
    //beware this does not check if the value doesnt exist
    public function insert() {

        $identifier = $this->model->getTableIdentifier();
        $attributes = $this->_getAttributes();
        $conditions = null;

        if(!empty($attributes[$identifier]))
            $conditions = $identifier . ' = "' . $attributes[$identifier] . '"';

        $this->_insert();
        
        $this->_refresh();

        //return the last insertion id
        return $this->model->lastInsertedId();
    }

    protected function _getColumns() {

        $columns = $this->model->getColumns();

        if(empty($columns)) {
            $columns = $this->model->getColumnsByTable($this->model->getTable());
            $this->model->setColumns($columns);
        }

        return $columns;
    }

    protected function _refresh() {
        foreach($this->_getColumns() as $col) {
            if(isset($this->model->$col)) {
              $this->model->$col = null;
            }
            else {
              continue;
            }
        }
    }

    public function delete($id = null) {

        $identifier = $this->model->getTableIdentifier();

        $attributes = $this->_getAttributes();

        if(empty($id)) {
            $id = $attributes[$identifier];
        }

        $where = ' WHERE ' . $identifier .' = '. $id;

        $sql = 'DELETE FROM ' . $this->model->getTable() . ' ' . $where;

        $this->model->execute($sql);

        $this->_refresh();
        
        return $this->model->numRowsAffected();
    }

    private function _getAttributes() {

        $attributes = array();

        $columns = $this->_getColumns();

        foreach($columns as $col) {

            if(isset($this->model->$col))
              $val = $this->model->$col;
            else 
              continue;
 
            if(is_array($val))
                $attributes[$col] = $this->model->serializeArray($val);
            else
                $attributes[$col] = $val;
        }

        return $attributes;
    }

    private function _setAttributes($attributes)
    {
        foreach($attributes as $key=>$val)
        {
            $this->model->$key = $val;
        }
    }

   

    private function _insertAttributes() {

        $attributes = $this->_getAttributes();

        $attr['columns'] = '`' . implode('`,`',array_keys($attributes)) . '`';       
        $attr['values'] = '"' . implode('","',array_values($attributes)) . '"';

        return $attr;
    }

    private function _updateAttributes() {
        $identifier = $this->model->getTableIdentifier();

        $attributes = $this->_getAttributes();

        foreach($attributes as $k=>$v) {
            if($k != $identifier)
                $statements[] = '' . $k . ' = "'. $v . '"';
        }

        return ' SET ' . implode(',',$statements) . ' WHERE ' . $identifier .' = '. $attributes[$identifier];
    }

    private function _insert() {

        $attributes = $this->_insertAttributes();

        extract($attributes);

        $sql = 'INSERT INTO ' . $this->model->getTable() . '('.$columns.') VALUES('.$values.')';

        $this->model->execute($sql);
    }

    private function _update() {
        $attributes = $this->_updateAttributes();

        $sql = 'UPDATE ' . $this->model->getTable() . ' ' . $attributes;

        $this->model->execute($sql);
    }
}
