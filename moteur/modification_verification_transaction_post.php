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
  $moyen = ($_POST['type_id'] === '1' ? '' : 'at.id_moyen_paiement = ' . $_POST['moyen'] . ',');
  $req = $bdd->prepare("UPDATE autres_transactions AS at
  SET at.commentaire = :commentaire, 
  at.id_last_hero = :id_last_hero, 
  at.last_hero_timestamp = NOW(), 
  $moyen
  at.somme = :somme,
  at.timestamp = :timestamp
  WHERE at.id = :id");
  $req->bindParam(':timestamp', $timestamp, PDO::PARAM_STR);
  $req->bindValue(':id', $_POST['id'], PDO::PARAM_INT);
  $req->bindValue(':id_last_hero', $_SESSION['id'], PDO::PARAM_INT);
  $req->bindParam(':commentaire', $_POST['commentaire'], PDO::PARAM_STR);
  $req->bindValue(':somme', $_POST['somme'], PDO::PARAM_INT);
  $req->execute();
  $req->closeCursor();
  header('Location:../ifaces/verif_transactions.php?numero=' . $_POST['npoint'] . '&date1=' . $_POST['date1'] . '&date2=' . $_POST['date2']);
} else {
  header('Location:../moteur/destroy.php');
}
