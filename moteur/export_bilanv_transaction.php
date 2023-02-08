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

  //on convertit les deux dates en un format compatible avec la bdd

  $txt1 = $_GET['date1'];
  $date1ft = DateTime::createFromFormat('d-m-Y', $txt1);
  $time_debut = $date1ft->format('Y-m-d');
  $time_debut = $time_debut . ' 00:00:00';

  $txt2 = $_GET['date2'];
  $date2ft = DateTime::createFromFormat('d-m-Y', $txt2);
  $time_fin = $date2ft->format('Y-m-d');
  $time_fin = $time_fin . ' 23:59:59';
  $id_point_vente = intval($_GET['numero']);
  //Premiere ligne = nom des champs (
  // on affiche la periode visée
  if ($_GET['date1'] === $_GET['date2']) {
    $csv_output = ' Le ' . $_GET['date1'] . "\t";
  } else {
    $csv_output = ' Du ' . $_GET['date1'] . ' au ' . $_GET['date2'] . "\t";
  }


  $csv_output .= "\n\r";
  $csv_output .= ($id_point_vente === 0 ? 'Pour tout les points de collecte' . "\t" : 'Pour le point numero:  ' . $id_point_vente . "\t");
  $csv_output .= "\n\r";
  $csv_output .= "\n\r";
  $csv_output .= "\n\r";
  $csv_output .= 'Type de transaction' . "\t" . 'Nombre' . "\t" . 'Somme' . "\t";
  $csv_output .= "\n\r";
  require_once '../moteur/dbconfig.php';
  $cond = ($id_point_vente > 0 ? " AND autres_transactions.id_point_vente = $id_point_vente " : ' ');
  $reponse = $bdd->prepare("SELECT
      COUNT(DISTINCT(autres_transactions.id)) as nb_tran,
      SUM(autres_transactions.somme) as chiffre_total,
      autres_transactions.id_type_transactions as id_tran,
      type_transactions.nom as nom
      FROM autres_transactions
      INNER JOIN type_transactions
      ON autres_transactions.id_type_transactions = type_transactions.id
      $cond
      WHERE DATE(autres_transactions.timestamp)
      BETWEEN :du AND :au
      GROUP BY autres_transactions.id_type_transactions");
  $reponse->execute(['du' => $time_debut, 'au' => $time_fin]);

  while ($donnees = $reponse->fetch()) {
    $csv_output .= $donnees['nom'] . "\t" . $donnees['nb_tran'] . "\t" . $donnees['chiffre_total'] . "\t" . "\n";
    require_once '../moteur/dbconfig.php';

    $reponse2 = $bdd->prepare("SELECT
        autres_transactions.id as id,
        autres_transactions.timestamp,
        autres_transactions.somme as chiffre_degage
        FROM autres_transactions
        WHERE DATE(autres_transactions.timestamp)
        BETWEEN :du AND :au
        AND autres_transactions.id_type_transactions = :id_tran
        $cond
        ORDER BY autres_transactions.id, autres_transactions.timestamp");
    $reponse2->execute(['du' => $time_debut, 'au' => $time_fin, 'id_tran' => $donnees['id_tran']]);

    $csv_output .= '#' . "\t" . 'Date' . "\t" . 'Somme' . "\t" . "\n";

    while ($donnees2 = $reponse2->fetch()) {
      $csv_output .= $donnees2['id'] . "\t" . $donnees2['timestamp'] . "\t" . $donnees2['chiffre_degage'] . "\t" . "\n";
    }

    $reponse2->closeCursor();

    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
  }
  $reponse->closeCursor();

  //=====================================================================================================================================
  $encoded_csv = mb_convert_encoding($csv_output, 'UTF-16LE', 'UTF-8');
  header('Content-Description: File Transfer');
  header('Content-Type: application/vnd.ms-excel');
  header('Content-disposition: attachment; filename=bilan_des_transactions_' . date('Ymd') . '.csv');
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  echo chr(255) . chr(254) . $encoded_csv;
  exit;
}
header('Location:../moteur/destroy.php');
