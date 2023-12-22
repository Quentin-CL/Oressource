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

$numero = filter_input(INPUT_GET, 'numero', FILTER_VALIDATE_INT);

if (is_valid_session() && $_SESSION['viz_caisse'] && is_allowed_vente_id($numero)) {
  require_once 'tete.php';

  $nb_viz_caisse = (int) ($_SESSION['nb_viz_caisse']);
?>

  <div class="container">
    <h1>Visualisation des <?= $nb_viz_caisse; ?> dernieres ventes</h1>
    <p align="right">
      <input class="btn btn-default btn-lg" type='button' name='quitter' value='Quitter' OnClick="window.close();" />
    </p>
    <div class="panel-body">
      <br>
    </div>

    <div class="row">
      <div class="col-md-11">
        <ul class="nav nav-tabs">
          <li><a href="viz_caisse.php?numero=<?= $numero; ?>">Ventes</a></li>
          <li class="active">
            <a href="#">Autres transactions</a>
          </li>
        </ul>
      </div>
    </div> <!-- row -->

    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date de création</th>
          <th>Somme</th>
          <th>Type de transaction</th>
          <th>Moyen de paiement</th>
          <th>Commentaire</th>
          <th>Auteur de la ligne</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach (viz_transaction($bdd, $numero, $nb_viz_caisse) as $transaction) { ?>
          <tr>
            <td><?= $transaction['id']; ?></td>
            <td><?= $transaction['date_creation']; ?></td>
            <td><?= $transaction['somme']; ?></td>
            <td><span class="badge" style="background-color: <?= $transaction['couleur']; ?>"><?= $transaction['type']; ?></span></td>
            <td><span class="badge" style="background-color: <?= $transaction['mpc']; ?>"><?= $transaction['moyen']; ?></span></td>
            <td><?= $transaction['commentaire']; ?></td>
            <td><?= $transaction['mail']; ?></td>
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
