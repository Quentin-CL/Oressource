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
  $id_point_collecte = intval($_GET['numero']);
  require_once '../moteur/dbconfig.php';

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

  $csv_output .= "\n\r";
  $csv_output .= ($id_point_collecte === 0 ? 'Pour tout les points de collecte' . "\t" : 'Pour le point numero:  ' . $id_point_collecte . "\t");
  $csv_output .= "\n\r";
  $csv_output .= "\n\r";
  $csv_output .= "\n\r";
  $csv_output .= 'localité:' . "\t" . 'masse collecté:' . "\t" . 'nombre de collectes:' . "\t";
  $csv_output .= "\n\r";
  $cond = ($id_point_collecte > 0 ? " AND collectes.id_point_collecte = $id_point_collecte " : ' ');
  $reponse = $bdd->prepare("SELECT
    localites.nom,SUM(pesees_collectes.masse) somme,localites.id id,COUNT(distinct collectes.id) ncol
    FROM pesees_collectes,collectes,localites
    WHERE pesees_collectes.timestamp BETWEEN :du AND :au AND
    localites.id =  collectes.localisation AND pesees_collectes.id_collecte = collectes.id
    $cond
    GROUP BY id");
  $reponse->execute(['du' => $time_debut, 'au' => $time_fin]);

  while ($donnees = $reponse->fetch()) {
    $csv_output .= $donnees['nom'] . "\t" . $donnees['somme'] . "\t" . $donnees['ncol'] . "\t" . "\n";
    $reponse2 = $bdd->prepare("SELECT localites.couleur,type_dechets.nom, sum(pesees_collectes.masse) somme
      FROM type_dechets,pesees_collectes ,localites , collectes
      WHERE pesees_collectes.timestamp BETWEEN :du AND :au
      AND type_dechets.id = pesees_collectes.id_type_dechet
      AND localites.id =  collectes.localisation AND pesees_collectes.id_collecte = collectes.id
      AND localites.id = :id_loc
      $cond
      GROUP BY nom
      ORDER BY somme DESC");
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
