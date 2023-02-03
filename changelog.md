# Carnet de changement du projet

## Sémantique des numéros de version

Le premier numéro indique des changements majeurs qui cassent par exemple l'api
ou des usages du logiciel
Le second numéro des changements moyen visible par le developpeur ou
l'utilisateur.
Le dernier des fix de bug.

## Changements

Dans ce fichier, nous tachons de tenir un historique humainement suivable des
changements apporté au logiciel.

### Version Quentin

Version comprenant des ameliorations d'ergonomie du logiciel et d'ajout de fonctionnalités légéres.

- Deplacement des modules type d'objet/ticket de caisse
- Changement du style des types d'objet
- Vendu en lot par defaut
- Suppression du 0 par defaut dans l'input prix unitaire et masse unitaire de l'onglet point de vente
- Correction d'un bug qui rendait impossible l'ajout de sortie hors-boutique. Il y avait une violation de contrainte de clé étrangère lors de l'ajout ou de la mise à jour d'une ligne enfant dans la table "pesees_sorties". Il s'agit d'une erreur de la base de données SQL qui se produit lorsqu'une valeur de clé étrangère dans la table "pesees_sorties" ne correspond à aucune valeur de clé primaire dans la table "types_poubelles" par exemple. Correction en mettant une valeur par defaut "Null" à ces clés étrangeres.
- Fix de la fonctionnalité de visualisation d'un vente en particulier accesible dans la page "Visualiser les 30 dernières ventes
- Ajout de l'historique des 30 dernieres collectes sur le modèles des 30 dernieres ventes
- Ajout de l'affichage momentané des 3 dernieres ventes
- L'export des donnée des bilans des ventes et des collectes en .csv est rendu fonctionnel mais a encore besoin d'être amelioré
- Ajout de la possibilité de modifier la date de création de chaque ventes
- Fix d'un bug qui empeché la visualisation des sorties poubelles et déchetterie dans la page verifier les sorties hors-boutiques. Ajout d'un "type" qui est manquant pour ces catégories.
- Suppression du mecanisme qui affectait une point moyen faux à toute les entités non pesées dans le bilan des ventes. On ignore maintenant les entités non pesées et on affecte le même poids à toute les entités d'une même ticket et d'un même type. Ajout des proportions de vente sur le bilan de masse et de chiffre d'affaire.
- Suppresion de la possibilité de changer la quantité pour la vente par lot. Elle est fixé à un 1.

### Version v0.3.x

- PR[424](https://github.com/mart1ver/oressource/pull/424) issue [#405](https://github.com/mart1ver/oressource/issues/405) fix 0/1 au lieu d'un terme comme oui/non dans les editions de type de poubelles
- PR[420](https://github.com/mart1ver/oressource/pull/420) Fix issue [419](https://github.com/mart1ver/oressource/issues/419) impossibles de saisir plusieurs objets avec masse en vente
- PR[#413](https://github.com/mart1ver/oressource/pull/413) travis.ci retiré car on utilisais pas mais ça gachais des ressources de calcul partagées
- PR[#417](https://github.com/mart1ver/oressource/pull/417) Reduction de la taille de la ligne de séparation des tickets d'impression
- PR[#415](https://github.com/mart1ver/oressource/pull/415) Ajout CSS pour impression tickets sur Chromium a test sur Firefox!

### Version v0.3.0

Version comprenant principalement corrections de bugs sur la
gestion des impressions papier et leur formattage.

- Fix erreurs de syntaxes dans le script d'installation web + cleanup.
- Fix-PR [#408](https://github.com/mart1ver/oressource/pull/408/files) Plusieurs bugs sur les sorties.
- PR: [#412](https://github.com/mart1ver/oressource/pull/412) Amélioration interface des verif de sorties le bouton est tout a gauche maintenant aussi factorisé la gestion des vues dans ce code.
- fix [#399](https://github.com/mart1ver/oressource/issues/399) bug des sorties
  seules les sorties don etait prises en compte :(
- fix [#406](https://github.com/mart1ver/oressource/issues/406) + pr [#407](https://github.com/mart1ver/oressource/issues/407) si aucune localité etait renseignée une sortie etait impossible
- fix [#396](https://github.com/mart1ver/oressource/issues/396) + pr [#404](https://github.com/mart1ver/oressource/issues/404): moyen de paiement affiché sur les tickets de caisse.
- Fix d'une erreur dans le script travis merci @HoverEpic PR [#383](https://github.com/mart1ver/oressource/issues/383).
- PR [#341](https://github.com/mart1ver/oressource/issues/341) : Ajout d'un script par @yvan-sraka pour vérifier des une mise a jour existe.
- Fix [#396](https://github.com/mart1ver/oressource/issues/396) PR [#402](https://github.com/mart1ver/oressource/issues/402) : @AureliaDolo Ajouts d'informations dans le ticket de caisse
  dates, mentions legales, reformattage.
- Corrections de soucis d'importance mineures detecter par eslint
- Correction de fautes de styles javascript vu par eslint.
- Mise en place d'une configuration pour le verificateur syntaxique [eslint](https://eslint.org/)
- Début du changement de convention de nommage sur le javascript (conformité
  standard communautaire JS).
- Mise à jour massive de la documentation de vente.js format jsdoc.
- Mise à jour de la documentation de ticket.js format jsdoc.
- [#391](https://github.com/mart1ver/oressource/issues/391) Correction d'un
  bug dans le calcul de TVA
- Ajout de la mention «HT» quand l'association n'est pas assujetie à la TVA,
  reformatage de la mention avec TTC lorsque la structure est assujetie
- [#392](https://github.com/mart1ver/oressource/issues/392) Affichage des
  quantitées dans les tickets de caisse papier
- [#393](https://github.com/mart1ver/oressource/issues/393) Tenir un carnet
  des changements apporté au logiciel

### version 0.2.0 et antérieur

Très grosse refonte générale, changement sur la base de donnée, corrections de
bugs.
