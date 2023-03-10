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

if (is_valid_session() && $_SESSION['viz_caisse'] && is_allowed_collecte_id($numero)) {
  require_once 'tete.php';

  $nb_viz_collecte = (int) ($_SESSION['nb_viz_caisse']);
?>

  <div class="container">
    <h1>Visualisation des <?= $nb_viz_collecte; ?> dernieres collectes</h1>
    <p align="right">
      <input class="btn btn-default btn-lg" type='button' name='quitter' value='Quitter' OnClick="window.close();" />
    </p>
    <div class="panel-body">
      <br>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date de création</th>
          <th>Type de collecte</th>
          <th>Localité</th>
          <th>Masse</th>
          <th>Nombre d'objets</th>
          <th>Commentaire</th>
          <th>Auteur de la ligne</th>
          <th>Visualiser</th>
        </tr>
      </thead>


      <tbody>
        <?php foreach (viz_collectes($bdd, $numero, $nb_viz_collecte) as $collecte) { ?>
          <tr>
            <td><?= $collecte['id']; ?></td>
            <td><?= $collecte['date_creation']; ?></td>
            <td><?= $collecte['type_collecte']; ?></td>
            <td><span class="badge" style="background-color: <?= $collecte['couleur']; ?>"><?= $collecte['localite']; ?></span></td>
            <td><?= $collecte['masse']; ?></td>
            <td><?= $collecte['quantite']; ?></td>
            <td style="width:100px"><?= $collecte['commentaire']; ?></td>
            <td><?= $collecte['mail']; ?></td>
            <td>
              <form action="viz_collecte.php?ncollecte=<?= $collecte['id']; ?>" method="post">
                <input type="hidden" name="id" id="id" value="<?= $collecte['id']; ?>">
                <input type="hidden" name="npoint" id="npoint" value="<?= $numero; ?>">
                <button class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-search"></span></button>
              </form>
            </td>
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
