<?php


header('content-type:application/json');
require_once '../core/requetes.php';
require_once '../moteur/dbconfig.php';

$now_date = (new DateTime())->format('Y-m-d');
$time_debut = $now_date . ' 00:00:00';
$time_fin = $now_date . ' 23:59:59';
$numero = filter_input(INPUT_GET, 'numero', FILTER_VALIDATE_INT) ?? 0;

try {
    $bilans = bilan_ventes(
        $bdd,
        $time_debut,
        $time_fin,
        $numero
    );
    http_response_code(200); // OK
    echo json_encode(['chiffre_du_jour' => $bilans['chiffre_degage'] - $bilans['remb_somme']], JSON_FORCE_OBJECT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de requete serveur'], JSON_FORCE_OBJECT);
}
