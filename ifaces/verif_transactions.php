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
require_once '../core/validation.php';
require_once '../core/requetes.php';
require_once '../core/composants.php';

if (is_valid_session() && is_allowed_verifications()) {

  require_once('../moteur/dbconfig.php');
  $points_ventes = filter_visibles(points_ventes($bdd));
  $date1 = $_GET['date1'];
  $date2 = $_GET['date2'];
  $numero = filter_input(INPUT_GET, 'numero', FILTER_VALIDATE_INT);
  $time_debut = DateTime::createFromFormat('d-m-Y', $date1)->format('Y-m-d') . ' 00:00:00';
  $time_fin = DateTime::createFromFormat('d-m-Y', $date2)->format('Y-m-d') . ' 23:59:59';

  $req = $bdd->prepare('SELECT
  autres_transactions.id as id,
  autres_transactions.timestamp as timestamp,
  autres_transactions.somme as somme,
  type_transactions.nom as type,
  type_transactions.id as type_id,
  type_transactions.couleur as couleur,
  autres_transactions.commentaire as commentaire,
  autres_transactions.last_hero_timestamp as lht,
  utilisateurs.mail as mail,
  moyens_paiement.nom as moyen,
  moyens_paiement.couleur as mpc
  from autres_transactions
  inner join type_transactions
  on autres_transactions.id_type_transactions = type_transactions.id
  and DATE(autres_transactions.timestamp) BETWEEN :du AND :au
  and autres_transactions.id_point_vente = :id_point_vente
  inner join utilisateurs
  on utilisateurs.id = autres_transactions.id_createur
  left join moyens_paiement
  on moyens_paiement.id = autres_transactions.id_moyen_paiement
  group by autres_transactions.id, autres_transactions.timestamp, type_transactions.nom,
  type_transactions.couleur, autres_transactions.commentaire,
  autres_transactions.last_hero_timestamp, utilisateurs.mail
  order by autres_transactions.timestamp desc
');
  $req->bindParam(':id_point_vente', $numero, PDO::PARAM_INT);
  $req->bindParam(':du', $time_debut, PDO::PARAM_STR);
  $req->bindParam(':au', $time_fin, PDO::PARAM_STR);
  $req->execute();
  $data = $req->fetchAll(PDO::FETCH_ASSOC);

  require_once 'tete.php';
?>

  <div class="container">
    <h1>Verification des ventes et des transactions</h1>

    <div class="row">
      <div class="col-md-11">
        <ul class="nav nav-tabs">
          <li><a href="verif_vente.php?numero=<?= $numero; ?>&date1=<?= $date1 ?>&date2=<?= $date2 ?>">Ventes</a></li>
          <li class="active">
            <a href="#">Autres transactions</a>
          </li>
        </ul>
      </div>
    </div> <!-- row -->

    <div class="panel-body">
      <ul class="nav nav-tabs">
        <?php foreach ($points_ventes as $point) { ?>
          <li <?= $numero === $point['id'] ? 'class="active"' : '' ?>>
            <a href="verif_vente.php?numero=<?= $point['id'] ?>&date1=<?= $date1 ?>&date2=<?= $date2 ?>"><?= $point['nom']; ?></a>
          </li>
        <?php } ?>
      </ul>

      <br>
      <div class="row">
        <?= datePicker() ?>
        <?= $date1 === $date2 ? " Le {$date1}," : " Du {$date1} au {$date2}," ?>
      </div>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Moment de la transaction</th>
          <th>Type de transaction</th>
          <th>Moyen de paiement</th>
          <th>Somme</th>
          <th>Commentaire</th>
          <th>Auteur</th>
          <th></th>
          <th>Modifié par</th>
          <th style="width:100px">Le</th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($data as $t) {
        ?>
          <tr>
            <td><?= $t['id'] ?></td>
            <td><?= $t['timestamp']; ?></td>
            <td><span class="badge" style="background-color: <?= $t['couleur']; ?>"><?= $t['type']; ?></span></td>
            <td><span class="badge" style="background-color: <?= $t['mpc']; ?>"><?= $t['moyen']; ?></span></td>
            <td><?= $t['somme'] ?></td>
            <td style="width:100px"><?= $t['commentaire']; ?></td>
            <td><?= $t['mail'] ?></td>
            <td>
              <form action="modification_verification_transaction.php?ntran=<?= $t['id']; ?>" method="post">
                <input type="hidden" name="type" value="<?= $t['type']; ?>">
                <input type="hidden" name="type_id" value="<?= $t['type_id']; ?>">
                <input type="hidden" name="moyen" value="<?= $t['moyen']; ?>">
                <input type="hidden" name="id" value="<?= $t['id']; ?>">
                <input type="hidden" name="date1" value="<?= $date1 ?>">
                <input type="hidden" name="date2" value="<?= $date2; ?>">
                <input type="hidden" name="npoint" value="<?= $numero; ?>">
                <button class="btn btn-warning btn-sm">Modifier</button>
              </form>
            </td>
            <td><?= $t['lht'] !== $t['timestamp'] ? $t['mail'] : '' ?></td>
            <td><?= $t['lht'] !== $t['timestamp'] ? $t['lht'] : '' ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div><!-- /.container -->
<?php
  require_once 'pied.php';
} else {
  header('Location: ../moteur/destroy.php');
}
?>