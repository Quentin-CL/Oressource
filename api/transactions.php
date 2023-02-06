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

require_once '../core/session.php';
require_once '../core/requetes.php';
require_once '../core/validation.php';

header("content-type:application/json");


function transaction_insert(PDO $bdd, array $transaction)
{
  $sql = 'INSERT INTO autres_transactions (
      timestamp,
      last_hero_timestamp,
      id_type_transactions,
      somme,
      commentaire,
      id_point_vente,
      id_createur,
      id_last_hero
    ) VALUES (
      :timestamp,
      :timestamp1,
      :id_type_transactions,
      :somme,
      :commentaire,
      :id_point,  
      :id_createur,
      :id_createur1)';
  $req = $bdd->prepare($sql);
  $req->bindValue(':timestamp', $transaction['date']->format('Y-m-d H:i:s'), PDO::PARAM_STR);
  $req->bindValue(':timestamp1', $transaction['date']->format('Y-m-d H:i:s'), PDO::PARAM_STR);
  $req->bindValue(':id_createur', $transaction['id_user'], PDO::PARAM_INT);
  $req->bindValue(':id_createur1', $transaction['id_user'], PDO::PARAM_INT);
  $req->bindValue(':id_point', $transaction['id_point'], PDO::PARAM_INT);
  $somme = parseFloat($transaction['somme']);
  $type = parseInt($transaction['id_type']);
  if ($somme >= 0.000 || $type === 1) {
    $req->bindValue(':somme', $somme, PDO::PARAM_STR);
    $req->bindValue(':commentaire', $transaction['commentaire'], PDO::PARAM_STR);
    $req->bindValue(':id_type_transactions', $type, PDO::PARAM_INT);
    $req->execute();
  } else {
    $req->closeCursor();
    throw new UnexpectedValueException('somme < 0.00 ');
  }
  $req->closeCursor();
  return $bdd->lastInsertId();
}

if (is_valid_session()) {
  require_once '../moteur/dbconfig.php';

  $json_raw = file_get_contents('php://input');
  $unsafe_json = json_decode($json_raw, true);
  $json = $unsafe_json;

  if (!is_allowed_vente_id($json['id_point'])) {
    http_response_code(403); // Forbiden.
    echo (json_encode(['error' => 'Action interdite.'], JSON_FORCE_OBJECT));
    die();
  }

  try {
    $json['date'] = allowDate($json) ? parseDate($json['date']) : new DateTime('now');
  } catch (UnexpectedValueException $ex) {
    http_response_code(400); // Bad Request
    echo (json_encode(['error' => $ex->getMessage()]));
    die();
  }

  $bdd->beginTransaction();
  try {
    $transaction_id = transaction_insert($bdd, $json);
    $bdd->commit();
    http_response_code(200); // Created
    echo (json_encode(['id' => $transaction_id], JSON_NUMERIC_CHECK));
  } catch (UnexpectedValueException $e) {
    $bdd->rollback();
    http_response_code(400); // Bad Request
    echo (json_encode(['error' => $e->getMessage()]));
  } catch (PDOException $e) {
    $bdd->rollback();
    http_response_code(500); // Internal Server Error
    echo (json_encode(['error' => 'Une erreur est survenue dans Oressource transaction annulée.']));
    throw $e;
  }
} else {
  http_response_code(401); // Unauthorized.
  echo (json_encode(['error' => "Session Invalide ou expiree."], JSON_FORCE_OBJECT));
  die();
}
