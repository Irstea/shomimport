<?php
/**
 * Script d'integration des coefficients de maree fournis par le SHOM
 * Realisation : Eric Quinton - mai 2019
 * Copyright © Irstea 2019
 */

error_reporting(E_ERROR);
require_once 'lib/Message.php';
require_once 'lib/fonctions.php';
require_once 'lib/import.class.php';
require_once 'lib/vue.class.php';
require_once 'lib/ObjetBDD_functions.php';
require_once 'lib/ObjetBDD.php';
require_once 'lib/station.class.php';
require_once 'lib/coef.class.php';
$message = new Message();
/**
 * End of Treatment
 */
$eot = false;

/**
 * Options par defaut
 */
$message->set("Marée : importation des coefficients de marée fournis par le SHOM");
$message->set("Licence : MIT. Copyright © 2019 - Éric Quinton, pour Irstea - EABX - Cestas");
/**
 * Traitement des options de la ligne de commande
 */
if ($argv[1] == "-h" || $argv[1] == "--help") {

    $message->set("Options :");
    $message->set("-h ou --help : ce message d'aide");
    $message->set("--station=stationName : nom de la station (obligatoire)");

    $message->set("--dsn=pgsql:host=server;dbname=database;sslmode=require : PDO dsn (adresse de connexion au serveur selon la nomenclature PHP-PDO)");
    $message->set("--login= : nom du login de connexion");
    $message->set("--password= : mot de passe associé");
    $message->set("--schema=public : nom du schéma contenant les tables");
    $message->set("--source=source : nom du dossier contenant les fichiers source");
    $message->set("--treated=treated : nom du dossier où les fichiers sont déplacés après traitement");
    $message->set("--param=param.ini : nom du fichier de paramètres (ne pas modifier sans bonne raison)");
    $message->set("--filetype=csv : extension des fichiers à traiter");
    $message->set("--noMove : pas de déplacement des fichiers une fois traités");
    $message->set("Les fichiers à traiter doivent être déposés dans le dossier import");
    $message->set("Une fois traités, les fichiers sont déplacés dans le dossier treated");
} else {
    /**
     * Processing args
     */
    $moveFile = true;
    $numBac = "";
    $params = array();
    for ($i = 1; $i <= count($argv); $i++) {
        $arg = explode("=", $argv[$i]);
        $params[$arg[0]] = $arg[1];
    }
}
if (!isset($params["param"])) {
    $params["param"] = "param.ini";
}
/**
 * Recuperation des parametres depuis le fichier param.ini
 * ne sont traitées que les valeurs dans la branche [general]
 * 
 */

if (false !== ($param = parse_ini_file($params["param"], true))) {
    foreach ($param["general"] as $key => $value) {
        if (isset($params[$key])) {
            $param["general"][$key] = $params[$key];
        }
    }
}

if (strlen($param["general"]["station"]) > 0) {
    if (!isset($param[$param["general"]["station"]])) {
        $message->set("Les paramètres n'existent pas pour la station " . $param["general"]["station"]);
        $eot = true;
    }
} else {
    $message->set("La station n'a pas été renseignée");
    $eot = true;
}
$stationParam = $param[$param["general"]["station"]];

/**
 * Connexion à la base de données
 */
try {
    $pdo = connect($param);
    $station = new Station($pdo);
    $coef = new Coef($pdo);
} catch (Exception $e) {
    $message->set($e->getMessage());
    $eot = true;
}

if (!$eot) {
    /**
     * Recuperation de la liste des fichiers a traiter
     */
    $files = array();
    try {
        $folder = opendir($param["general"]["source"]);
        if ($folder) {
            $filesOnly = array();
            while (false !== ($filename = readdir($folder))) {
                /**
                 * Extraction de l'extension
                 */

                $extension = (false === $pos = strrpos($filename, '.')) ? '' : strtolower(substr($filename, $pos + 1));
                if ($extension == $param["general"]["filetype"]) {
                    $files[] = $filename;
                }
            }
            closedir($folder);
        } else {
            $message->set("Le dossier " . $param["general"]["source"] . " n'existe pas");
        }
    } catch (Exception $e) {
        $message->set("Le dossier " . $param["general"]["source"] . " n'existe pas");
    }

    if (count($files) > 0) {
        /**
         * Declenchement de la lecture
         */
        $import = new Import();
        try {
            foreach ($files as $file) {
                $data = $import->initFile($param["general"]["source"] . "/" . $file);
                $pdo->beginTransaction();
                foreach ($data as $row) {
                    try {
                        $coef->setValue($data, $stationParam);
                        $pdo->commit();
                        /**
                         * Deplacement du fichier
                         */
                        if (!isset($params["noMove"])) {
                            rename($param["general"]["source"] . "/" . $file, $param["general"]["treated"] . "/" . $file);
                        }
                    } catch (Exception $e) {
                        $pdo->rollBack();
                    }
                }
            }
        } catch (Exception $e) {
            $message->set("Echec d'importation des fichiers");
            $message->set($e->getMessage());
        }
    } else {
        $message->set("Pas de fichiers à traiter dans le dossier " . $param["general"]["folder"]);
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
