<?php
/**
 * Script d'integration des coefficients de maree fournis par le SHOM
 * Realisation : Eric Quinton - mai 2019
 * Copyright © Irstea 2019
 */

error_reporting(E_ERROR);
require_once 'lib/Message.php';
require_once 'lib/fonctions.php';
require_once 'lib/sonde.class.php';
require_once 'lib/vue.class.php';
$message = new Message();

/**
 * Options par defaut
 */
$paramfile = "param.ini";
$format = "pcwin";
$fileExtension = "csv";
    $message->set("Sonde : conversion des données de sonde à partir d'un fichier csv ou excel non tabulé en fichier csv tabulé");
    $message->set("Licence : MIT. Copyright © 2019 - Éric Quinton, pour Irstea - EABX - Cestas");
/**
 * Traitement des options de la ligne de commande
 */
if ($argv[1] == "-h" || $argv[1] == "--help") {

    $message->set("Options :");
    $message->set("-h ou --help : ce message d'aide");
    $message->set("--format=[pcwin|multi3630|hobo] : format des fichiers a traiter. Par défaut : pcwin");
    $message->set("--numBac=1 : numéro du bac, quand l'information ne figure pas dans le fichier (import hobo)");
    $message->set("--param=param.ini : nom du fichier de paramètres (ne pas modifier sans bonne raison)");
    $message->set("--export=fichier.csv : nom du fichier csv généré. Par défaut : format + date.csv");
    $message->set("--noMove : pas de déplacement des fichiers une fois traités");
    $message->set("Les fichiers à traiter doivent être déposés dans le dossier import");
    $message->set("Le fichier généré est déposé dans le dossier export");
    $message->set("Une fois traités, les fichiers sont déplacés dans le dossier treated");
} else {
    /**
 * Processing args
 */
    $moveFile = true;
    $numBac = "";
    for ($i = 1; $i <= count($argv); $i++) {
        $arg = explode("=", $argv[$i]);
        switch ($arg[0]) {
            case "--export":
                $fileExport = $arg[1];
                break;
            case "--format":
                $format = $arg[1];
                break;
            case "--param":
                $paramfile = $arg[1];
                break;
            case "--noMove":
                $moveFile = false;
                break;
            case "--numBac":
                $numBac = $arg[1];
        }
    }

    /**
 * Recuperation des parametres
 */
    $param = parse_ini_file($paramfile, true);
    $fileExport = $format . "-" . date('YmdHi') . "." . $fileExtension;
    /**
 * Recuperation de la liste des fichiers a traiter
 */
    $files = array();
    try {
        $folder = opendir($param[$format]["folder"]);
        if ($folder) {
            $filesOnly = array();
            while (false !== ($filename = readdir($folder))) {
                /**
             * Extraction de l'extension
             */

                $extension = (false === $pos = strrpos($filename, '.')) ? '' : strtolower(substr($filename, $pos + 1));
                if ($extension == $param[$format]["filetype"]) {
                    $filesOnly[] = $filename;
                    $files[] = $param[$format]["folder"] . "/" . $filename;
                }
            }
            closedir($folder);
        } else {
            $message->set("Le dossier " . $param[$format]["folder"] . " n'existe pas");
        }
    } catch (Exception $e) {
        $message->set("Le dossier " . $param[$format]["folder"] . " n'existe pas");
    }

    if (count($files) > 0) {
        /**
 * Declenchement de la lecture
 */
        try {
            $param[$format]["numBac"] = $numBac;
            $result = $sonde->read($param, $files, $format);
            if (count($result) > 0) {
                /**
         * Ecriture du fichier CSV
         */
                $vueCsv = new VueCsv();
                $vueCsv->set($result);
                $vueCsv->send($param["general"]["export"] . "/" . $fileExport);
                /**
          * Deplacement des fichiers traites dans le dossier treated
          */
                foreach ($filesOnly as $file) {
                    $message->set($param[$format]["folder"] . "/" . $file . " traité");
                    if ($moveFile) {
                        rename($param[$format]["folder"] . "/" . $file, $param["general"]["treated"] . "/" . $file);
                    }
                }
            } else {
                $message->set("Echec de préparation du fichier : les données sont vides");
            }
        } catch (Exception $e) {
            $message->set("Echec d'importation des fichiers");
            $message->set($e->getMessage());
        }
    } else {
        $message->set("Pas de fichiers à traiter pour le format " . $format . " dans le dossier " . $param[$format]["folder"]);
    }
}

/**
 * Display messages
 */
if (!stripos(PHP_OS, "WIN")) {
    $windows = false;
} else {
    $windows = true;
}
foreach ($message->get() as $line) {
    if ($windows) {
        utf8_decode($line);
    }
    echo ($line . PHP_EOL);
}
echo (PHP_EOL);
