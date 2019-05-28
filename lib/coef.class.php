<?php

class Coef extends ObjetBDD
{
    private $month = array(
        "Janvier"=>"01",
        "Février"=>"02",
        "Mars"=>"03",
        "Avril"=>"04",
        "Mai"=>"05",
        "Juin"=>"06",
        "Juillet"=>"07",
        "Août"=>"08",
        "Septembre"=>"09",
        "Octobre"=>"10",
        "Novembre"=>"11",
        "Décembre"=>"12"
        ) ;
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

    function setValue($data, $param) {
        $my = explode(" ", utf8_encode($data[0]));
        $dd = explode(" ", $data[1]);
        $row=array();
        $row["daydate"] = $my[1]."-".$this->month[$my[0]]."-".$dd[1];
        /**
         * Traitement de la premiere pleine mer
         */
        $date = date($row["daydate"]." ".$data[3].":00");
        /**
         * Ajout du décalage
         */
        $decalage = $data[2]
    }
}
