<?php
 //use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SondeException extends Exception
{ };

class Sonde
{

    /**
     * Fonction initialisant l'importation des données, 
     * et déclenchant l'import lui-même
     *
     * @param int $sonde_id
     * @param array $files
     * @return int
     */
    function read(array $params, array $files, string $format)
    {
        $param = $params[$format];
        if ($param["filetype"] == "xlsx") {
            /**
             * Initialisation Excel
             */
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setLoadSheetsOnly($param["sheetname"]);
        }

        /**
         * Extraction des données
         */
        $data = array();
        foreach ($files as $file) {
            
            if ($param["filetype"] == "xlsx") {
                try {
                $spreadSheet = $reader->load($file);
                $sheet = $spreadSheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow("A");
                if (! $highestRow > 1) {
                    throw new SondeException("L'onglet " . $param["sheetname"] . " de " . $file["name"] . " est vide ou inexistant");
                }
                /**
                 * Recuperation de la ligne d'entete
                 */
                $highestColumn = $sheet->getHighestColumn(1);
                $header = array();
                $i = 1;
                for ($col = 'A'; $col != $highestColumn; $col++) {
                    $header[$i] = $sheet->getCell($col . $param["headerline"])->getValue();
                    $i++;
                }
                /**
                 * Recuperation du tableau contenant les donnees
                 */
                for ($row = $param["headerline"] + 1; $row <= $highestRow; $row++) {
                    $j = 1;
                    $drow = array();
                    for ($col = 'A'; $col != $highestColumn; ++$col) {
                        $drow[$header[$j]] = $sheet->getCell($col . $row)->getFormattedValue();
                        $j++;
                    }
                    $data[] = $drow;
                }
                } catch (Exception $e) {
                    throw new SondeException($e->getMessage());
                }
            } elseif($param["filetype"] == "csv"){
                $handle = fopen($file, "r");
                /**
                 * Recuperation de l'entete
                 */
                for ($i = 0; $i < $param["headerline"]; $i ++) {
                    $header = fgetcsv($handle,1000,$param["fieldSeparator"]);
                }
                while (($linecsv = fgetcsv($handle, 1000, $param["fieldSeparator"])) !== FALSE) {
                    $i = 0;
                    $line = array();
                    foreach ($linecsv as $val) {
                        $line [$header[$i]] = $val;
                        $i ++;
                    }
                    $data[] = $line;
                }
            }
        }
        return $this->$format($param, $data, $header);
    }

    /**
     * Fonction générant l'import pour les fichiers créés par Pcwin
     *
     * @param array $param : paramètres de l'importation
     * @param array $data : jeu de données
     * @return int
     */
    function pcwin($param, $data, $header)
    {
        $abnormalValues = explode(",", $param["abnormalvalues"]);
        $listeAnalyse = array();
        foreach ($data as $row) {
            /**
             * Verification que les données fournies ne sont pas anormales
             */
            if ((strlen($row["value"] > 0) and !in_array($row["value"], $abnormalValues))) {
                /**
                 * Extraction du bac
                 */
                $drank = explode($param["fieldSeparator"], $row["rank"]);
                $bac = substr($drank[0], 3);

                /**
                     * Recherche de l'attribut correspondant au critère analysé
                     */
                $attribut = $param[substr($drank[1], 0, 1)];
                if (strlen($attribut) > 0) {
                    $listeAnalyse[$bac][$row["date"]][$attribut] = $row["value"];
                } else {
                    throw new SondeException("La valeur analysée " . $drank[1] . " n'est pas décrite dans le fichier de paramétrage");
                }
                //$analyse = array("circuit_eau_id"=>$circuit_id); 
            }
        }
        /**
         * generation du tableau final
         */
        return $this->_generateFinalArray($listeAnalyse);
    }

    /**
     * Mise en forme des donnees traitees par chaque type de sonde pour generation du fichier final
     *
     * @param array $listeAnalyse
     * @return array
     */
    private function _generateFinalArray($listeAnalyse) {
        $data = array();
        foreach ($listeAnalyse as $bacId => $bac) {
            foreach ($bac as $analyseDate => $analyse) {
                $data[] = array(
                    "bac" => $bacId, 
                    "date" => $analyseDate, 
                    "o2_pc" => $analyse["o2_pc"],
                    "ph"=>$analyse["ph"],
                    "salinite"=>$analyse["salinite"],
                    "temperature"=>$analyse["temperature"],
                    "conductivite"=>$analyse["conductivite"],
                    "o2_mg"=>$analyse["o2_mg"]
                 );
            }        
       } 
        return $data;
    }

    /**
     * Traitement des donnees generees par la sonde multi3630
     *
     * @param array $param: parametres d'importation
     * @param array $data
     * @return array
     */
    function multi3630($param, $data, $header) {
        $listeAnalyse = array();
        foreach ($data as $row) {
            /**
             * Reformatage de la date
             */
            $row["date"] = str_replace(".", "/", $row["Date/Time"]);
            /**
             * Recuperation de la station
             */
            $station = $param["id".$row["ID"]];
            /**
             * Traitement particulier de l'oxygene
             */
            if ($row["Mode"] == "Ox" ) {
                if ($row["Unit"] != $param["oxygenunit"]) {
                   $row["Mode"] = "o2_mg";
                }
            } 

           $listeAnalyse[$station][$row["date"]][$param[$row["Mode"]]]= $row ["Value"];
            /**
             * Recuperation de la temperature
             */
            $listeAnalyse[$station][$row["date"]][$param[$row["Mode2"]]]= $row ["Value2"];
        }
        return $this->_generateFinalArray($listeAnalyse);
    }

    /**
     * Traitement des donnees fournies par la sonde hobo
     *
     * @param array $param: parametres d'importation
     * @param array $data
     * @return array
     */
    function hobo($param, $data, $header) {
        $listeAnalyse = array();
        if ( strlen($param["numBac"])==0) {
            $param["numBac"] = 1;
        }
        foreach ($data as $row) {
            /**
             * Reformatage de la date
             */
            $adate = date_parse_from_format($param["formatdate"], $row[$header[1]]);
            //print_r($adate);
            $row["date"] = str_pad($adate["day"],2, 0, STR_PAD_LEFT) . "/".
                            str_pad($adate["month"], 2, 0, STR_PAD_LEFT)."/".
                            $adate["year"]." ".
                            str_pad($adate["hour"],2,0, STR_PAD_LEFT).":".
                            str_pad($adate["minute"],2,0, STR_PAD_LEFT).":".
                            str_pad($adate["second"],2,0, STR_PAD_LEFT);
            $listeAnalyse[$param["numBac"]][$row["date"]] = array("o2_mg"=>$row[$header[2]], "temperature"=>$row[$header[3]]);
            
        }
        return $this->_generateFinalArray($listeAnalyse);
    }
}
 