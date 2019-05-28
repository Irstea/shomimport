<?php
/**
 * Fonction permettant de reorganiser les donnees des fichiers telecharges,
 * pour une utilisation directe en tableau
 * @return multitype:multitype:NULL  unknown
 */
function formatFiles($attributName = "documentName")
{
    global $_FILES;
    $files = array();
    $fdata = $_FILES[$attributName];
    if (is_array($fdata['name'])) {
        for ($i = 0; $i < count($fdata['name']); ++$i) {
            $files[] = array(
                'name'    => $fdata['name'][$i],
                'type'  => $fdata['type'][$i],
                'tmp_name' => $fdata['tmp_name'][$i],
                'error' => $fdata['error'][$i],
                'size'  => $fdata['size'][$i]
            );
        }
    } else $files[] = $fdata;
    return $files;
}
