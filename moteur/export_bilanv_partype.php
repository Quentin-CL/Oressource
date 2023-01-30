<?php

/*
  Oressource
  Copyright (C) 2014-2017  Martin Vert and Oressource devellopers

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

session_start();

if (isset($_SESSION['id']) && $_SESSION['systeme'] === 'oressource' && (strpos($_SESSION['niveau'], 'bi') !== false)) {
  require_once '../moteur/dbconfig.php';

  $txt1 = $_GET['date1'];
  $date1ft = DateTime::createFromFormat('d-m-Y', $txt1);
  $time_debut = $date1ft->format('Y-m-d');
  $time_debut = $time_debut . ' 00:00:00';

  $txt2 = $_GET['date2'];
  $date2ft = DateTime::createFromFormat('d-m-Y', $txt2);
  $time_fin = $date2ft->format('Y-m-d');
  $time_fin = $time_fin . ' 23:59:59';

  //Premiere ligne = nom des champs (
  // on affiche la periode visée
  if ($_GET['date1'] === $_GET['date2']) {
    $csv_output = ' Le ' . $_GET['date1'] . "\t";
    $nomfich = "bilan_ventes_par_type_" . $_GET['date1'];
  } else {
    $csv_output = ' Du ' . $_GET['date1'] . ' au ' . $_GET['date2'] . "\t";
    $nomfich = "bilan_ventes_par_type_" . $_GET['date1'] . '_au_' . $_GET['date2'];
  }

  if ($_GET['numero'] === "0") {
    $csv_output .= "\n\r";
    $csv_output .= 'Pour tout les points de collecte' . "\t";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= 'Type d\'objet:' . "\t" . 'Chiffre dégagé en €:' . "\t" . 'Masse pesée en kg' . "\t" . 'Quantité vendue:' . "\t" . 'Somme remboursée en €:' . "\t" . 'Quantité remboursée:';
    $csv_output .= "\n\r";
    $reponse = $bdd->prepare("SELECT
    type_dechets.id as id,
    type_dechets.couleur as couleur,
    type_dechets.nom as nom,
    SUM(case when vendus.lot > 0
    then vendus.prix
    else vendus.prix * vendus.quantite end ) as chiffre_degage,
    COALESCE(SUM(pesees_vendus.masse*pesees_vendus.quantite), 0) as vendu_masse,
    SUM(vendus.quantite) as vendu_quantite,
    SUM(vendus.remboursement) as remb_somme,
    SUM(case when vendus.remboursement > 0 then 1 else 0 end) as remb_quantite
    FROM vendus
    INNER JOIN type_dechets
    ON vendus.id_type_dechet = type_dechets.id
    INNER JOIN ventes
    ON vendus.id_vente = ventes.id
    LEFT JOIN pesees_vendus
    ON pesees_vendus.id = vendus.id 
    WHERE DATE(vendus.timestamp)
    BETWEEN :du AND :au
    GROUP BY type_dechets.id, type_dechets.couleur, type_dechets.nom");
    $reponse->execute(['du' => $time_debut, 'au' => $time_fin]);

    while ($donnees = $reponse->fetch()) {
      $csv_output .= $donnees['nom'] . "\t" . $donnees['chiffre_degage'] . "\t" . $donnees['vendu_masse'] . "\t" . $donnees['vendu_quantite'] . "\t" . $donnees['remb_somme'] . "\t" . $donnees['remb_quantite'] . "\n";
    }
    $reponse->closeCursor();
  } else {
    $csv_output .= "\n\r";
    $csv_output .= 'Pour le point numero:  ' . $_GET['numero'] . "\t";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= 'Type d\'objet:' . "\t" . 'Chiffre dégagé en €:' . "\t" . 'Masse pesée en kg' . "\t" . 'Quantité vendue:' . "\t" . 'Somme remboursée en €:' . "\t" . 'Quantité remboursée:';
    $csv_output .= "\n\r";
    $reponse = $bdd->prepare("SELECT
    type_dechets.id as id,
    type_dechets.couleur as couleur,
    type_dechets.nom as nom,
    SUM(case when vendus.lot > 0
    then vendus.prix
    else vendus.prix * vendus.quantite end ) as chiffre_degage,
    COALESCE(SUM(pesees_vendus.masse*pesees_vendus.quantite), 0) as vendu_masse,
    SUM(vendus.quantite) as vendu_quantite,
    SUM(vendus.remboursement) as remb_somme,
    SUM(case when vendus.remboursement > 0 then 1 else 0 end) as remb_quantite
    FROM vendus
    INNER JOIN type_dechets
    ON vendus.id_type_dechet = type_dechets.id
    INNER JOIN ventes
    ON vendus.id_vente = ventes.id
    INNER JOIN pesees_vendus
    ON pesees_vendus.id = vendus.id 
    WHERE DATE(vendus.timestamp) 
    BETWEEN :du AND :au 
    AND ventes.id_point_vente = :numero
    GROUP BY type_dechets.id, type_dechets.couleur, type_dechets.nom");
    $reponse->execute(['du' => $time_debut, 'au' => $time_fin, 'numero' => $_GET['numero']]);

    while ($donnees = $reponse->fetch()) {
      $csv_output .= $donnees['nom'] . "\t" . $donnees['chiffre_degage'] . "\t" . $donnees['vendu_masse'] . "\t" . $donnees['vendu_quantite'] . "\t" . $donnees['remb_somme'] . "\t" . $donnees['remb_quantite'] . "\n";
    }
    $reponse->closeCursor();
  }

  $encoded_csv = mb_convert_encoding($csv_output, 'UTF-16LE', 'UTF-8');
  header('Content-Description: File Transfer');
  header('Content-Type: application/vnd.ms-excel');
  header('Content-type: application/vnd.ms-excel');
  header('Content-disposition: attachment; filename=' . $nomfich . '.csv');
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  echo chr(255) . chr(254) . $encoded_csv;
  exit;
}
header('Location:../moteur/destroy.php');
