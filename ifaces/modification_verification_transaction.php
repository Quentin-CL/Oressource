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

require_once '../core/requetes.php';
require_once '../core/session.php';

if (is_valid_session() && is_allowed_verifications()) {
  require_once '../moteur/dbconfig.php';
  $users = map_by(utilisateurs($bdd), 'id');


  $reponse = $bdd->prepare('SELECT commentaire, timestamp, somme FROM autres_transactions WHERE id = :id_tran');
  $reponse->execute(['id_tran' => $_GET['ntran']]);
  $transaction = $reponse->fetch();
  $commentaire = $transaction['commentaire'];
  $timestamp = substr(str_replace(' ', 'T', $transaction['timestamp']), 0, -3);
  $somme = $transaction['somme'];
  $reponse->closeCursor();
  $nowDate = time();
  $mysql_nowDate = date("Y-m-d H:i:s", $nowDate);

  require_once 'tete.php';
?>
  <div class="container">
    <h1>Modifier la transaction <em><?= $_POST['type']; ?></em> n° <?= $_GET['ntran']; ?></h1>
    <div class="panel-body">
      <br>
      <div class="row">
        <form action="../moteur/modification_verification_transaction_post.php?ntran=<?= $_GET['ntran']; ?>" method="post">
          <input type="hidden" name="id" value="<?= $_GET['ntran']; ?>">
          <input type="hidden" name="date1" value="<?= $_POST['date1']; ?>">
          <input type="hidden" name="date2" value="<?= $_POST['date2']; ?>">
          <input type="hidden" name="npoint" value="<?= $_POST['npoint']; ?>">

          <div class="col-md-3">
            <label for="datetime">Date de création :</label>
            <input type="datetime-local" name="datetime" id="datetime" max="<?= $mysql_nowDate ?>" value="<?= $timestamp ?>">
          </div>

          <div class="col-md-3">
            <label for="somme">Somme perçue :</label>
            <input type="number" name="somme" id="somme" value="<?= $somme ?>">
          </div>

          <div class="col-md-3">
            <label for="commentaire">Commentaire :</label>
            <textarea name="commentaire" id="commentaire" class="form-control"><?= $commentaire ?></textarea>
          </div>

          <div class="col-md-3">
            <br>
            <button name="creer" class="btn btn-warning">Modifier</button>
            <a href="verif_vente.php?date1=<?= $_POST['date1']; ?>&date2=<?= $_POST['date2']; ?>&numero=<?= $_POST['npoint']; ?>">
              <button name="creer" class="btn" style="float: right;">Annuler</button>
            </a>
          </div>
        </form>
      </div>
    </div>

  <?php
  require_once 'pied.php';
} else {
  header('Location: ../moteur/destroy.php');
}
  ?>