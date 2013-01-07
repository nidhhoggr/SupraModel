<?php
interface Selection {

    public function find($table,$fields,$order,$fetchArray);

    public function findBy($table,$fields,$condtions,$order,$fetchArray);

    public function findOneBy($table,$fields,$condtions,$order);

    public function getQuery();
}
