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

  $numero = htmlspecialchars($_GET['numero']);
  //on convertit les deux dates en un format compatible avec la bdd
  $date1 = $_GET['date1'];
  $date1ft = DateTime::createFromFormat('d-m-Y', $date1);
  $time_debut = $date1ft->format('Y-m-d');
  $time_debut .= ' 00:00:00';

  $date2 = $_GET['date2'];
  $date2ft = DateTime::createFromFormat('d-m-Y', $date2);
  $time_fin = $date2ft->format('Y-m-d');
  $time_fin .= ' 23:59:59';

  // on affiche la periode visée
  if ($date1 === $date2) {
    $nomfic = "bilan_vente_$date1.csv";
    $csv_output = "Le $date1";
  } else {
    $nomfic = `bilan_vente_${$date1}_au_${$date2}.csv`;
    $csv_output = "Du $date1 au $date2";
  }

  if ($numero == '0') {
    $csv_output .= "\n\r";
    $csv_output .= "\nPour tous les points de vente\n\n";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    //Ligne des noms des champs
    $csv_output .= "Réf\tMoyen de paiement\tDate\tPoint de vente\tNbx d'obj\tTotal quantités\tTotal masse\tTotal prix\tTotal remboursement\n";
    //  }
    $req = $bdd->prepare('SELECT ventes.id, moyens_paiement.nom AS moyen_paiement, ventes.timestamp, points_vente.nom, count(vendus.id), sum(vendus.quantite), sum(masse* pesees_vendus.quantite),sum(prix*vendus.quantite),sum(remboursement)
    FROM ventes, vendus,points_vente, moyens_paiement, pesees_vendus WHERE DATE(ventes.timestamp) BETWEEN :du AND :au AND id_vente=ventes.id AND id_point_vente=points_vente.id AND ventes.id_moyen_paiement=moyens_paiement.id AND vendus.id = pesees_vendus.id GROUP BY ventes.id');
    $req->execute([':du' => $time_debut, ':au' => $time_fin]);
    while ($donnees = $req->fetch(PDO::FETCH_ASSOC)) {
      $csv_output .= implode("\t", $donnees) . "\n";
    }
    $req->closeCursor();
  } else {
    $csv_output .= "\n\r";
    $csv_output .= 'Pour le point numero:  ' . $numero . "\t";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    $csv_output .= "\n\r";
    //Ligne des noms des champs
    $csv_output .= "Réf\tMoyen de paiement\tDate\tNbx d'obj\tTotal quantités\tTotal masse\tTotal prix\tTotal remboursement\n";
    //  }
    $req = $bdd->prepare('SELECT ventes.id, moyens_paiement.nom AS moyen_paiement, ventes.timestamp, count(vendus.id), sum(vendus.quantite), sum(masse* pesees_vendus.quantite),sum(prix*vendus.quantite),sum(remboursement)
        FROM ventes, vendus, moyens_paiement, pesees_vendus WHERE DATE(ventes.timestamp) BETWEEN :du AND :au AND id_vente=ventes.id AND id_point_vente= :numero AND ventes.id_moyen_paiement=moyens_paiement.id AND vendus.id = pesees_vendus.id GROUP BY ventes.id');
    $req->execute([':du' => $time_debut, ':au' => $time_fin, ':numero' => $numero]);
    while ($donnees = $req->fetch(PDO::FETCH_ASSOC)) {
      $csv_output .= implode("\t", $donnees) . "\n";
    }
    $req->closeCursor();
  }
  $encoded_csv = mb_convert_encoding($csv_output, 'UTF-16LE', 'UTF-8');
  header('Content-Description: File Transfer');
  header('Content-Type: application/vnd.ms-excel');
  header('Content-type: application/vnd.ms-excel');
  header('Content-disposition: attachment; filename=' . $nomfic);
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  echo chr(255) . chr(254) . $encoded_csv;
} else {
  header('Location:../moteur/destroy.php');
}
