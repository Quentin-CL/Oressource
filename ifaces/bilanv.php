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
// Oressource 2017, Bilan des ventes
session_start();

require_once '../core/session.php';
require_once '../core/requetes.php';
require_once '../core/composants.php';

if (is_valid_session() && is_allowed_bilan()) {
  require_once './tete.php';
  require_once '../moteur/dbconfig.php';

  $date1ft = DateTime::createFromFormat('d-m-Y', $_GET['date1']);
  $time_debut = $date1ft->format('Y-m-d') . ' 00:00:00';
  $date1 = $date1ft->format('d-m-Y');

  $date2ft = date_create_from_format('d-m-Y', $_GET['date2']);
  $time_fin = $date2ft->format('Y-m-d') . ' 23:59:59';
  $date2 = $date2ft->format('d-m-Y');

  $date_query = "date1=$date1&date2=$date2";
  $numero = filter_input(INPUT_GET, 'numero', FILTER_VALIDATE_INT) ?? 0;

  $bilans = bilan_ventes($bdd, $time_debut, $time_fin, $numero);
  $bilans_types = bilan_ventes_par_type($bdd, $time_debut, $time_fin, $numero);
  $bilans_pesees_types = bilan_ventes_pesees($bdd, $time_debut, $time_fin, $numero);
  $chiffre_affaire = chiffre_affaire_mode_paiement($bdd, $time_debut, $time_fin, $numero);
  $nb_ventes = nb_ventes($bdd, $time_debut, $time_fin, $numero);
  $remb_nb = nb_remboursements($bdd, $time_debut, $time_fin, $numero);
  $bilans_transactions = bilan_transactions_par_type($bdd, $time_debut, $time_fin, $numero);
  $bilan_tran = bilan_transactions($bdd, $time_debut, $time_fin, $numero);
  $nb_tran = $bilan_tran['nb_tran'];

  $isNegativeSubstraction = contain_a_negative_value(array_column($bilans_types, 'chiffre_degage'), array_column($bilans_types, 'remb_somme'));
  $points_ventes = filter_visibles(points_ventes($bdd));
  $bilan_pesee_mix = array_reduce(array_keys($bilans_pesees_types), function ($acc, $e)
  use ($bilans_pesees_types, $bilans_types) {
    if (isset($bilans_types[$e])) {
      $acc[$e] = array_merge($bilans_types[$e], $bilans_pesees_types[$e]);
      return $acc;
    }
    return $acc;
  }, []);

  $panier_moyen = $nb_ventes === 0 ? 'Non défini' : round($bilans['chiffre_degage'] / $nb_ventes, 2) . "€";

  $graphMv = data_graphs_from_bilan($bilans_pesees_types, 'vendu_masse');
  $graphPv = data_graphs_from_bilan($bilans_types, 'chiffre_degage');
?>

  <div class="container">
    <div class="row">
      <div class="col-md-11">
        <h1>Bilan global</h1>
        <div class="col-md-4 col-md-offset-8">
          <?= datePicker() ?>
        </div>

        <ul class="nav nav-tabs">
          <li>
            <a href="bilanc.php?<?= $date_query; ?>&numero=0">Collectes</a>
          </li>
          <li>
            <a href="bilanhb.php?<?= $date_query; ?>&numero=0">Sorties hors-boutique</a>
          </li>
          <li class="active"><a href="#">Recettes</a></li>
        </ul>
      </div>
    </div> <!-- row -->
  </div> <!-- container -->

  <hr />
  <div class="row">
    <div class="col-md-8 col-md-offset-1">
      <h2>Bilan des recettes de la structure</h2>
      <ul class="nav nav-tabs">
        <?php foreach ($points_ventes as $point_vente) { ?>
          <li class="<?= ($numero == $point_vente['id'] ? 'active' : ''); ?>">
            <a href="bilanv.php?<?= $date_query; ?>&numero=<?= $point_vente['id']; ?>"><?= $point_vente['nom']; ?></a>
          </li>
        <?php } ?>
        <li class="<?= ($numero === 0 ? 'active' : ''); ?>">
          <a href="bilanv.php?<?= $date_query; ?>&numero=0">Tous les points</a>
        </li>
      </ul>

      <div class="row">
        <h2><?= ($date1 === $date2) ? "Le $date1" : "Du $date1 au $date2"; ?> :</h2>
        <?php if (!($nb_ventes > 0 || $remb_nb > 0 || $nb_tran > 0)) { ?>
          <img src="../images/nodata.jpg" class="img-responsive" alt="Responsive image">
        <?php
        } else { ?>
          <div class="row">
            <div class="col-md-6">
              <table class='table table-hover'>
                <tbody>
                  <?php if ($numero === 0) { ?>
                    <tr>
                      <td>Nombre de points de vente :</td>
                      <td><?= count($points_ventes) ?></td>
                    </tr>
                  <?php } ?>
                  <tr>
                    <td>Chiffre total dégagé (vente) :</td>
                    <td><?= $bilans['chiffre_degage']; ?> €</td>
                  </tr>
                  <tr>
                    <td>Nombre d'objets vendus :</td>
                    <td><?= $bilans['vendu_quantite']; ?></td>
                  </tr>
                  <tr>
                    <td>Nombre de ventes :</td>
                    <td><?= $nb_ventes; ?></td>
                  </tr>
                  <tr>
                    <td>Panier moyen :</td>
                    <td><?= $panier_moyen ?></td>
                  </tr>
                  <tr>
                    <td>Nombre d'objets remboursés :</td>
                    <td><?= $bilans['remb_quantite']; ?>
                    </td>
                  </tr>
                  <tr>
                    <td>Nombre de remboursemments :</td>
                    <td><?= $remb_nb; ?></td>
                  </tr>
                  <tr>
                    <td>Somme remboursée :</td>
                    <td><?= $bilans['remb_somme']; ?> €</td>
                  </tr>
                  <tr>
                    <td>Nombre d'autres transactions :</td>
                    <td><?= $nb_tran; ?></td>
                  </tr>
                  <tr>
                    <td>Somme totale perçue (transaction) :</td>
                    <td><?= $bilan_tran['chiffre_total']; ?> €</td>
                  </tr>
                  <tr>
                    <td>Masse pesée en caisse :</td>
                    <td><?= $bilans['vendu_masse']; ?> kg</td>
                  </tr>
                </tbody>

                <tfoot>

                  <tr>
                    <td align=center colspan=3>
                      <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          Exporter (.csv) <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                          <li><a href="../moteur/export_bilanv.php?numero=<?= $numero; ?>&<?= $date_query; ?>">Ventes detaillées</a></li>
                          <li><a href="../moteur/export_bilanv_partype.php?numero=<?= $numero; ?>&<?= $date_query; ?>">Ventes par type</a></li>
                          <li><a href="../moteur/export_bilanv_transaction.php?numero=<?= $numero; ?>&<?= $date_query; ?>">Transactions</a></li>
                        </ul>
                      </div>
                    </td>
                  </tr>

                  <br>
                </tfoot>
              </table>

              <h3>Récapitulatif par mode de paiement</h3>

              <table class='table table-hover'>
                <thead>
                  <tr>
                    <th>Moyen de Paiement</th>
                    <th>Nombre de Ventes</th>
                    <th>Nombre de Transactions</th>
                    <th>Chiffre Dégagé en €</th>
                    <th>Somme remboursée en €</th>
                  </tr>
                </thead>

                <tbody>
                  <?php foreach ($chiffre_affaire as $ligne) { ?>
                    <tr>
                      <td><?= $ligne['moyen']; ?></td>
                      <?php if (isset($ligne['total_vendue']) && isset($ligne['total_transaction'])) { ?>
                        <td><?= $ligne['quantite_vendue'] ?></td>
                        <td><?= $ligne['quantite_transaction'] ?></td>
                        <td><?= $ligne['total_vendue'] + $ligne['total_transaction']; ?></td>
                        <td><?= $ligne['remboursement']; ?></td>
                      <?php } else if (isset($ligne['total_vendue'])) { ?>
                        <td><?= $ligne['quantite_vendue'] ?></td>
                        <td>0</td>
                        <td><?= $ligne['total_vendue'] ?></td>
                        <td><?= $ligne['remboursement']; ?></td>
                      <?php } else { ?>
                        <td>0</td>
                        <td><?= $ligne['quantite_transaction'] ?></td>
                        <td><?= $ligne['total_transaction'] ?></td>
                        <td>0.00</td>
                      <?php } ?>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
              <h4>Chiffre dégagé par type d'objet: </h4>
              <div id="graphPV" style="height: 180px;"></div>
            </div>

            <div class="col-md-6 ">
              <h3 style="text-align:center;">Chiffre de caisse : <?= $bilans['chiffre_degage'] - $bilans['remb_somme'] + $bilan_tran['chiffre_total']; ?> €</h3>
              <h4>Récapitulatif par type d'objet</h4>
              <h6 style="color : red"><em>
                  <?=
                  $isNegativeSubstraction ? "Au moins un des bilans par type d'objet est negatif et empêche le calcul des proportions de vente" : ""
                  ?>
                </em></h6>
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Type d'objet</th>
                    <th>Chiffre dégagé en €</th>
                    <th>Quantité vendue</th>
                    <th>Somme remboursée en €</th>
                    <th>Quantité remboursée</th>
                    <th>Proportion de vente (% du chiffre dégagé)</th>
                  </tr>
                </thead>

                <tbody>
                  <?php foreach ($bilans_types as $id => $bilan_type) { ?>
                    <tr>
                      <th scope="row">
                        <a href="./jours.php?<?= $date_query; ?>&type=<?= $id; ?>"><?= $bilan_type['nom']; ?></a>
                      </th>
                      <td><?= $bilan_type['chiffre_degage']; ?></td>
                      <td><?= $bilan_type['vendu_quantite']; ?></td>
                      <td><?= $bilan_type['remb_somme']; ?></td>
                      <td><?= $bilan_type['remb_quantite']; ?></td>
                      <td>
                        <?=
                        $isNegativeSubstraction ? " " :
                          round((($bilan_type['chiffre_degage'] - $bilan_type['remb_somme']) / ($bilans['chiffre_degage'] - $bilans['remb_somme'])) * 100, 2);
                        ?>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>

              <h4>Récapitulatif des autres transactions</h4>
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Type de transaction</th>
                    <th>Chiffre dégagé en €</th>
                    <th>Nombre</th>
                  </tr>
                </thead>

                <tbody>
                  <?php foreach ($bilans_transactions as $id => $bilan_transaction) { ?>
                    <tr>
                      <th scope="row">
                        <?= $bilan_transaction['nom']; ?>
                      </th>
                      <td><?= $bilan_transaction['chiffre_degage']; ?></td>
                      <td><?= $bilan_transaction['quantite']; ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>


              <h3>Récapitulatif des masses pesées à la caisse</h3>
              <h5><em>Les objets non pesés sont ignorés dans le bilan des masses</em></h5>

              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Type d'objet</th>
                    <th>Masse pesée en kg</th>
                    <th>Nombre de pesées</th>
                    <th>Nombre d'objets non pesés</th>
                    <th>Nombre d'objets vendus</th>
                    <th>Masse sortie totale en kg</th>
                    <th>Prix à la tonne en €</th>
                    <th>Proportion de vente (% de la masse)</th>
                  </tr>
                </thead>

                <tbody>
                  <?php
                  // TODO: Mettre des noms de variables explicites.
                  $masse_totale_vendus = array_sum(array_column($bilan_pesee_mix, 'vendu_masse'));
                  foreach ($bilan_pesee_mix as $id => $bilan_mix) {
                    $chiffre_degage = $bilan_mix['chiffre_degage'];
                    $id_type_dechet = $id;
                    $vendus_pesees = $bilan_mix['quantite_pesee_vendu'];
                    $Mtpe = (float) $bilan_mix['vendu_masse'];
                    $Ntpe = (int) $bilan_mix['nb_pesees_ventes'];
                    $Notpe = (int) $bilan_mix['quantite_pesee_vendu'];
                    $obj_vendu = (int) $bilan_mix['vendu_quantite'];
                    $masse_pesee = (float) $bilan_mix['pesee_masse'];
                    $remb_quantite = (int) $bilan_mix['remb_quantite'];
                  ?>
                    <tr>
                      <th scope="row">
                        <a href="./jours.php?<?= $date_query; ?>&type=<?= $id; ?>"><?= $bilan_mix['nom']; ?></a>
                      </th>
                      <td><?= round($masse_pesee, 2); ?></td>
                      <td><?= round($Ntpe, 2); ?></td>
                      <td><?= $obj_vendu - $Notpe - $remb_quantite; ?></td>
                      <td><?= $obj_vendu  - $remb_quantite; ?></td>
                      <td><?= round($Mtpe, 2); ?></td>
                      <td><?= round(($chiffre_degage / $Mtpe) * 1000, 2); ?></td>
                      <td><?= round(($Mtpe / $masse_totale_vendus) * 100, 2); ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>

              <h4>Masses vendus par type d'objet :</h4>

              <div id="graphMV" style="height: 180px;"></div>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <script type="text/javascript">
    'use strict';

    $(document).ready(() => {
      const dataMv = <?= json_encode($graphMv, JSON_NUMERIC_CHECK); ?>;
      graphMorris(dataMv, 'graphMV', 'Kgs.');
      const dataPv = <?= json_encode($graphPv, JSON_NUMERIC_CHECK); ?>;
      graphMorris(dataPv, 'graphPV', '€');
    });
  </script>

<?php
  require_once 'pied.php';
} else {
  header('Location: ../moteur/destroy.php');
}
