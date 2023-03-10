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
  $req = $bdd->prepare('UPDATE collectes 
                        INNER JOIN pesees_collectes AS pc
                        ON collectes.id = pc.id_collecte
                        SET collectes.id_type_collecte = :id_type_collecte,
                          collectes.localisation = :localisation,
                          collectes.id_last_hero = :id_last_hero,
                          collectes.last_hero_timestamp = NOW(),
                          collectes.commentaire =:commentaire,
                          collectes.timestamp = :timestamp,
                          pc.timestamp = collectes.timestamp,
                          pc.last_hero_timestamp = collectes.last_hero_timestamp,
                          pc.id_last_hero = collectes.id_last_hero
                        WHERE collectes.id = :id');
  $req->execute(['id_type_collecte' => $_POST['id_type_collecte'], 'localisation' => $_POST['localisation'], 'id' => $_POST['id'], 'id_last_hero' => $_SESSION['id'], 'commentaire' => $_POST['commentaire'], 'timestamp' => $timestamp]);
  $req->closeCursor();
  header('Location:../ifaces/modification_verif_collecte.php?id=' . $_POST['id']);
} else {
  header('Location:../moteur/destroy.php');
}
