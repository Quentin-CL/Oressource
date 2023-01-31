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
if (isset($_SESSION['id']) && $_SESSION['systeme'] === 'oressource' && (strpos($_SESSION['niveau'], 'h') !== false)) {
  require_once '../moteur/dbconfig.php';
  $timestamp = str_replace('T', ' ', $_POST["datetime"]) . ':00';
  $req = $bdd->prepare('UPDATE ventes 
  INNER JOIN vendus
  ON ventes.id = vendus.id_vente
  LEFT JOIN pesees_vendus AS pv
  ON vendus.id = pv.id
  SET ventes.commentaire = :commentaire, 
  ventes.id_last_hero = :id_last_hero, 
  ventes.last_hero_timestamp = NOW(), 
  ventes.timestamp = :timestamp,
  vendus.id_last_hero = ventes.id_last_hero,
  vendus.last_hero_timestamp = NOW(), 
  vendus.timestamp = ventes.timestamp,
  pv.id_last_hero = ventes.id_last_hero, 
  pv.last_hero_timestamp = NOW(), 
  pv.timestamp = ventes.timestamp,
  ventes.id_moyen_paiement = :id_moyen_paiement
  WHERE ventes.id = :id');
  $req->bindParam(':timestamp', $timestamp, PDO::PARAM_STR);
  $req->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
  $req->bindValue(':id_last_hero', $_SESSION['id'], PDO::PARAM_INT);
  $req->bindParam(':commentaire', $_POST['commentaire'], PDO::PARAM_STR);
  $req->bindValue(':id_moyen_paiement', $_POST['moyen'], PDO::PARAM_INT);
  $req->execute();
  $req->closeCursor();
  header('Location:../ifaces/verif_vente.php?numero=' . $_POST['npoint'] . '&date1=' . $_POST['date1'] . '&date2=' . $_POST['date2']);
} else {
  header('Location:../moteur/destroy.php');
}
