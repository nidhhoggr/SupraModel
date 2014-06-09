<?php

require_once(dirname(__FILE__) . '/SupraModel/SupraModel.class.php');

//SET THE CONNECTION VARS HERE
$dbuser = 'root';
$dbpassword  = 'root';
$dbname = 'eaacorpc_deailers';
$dbhost = 'localhost';
$driver = 'mysql';

$connection_args = compact('dbuser','dbname','dbpassword','dbhost','driver');

//EXTEND THE BASE MODEL
class MinerModel extends SupraModel {

    //SET THE TABLE OF THE MODEL AND THE IDENTIFIER
    public function configure() {

        $this->setTable("dealers_dub");
    }
}

$MinerModel = new MinerModel($connection_args);

$mm = $MinerModel;

$res = $mm->find([]);

$mm->setTable('dealers');

$dups = $deletable = array();


foreach($res as $row)
{
    $dups['name'][$row->id] = $mm->findBy(['conditions'=>["name = '". mysql_real_escape_string($row->name)."'"]]);

    $dups['street'][$row->id] = $mm->find(['conditions'=>["address_street = '". mysql_real_escape_string($row->address_street)."'"]]);
}

$debug_tgoggled = TRUE;

function debug_print($str)
{
    global $debug_tgoggled;

    if($debug_tgoggled) var_dump($str);
}

function lev_for($dups, $key)
{
    global $deletable;

    $issue_count = 0;

    foreach($dups as $offender=>$dup)
    {
        $strings = array();

        foreach($dup  as $d)
        {   
            $strings[] = $d->name . $d->address_street;
        }

        if(count($strings) > 2) 
        {
            debug_print('to much for ' . $offender);
            
            $issue_count ++;
        }
        elseif($strings[0] !== $strings[1]) 
        {
            $lev = levenshtein($strings[0], $strings[1]);
        
            debug_print('Lev: ' . $lev);

            $issue_count ++;
        }
        else 
        {
            $deletable[] = $dup;

            continue;
        }

        debug_print($key.' offender ' . $offender);

        if(isset($lev) && $lev < 5) 

        debug_print($dup);

    }

    return $issue_count;
}

foreach($dups as $key=>$dup)
{
    debug_print(lev_for($dup, $key) . ' Issues for ' . $key);
}

exit;

var_dump(count($deletable));

var_dump($deletable);
