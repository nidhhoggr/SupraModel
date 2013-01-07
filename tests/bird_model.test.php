<?php

require_once(dirname(__FILE__) . '/../SupraModel.class.php');

//SET THE CONNECTION VARS HERE
$dbuser = 'root';
$dbpassword  = 'root';
$dbname = 'bob_birdfinder';
$dbhost = 'localhost';
$driver = 'mysql';

$connection_args = compact('dbuser','dbname','dbpassword','dbhost','driver');

//EXTEND THE BASE MODEL
class BirdModel extends SupraModel {

    //SET THE TABLE OF THE MODEL AND THE IDENTIFIER
    public function configure() {

        $this->driverModel->setTable("bird");
    }
}

$BirdModel = new BirdModel($connection_args);

var_dump($BirdModel->findBy(array('conditions'=>array("id=195"),'fetchArray'=>false)));

var_dump($BirdModel->getQuery());

$BirdModel->setTable('bird_taxonomy');

var_dump($BirdModel->findOneBy(array('conditions'=>"name LIKE '%arizona%'")));
var_dump($BirdModel->getQuery());
