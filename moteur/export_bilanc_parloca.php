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

  //Premiere ligne = nom des champs (
  // on affiche la periode visée
  if ($_GET['date1'] === $_GET['date2']) {
    $csv_output = ' Le ' . $_GET['date1'] . "\t";
  } else {
    $csv_output = ' Du ' . $_GET['date1'] . ' au ' . $_GET['date2'] . "\t";
  }

  if ($_GET['numero'] === "0") {
    $csv_output .= "\n\r";
    $csv_output .= 'Pour tout les points de collecte' . "\t";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= 'localité:' . "\t" . 'masse collecté:' . "\t" . 'nombre de collectes:' . "\t";
    $csv_output .= "\n\r";
    require_once '../moteur/dbconfig.php';
    $reponse = $bdd->prepare('SELECT
      localites.nom,SUM(pesees_collectes.masse) somme,pesees_collectes.timestamp,localites.id id,COUNT(distinct collectes.id) ncol
      FROM pesees_collectes,collectes,localites
      WHERE pesees_collectes.timestamp BETWEEN :du AND :au AND
      localites.id =  collectes.localisation AND pesees_collectes.id_collecte = collectes.id
      GROUP BY id');
    $reponse->execute(['du' => $time_debut, 'au' => $time_fin]);

    while ($donnees = $reponse->fetch()) {
      $csv_output .= $donnees['nom'] . "\t" . $donnees['somme'] . "\t" . $donnees['ncol'] . "\t" . "\n";
      require_once '../moteur/dbconfig.php';

      $reponse2 = $bdd->prepare('SELECT localites.couleur,type_dechets.nom, sum(pesees_collectes.masse) somme
        FROM type_dechets,pesees_collectes ,localites , collectes
        WHERE pesees_collectes.timestamp BETWEEN :du AND :au
        AND type_dechets.id = pesees_collectes.id_type_dechet
        AND localites.id =  collectes.localisation AND pesees_collectes.id_collecte = collectes.id
        AND localites.id = :id_loc
        GROUP BY nom
        ORDER BY somme DESC');
      $reponse2->execute(['du' => $time_debut, 'au' => $time_fin, 'id_loc' => $donnees['id']]);

      $csv_output .= 'objets collectés pour cette localité:' . "\t" . 'masse collecté:' . "\t";
      $csv_output .= "\n\r";

      while ($donnees2 = $reponse2->fetch()) {
        $csv_output .= $donnees2['nom'] . "\t" . $donnees2['somme'] . "\t" . "\n";
      }

      $reponse2->closeCursor();

      $csv_output .= "\n\r";
    }
    $reponse->closeCursor();
  } else {
    $csv_output .= "\n\r";
    $csv_output .= ' pour le point numero:  ' . $_GET['numero'] . "\t";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= 'localité:' . "\t" . 'masse collecté:' . "\t" . 'nombre de collectes:' . "\t";
    $csv_output .= "\n\r";

    // on determine les masses totales collèctés sur cete periode(pour un point donné)
    require_once '../moteur/dbconfig.php';
    $reponse = $bdd->prepare('SELECT
      localites.nom,SUM(pesees_collectes.masse) somme,pesees_collectes.timestamp,localites.id,COUNT(distinct collectes.id) ncol
      FROM pesees_collectes,collectes,localites
      WHERE pesees_collectes.timestamp BETWEEN :du AND :au AND
      localites.id =  collectes.localisation AND pesees_collectes.id_collecte = collectes.id
      AND collectes.id_point_collecte = :numero
      GROUP BY id');

    $reponse->execute(['du' => $time_debut, 'au' => $time_fin, 'numero' => $_GET['numero']]);

    while ($donnees = $reponse->fetch()) {
      $csv_output .= $donnees['nom'] . "\t" . $donnees['somme'] . "\t" . $donnees['ncol'] . "\t" . "\n";

      require_once '../moteur/dbconfig.php';

      $reponse2 = $bdd->prepare('SELECT type_dechets.couleur,type_dechets.nom, sum(pesees_collectes.masse) somme
        FROM type_dechets,pesees_collectes ,type_collecte , collectes
        WHERE pesees_collectes.timestamp BETWEEN :du AND :au
        AND type_dechets.id = pesees_collectes.id_type_dechet
        AND type_collecte.id =  collectes.id_type_collecte AND pesees_collectes.id_collecte = collectes.id
        AND type_collecte.id = :id_type_collecte AND collectes.id_point_collecte = :numero
        GROUP BY nom
        ORDER BY somme DESC');
      $reponse2->execute(['du' => $time_debut, 'au' => $time_fin, 'numero' => $_GET['numero'], 'id_type_collecte' => $donnees['id']]);
      $csv_output .= 'objets collectés pour cette localité:' . "\t" . 'masse collecté:' . "\t";
      $csv_output .= "\n\r";

      while ($donnees2 = $reponse2->fetch()) {
        $csv_output .= $donnees2['nom'] . "\t" . $donnees2['somme'] . "\t" . "\n";
      }
      $reponse2->closeCursor();

      $csv_output .= "\n\r";
    }
    $reponse->closeCursor();
  }

  //=====================================================================================================================================
  $encoded_csv = mb_convert_encoding($csv_output, 'UTF-16LE', 'UTF-8');
  header('Content-Description: File Transfer');
  header('Content-Type: application/vnd.ms-excel');
  header('Content-disposition: attachment; filename=collectes_par_localites_' . date('Ymd') . '.csv');
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  echo chr(255) . chr(254) . $encoded_csv;
  exit;
}
header('Location:../moteur/destroy.php');
