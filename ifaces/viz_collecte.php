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

if (is_valid_session() && is_allowed_collecte() && $_SESSION['viz_caisse']) {
  require_once '../moteur/dbconfig.php';
  $users = map_by(utilisateurs($bdd), 'id');

  $req = $bdd->prepare('SELECT pc.id, 
  pc.timestamp, 
  type_dechets.nom AS type, 
  type_dechets.couleur,
  pc.masse,
  pc.id_createur, 
  utilisateurs.mail 
  FROM pesees_collectes AS pc
  INNER JOIN type_dechets 
  ON type_dechets.id = pc.id_type_dechet 
  INNER JOIN utilisateurs 
  ON utilisateurs.id = pc.id_createur 
  WHERE pc.id_collecte = :id_collecte
  GROUP BY pc.id');
  $req->execute(['id_collecte' => $_GET['ncollecte']]);
  $donnees = $req->fetchAll(PDO::FETCH_ASSOC);
  $req->closeCursor();

  require_once 'tete.php';
?>
  <div class="container">
    <h1>Visualiser la collecte n° <?= $_GET['ncollecte']; ?></h1>
    <p align="right">
      <input class="btn btn-default btn-lg" type='button' name='quitter' value='Quitter' OnClick="window.close();" />
    </p>
    <div class="panel-body">
      <br>

    </div>
    <h1>Objets inclus dans cette collecte</h1>

    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date de création</th>
          <th>Type d'objet:</th>
          <th>Masse</th>
          <th>Auteur de la ligne</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($donnees as $d) { ?>
          <tr>
            <td><?= $d['id']; ?></td>
            <td><?= $d['timestamp']; ?></td>
            <td><span class="badge" style="background-color: <?= $d['couleur']; ?>"><?= $d['type']; ?></span></td>
            <td><?= $d['masse']; ?></td>
            <td><?= $users[$d['id_createur']]['mail'] ?></td>
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