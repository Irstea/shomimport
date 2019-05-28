<?php

class Coef extends ObjetBDD
{
    function __construct($pdo, $param = array())
    {
        $this->table = "coef";
        $this->colonnes = array(
            "coef_id" => array("key" => 1, "type" => 1),
            "coef_type_id" => array("type" => 1),
            "station_id" => array("type" => 1),
            "daydate" => array("type" => 0),
            "hour" => array("type" => 0),
            "coef" => array("type" => 1)
        );
        parent::__construct($pdo, param);
    }
}
