'use strict';
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

// use Ticket from ticket.js

//! L'interface des ventes est gérée à l'aide d'un état interne sous forme d'objets JavaScript
//! et de fonctions les manipulants et d'une interface graphique en HTML+CSS.
//!
//! «L'état de référence» sont les objets JS. L'interface graphique n'est qu'une «vue» des données
//! Par exemple clicker sur «ajouter» rajoute dans la représentation interne d'un panier de
//! vente l'objet, le prix et la quantité désirée et ensuite met à jour l'interface graphique.
//! La page et les données sont remises à 0, en cas d'envoi réussi (avec ou sans impressions)
//! ou de click sur la remise à 0.

/**
 * Represente une ligne du ticket de caisse type, quantitité et objet.
 * @typedef {{type: {type: string, couleur: string}, objet: {prix: number, masse: number, couleur: string, nom: string}}} Item
 */

/**
 * Calcul la somme des quantité sur un ticket
 * @return {number} Sommes de quantité du ticket
 */
Ticket.prototype.sum_quantite = function () {
  return this.to_array()
    .reduce((acc, { quantite }) => acc + quantite, 0);
};

/**
 * Calcul la somme des prix sur un ticket
 * @return {number} sommes des prix du ticket
 */
Ticket.prototype.sum_prix = function () {
  return (this
    .to_array()
    .reduce((acc, { lot, prix, quantite }) => (
      acc + (lot ? prix : (prix * quantite))
    ), 0.0));
};

/** Type representant l'état d'une vente ce type fait le
 * lien avec l'interface et la logique interne.
 *
 * @typedef {Object} Ticket définit dans ticket.js
 * @typedef {{
 * moyen: number,  // especes
 * ticket: Ticket,
 * last: undefined|Item
 * vente_unite: boolean
 * }} EtatVente
 */

/**
 * Crée un nouvel état de vente representé par un objet Js:
 *
 * - `ticket` : `Ticket` class définie dans ticket.js
 * - `last` : dernier item ajouté au panier
 * - `vente_unite`: d'un mode de vente
 * - `moyen`: id moyen de paiement dans `window.OressourceEnv.moyens_paiement`
 *    defini dans `ventes.php`
 * @returns {EtatVente} Objet represantant l'etat d'une vente
 */
function new_state() {
  const s = {
    // TODO: Attention c'est hardcodé alors que c'est géré en base!
    // Voir commentaire de la fonction! :)
    // Vente par lot passé par defaut 
    moyen: 1,
    ticket: new Ticket(),
    last: undefined,
    vente_unite: window.OressourceEnv.lots ? false : true,
  };
  // Hack pour les impressions...
  window.OressourceEnv.tickets = s.ticket;
  return s;
}

/** Crée l'objet qui gère les données du clavier visuel,
 * avec '' pour valeur par défaut à tout les champs.
 * @return {Numpad}
*/
function new_numpad() {
  return {
    prix: '',
    quantite: '',
    masse: '',
  };
}

/** Type representant un rendu de monaie.
 * @typedef {{
 * reglement: number,
 * difference: number
 * }} Rendu
 */

/** Constructeur d'un nouveau rendu monaie
 * @returns {Rendu} de monaie
 */
function new_rendu() {
  return {
    reglement: 0,
    difference: 0,
  };
}

/** Fonction permetant de raccoursir une chaine trop longue.
  *
  * @param {string} s Chaine a tronquer
  * @param {number} n Combien de caratères a preserver
  * @param {string} c Caractère a inserer si on a fait une troncature
  * @return {string} Chaine tronqué avec eventuellement `c` comme caractère de remplacement.
  *
  * ## Exemple
  * `wrapString("Matériel élèctrique à 0.5€", 15, '…')`
  * sera evaluée à `"matériel éléc à…"`.
  *
  */
const wrapString = (s, n, c) => ((s.length > n) ? (s.slice(0, n) + c) : s);

/** @global */
let state = new_state();
/** @global */
let numpad = new_numpad();
/** @global */
let rendu = new_rendu();

/** @global */
let current_focus = document.getElementById('quantite');
/** Fonction gérant les différents champs du numpad elle est appellée via le "click"
  * HTML de l'inferface de ifaces/vente.php
  * @param {HTMLElement} element
  */
function fokus(element) {
  current_focus = element;
}

/** Réalise l'actualisation du clavier visuel
  * @param {Numpad} données du clavier visuel
  */
function render_numpad({ prix, quantite, masse }) {
  document.getElementById('quantite').value = quantite;
  document.getElementById('prix').value = prix;
  if (window.OressourceEnv.pesees) {
    document.getElementById('masse').value = masse;
  }
}

/** Remise a zéro du rendu de monaie. */
function reset_rendu() {
  /** @global */
  rendu = new_rendu();
  update_rendu();
}

/** Remise a zéro du numpad. */
function reset_numpad() {
  /** @global */
  numpad = new_numpad();
  render_numpad(numpad);
}

/** Cette fonction remet l'interface du choix du moyen de paiement à son état initial
  * c'est à dire sur «espèce».
  */
function reset_paiement() {
  const moyensPaiementSelector = document.getElementById('moyens');
  Array.from(moyensPaiementSelector.children).forEach((elem) => {
    elem.classList.remove('active');
  });

  // L'element 0 c'est les especes.
  moyensPaiementSelector.children[0].classList.add('active');
}

/** Un object pour representer les données saisies au clavier numérique visuel
 * @typedef {Object<number, number, number>} Numpad
 * @property {number} quantite (int) quantité saisie dans le clavier numérique visuel
 * @property {number} masse Masse saisie, si non saisie assignée à `NaN`
 * @property {number} prix Prix saisi
 */

/**
 * Permet de récupérer les saisies du numpad sous la forme d'un objet js.
 * @returns {Numpad}
 */
function get_numpad() {
  const masseinput = document.getElementById('masse');
  return {
    quantite: Number.parseInt(document.getElementById('quantite').value, 10),
    masse: Number.parseFloat(masseinput === null ? NaN : masseinput.value, 10),
    prix: Number.parseFloat(document.getElementById('prix').value, 10),
  };
}

/**
 * Ajoute au panier l'objet selectionné.
 *
 * -.
 * @param {Item}
 * - type de l'item ajouté au ticket
 * - prix de l'item
 * - masse de l'item
 *
 */
function update_state({ type, objet }) {
  numpad = get_numpad();
  if (objet) {
    numpad.prix = objet.prix;
  } else {
    objet = { prix: 0, masse: 0.0 }
  }
  numpad.prix = (Number.isNaN(numpad.prix)) ? '' : numpad.prix;
  numpad.masse = (Number.isNaN(numpad.masse)) ? '' : numpad.masse;
  numpad.quantite = 1;
  state.last = { type, objet };
  const color = objet.couleur || type.couleur;
  const name = objet.nom || type.nom;
  const html = `<span class='badge' id='cool' style="background-color:${color}">${wrapString(name, 15, '&hellip;')}</span>`;
  document.getElementById('nom_objet').innerHTML = html;
  render_numpad(numpad);
}

// Effectue l'affichage temporaire des 3 dernières ventes sur la page de vente 
function update_historique(data, _) {
  const idMoyenPaiement = data.id_moyen;
  const moyenPaiement = window.OressourceEnv.moyens_paiement.find(element => element.id === idMoyenPaiement);
  const historique = document.querySelector(".table tbody");
  if (historique.childElementCount === 3) {
    const lChild = historique.lastElementChild;
    lChild.remove()
  }
  const row = document.createElement("tr");
  const dateCell = document.createElement('td');
  dateCell.innerText = moment().format('DD/MM/YYYY - HH:mm');
  dateCell.setAttribute('colspan', '2')
  const prixCell = document.createElement('td');
  prixCell.innerText = state.ticket.sum_prix();
  const objetCell = document.createElement('td');
  objetCell.innerText = data.items.length;
  const moyenCell = document.createElement('td');
  moyenCell.innerHTML = `<span class='badge' style="background-color:${moyenPaiement.couleur}">${moyenPaiement.nom}</span>`;
  row.append(dateCell, prixCell, objetCell, moyenCell);
  historique.prepend(row);
}
/**
 * Effectue le reset des données représentant une vente et de l'interface graphique. Met à jour l'affichage des 3 dernières ventes.
 */
function reset(data, response) {
  update_historique(data, response);
  state = new_state();

  reset_numpad();
  reset_rendu();
  reset_paiement();


  // On remet à zéro le panier
  {
    const range = document.createRange();
    range.selectNodeContents(document.getElementById('transaction'));
    range.deleteContents();
    update_recap(0.0, 0.0);
  }

  // On donne le nouveau numéro «prévisionnel» à la futur vente.
  // Attention ce numéro est «provisoire» si il y a plusieurs caisses.
  document.getElementById('num_vente').textContent = response.id + 1;
  // #FIX: 384 Reset du commentaire associé à la vente.
  document.getElementById('commentaire').value = '';
}

/**
 * Met a jour le moyen de paiement représenté par un entier.
 * @param {number} moyen
 *
 * Manipule la globale `state`
 */
function moyens(moyen) {
  state.moyen = moyen;
}

/**
 * Envoie coté serveur une vente après quelques verifications locales.
 *
 * @returns {{} | Item}
 */
function encaisse_vente() {
  if (state.ticket.size > 0) {
    const date = document.getElementById('date');
    const data = {
      classe: 'ventes',
      id_point: window.OressourceEnv.point.id,
      id_user: window.OressourceEnv.id_user,
      id_moyen: state.moyen,
      commentaire: document.getElementById('commentaire').value.trim(),
      items: state.ticket.to_array(),
    };
    if (date !== null) {
      data.date = date.value;
    }
    return data;
  } else {
    return {};
  }
}

/**
 * Fonction d'affichage des informations relative a la TVA et au prix.
 *
 * ## Cas TVA non active
 *
 * Cependant si la structure n'a pas activé la TVA alors le prix est considéré
 * Hors Taxe et aucun calul n'est fait.
 *
 * ## Cas de la TVA active
 *
 * Actuellement Oressource ne gére que un taux de TVA unique.
 *
 * Si la structure active la TVA les prix affichées en boutiques sont
 * Toutes Taxes Comprises, il conviens alors de calculer un prix HT
 * et la part TVA pour informer, l'usager de la ressourcerie.
 *
 * Formule: Prix HT = Prix TTC * 100 / (100 + Taux)
 *
 * Source:
 * https://www.service-public.fr/professionnels-entreprises/vosdroits/F24271
 *
 * @returns {string}
 */
const printTva = (() => {
  if (window.OressourceEnv.tva_active) {
    return () => {
      const ttc = state.ticket.sum_prix();
      const taux_tva = window.OressourceEnv.taux_tva;
      const ht = (ttc * 100) / (100 + taux_tva);
      const part_tva = ttc - ht;
      return `Prix TTC = ${ttc.toFixed(2)} €<br> Prix HT = ${ht.toFixed(2)} €<br> TVA ${taux_tva}% = ${part_tva.toFixed(2)} €`;
    };
  } else {
    return () => {
      const ht = state.ticket.sum_prix();
      return `Prix HT = ${ht.toFixed(2)} €<br>Association non assujettie à la TVA.`;
    };
  }
})();

/**
 * Fonction d'actualisation du rendu visuel de l'interface des ventes.
 */
function update_rendu() {
  const total = state.ticket.sum_prix();
  const input = document.getElementById('reglement');
  rendu.reglement = parseFloat(input.value, 10).toFixed(3);
  rendu.difference = rendu.reglement - total;
  document.getElementById('somme').value = total;
  document.getElementById('difference').value = rendu.difference || 0;
}

/**
 * Hack pas très glorieux pour gérer le «multi-clavier» visuel avec un seul element HTML.
 * @param {HTMLElement} Element HTML representant un bouton du clavier visuel
 * sur lequel on viens de cliquer.
 */
function numpad_input(elem) {
  current_focus.value += elem.value;
}

/**
 * Fonction de retrait d'objet dans l'interface web de vente.
 * Le retrait d'un objet met a jour le composant du rendu de monaie,
 * l'etait du ticket et remet a zéro le clavier visuel (Numpad)
 *
 * @param {Number} id de l'objet a retirer
 */
function remove(id) {
  // TODO: Ajouter une gestion du Refaire une action/annuler une suppression
  state.ticket.remove(id);
  document.getElementById(id).remove();
  update_rendu();
  update_recap(state.ticket.sum_prix(), state.ticket.sum_quantite());
  reset_numpad();
}

/**
 * Mise a jour des totaux
 * du récapitulatif d'une vente dans le client web (widget de gauche).
 *
 * Et l'objet en cours de saisie du numpad.
 *
 * Manipule le DOM pour mettre a jour les champs:
 * - `total`: Recapitulatif du total prix et nombres d'articles.
 * - `recaptotal`: Recapitulatif du total en euros €
 * - `nom_objet`: nom de l'objet en saisie sur le numpad
 *
 * @param {number} totalPrice
 * @param {number} total_quantity
 */
function update_recap(totalPrice, totalQuantity) {
  const totalPriceTxt = totalPrice.toFixed(2);
  document.getElementById('total').innerHTML = `<li class="list-group-item">Soit : ${totalQuantity} article(s) pour : <span class="badge" style="float:right;">${totalPriceTxt} €</span></li>`;
  document.getElementById('recaptotal').innerHTML = `${totalPriceTxt} €`;
  document.getElementById('nom_objet').textContent = 'Objet:';
}

/**
 * Cette fonction est utilisée lorsque l'on click sur le bouton `ajouter`
 * du numpad.
 * On récupére l'état des objets du même type à ajouter au panier puis
 * on met a jour l'interface graphique du panier.
 *
 * Deux points sont assez particulier:
 *
 * ## Cas de la vente en lot
 *
 * En lot on décide d'appliquer un prix arbitraire à un ensemble d'objet
 * du même type sans respecter le prix unitaire (une promotion en somme).
 * On ne peux donc pas utilier comme "prix total" quantité * prix
 * comme dans la vente unitaire. On prends le prix seulement du lot.
 *
 * ## Cas des pesées en vente:
 *
 * On ajoute la masse de l'ensemble des objets à ajouter au panier
 * (Comme dans une collecte ou sortie), on ne pesee pas independament tout
 * les objets on pesee en lot en somme.
 */
function add() {
  if (state.last !== undefined) {
    const { prix, quantite, masse } = get_numpad();
    if (quantite > 0 && !Number.isNaN(prix)) {
      const current = state.last;
      state.last = undefined;
      // Idée: Ajouter un champ "prix total" pour eviter de faire un if
      // pour les calculs sur les prix.

      const name = current.objet.nom || current.type.nom;
      const lotTxt = !state.vente_unite ? 'lot' : '';
      const vente = {
        id_type: current.type.id,
        id_objet: current.objet.id || null,
        lot: !state.vente_unite,
        quantite,
        prix,
        masse,
        name, // Hack pour les impressions.
        /**
         * Fonction pour afficher une vente dans l'interface web.
         * @returns {string} Representant un fragment HTML.
         */
        show() {
          const montantTotal = this.quantite * this.prix;
          const prixTxt = `${this.prix.toFixed(2)} €`;
          const masseTxt = this.masse >= 0.00 ? ` ${this.masse} kg` : '';
          return `<p>${lotTxt} ${this.quantite} * ${this.name} ${prixTxt} ${montantTotal.toFixed(2)} €${masseTxt}</p>`;
        },
      };

      const id = state.ticket.push(vente);

      const li = document.createElement('li');
      li.setAttribute('id', id);
      li.setAttribute('class', 'list-group-item');
      const amount = vente.lot ? vente.prix : vente.prix * vente.quantite;
      let html = `
            <span class="badge">${amount.toFixed(2)} €</span>
            <span class="glyphicon glyphicon-trash" aria-hidden="true"
                  onclick="${remove.name}(${id});return false;">
            </span>&nbsp;&nbsp; ${lotTxt} ${quantite} &#215; ${name}`;
      if (masse > 0 && window.OressourceEnv.pesees) {
        html += `, ${(masse).toFixed(3)} Kgs.`;
      }
      li.innerHTML = html;
      document.getElementById('transaction').appendChild(li);
      update_recap(state.ticket.sum_prix(), state.ticket.sum_quantite());
      update_rendu();
      reset_numpad();
      // Reset du selecteur lot/unité
      $('#typeVente').bootstrapSwitch('state', true, false);
    } else {
      this.input.setCustomValidity('Quantite nulle ou inferieur a 0.');
    }
  }
}

/**
 * Change le mode de vente de unite a lot.
 * @param {string} label
 * @param {string} prix_string
 * @param {string} masse_string pas lu si `window.OressourceEnv.pesee` est faux.
 * @param {string} bg_color
 */
const lot_or_unite = (label, prix_string, masse_string, bg_color, quantite_string, quantite_type) => {
  document.getElementById('labellot').textContent = label;
  document.getElementById('labelprix').textContent = prix_string;
  document.getElementById('panelcalc').style.backgroundColor = bg_color;
  document.getElementById('labelquantite').textContent = quantite_string;
  document.getElementById('quantite').setAttribute('type', quantite_type);

  if (window.OressourceEnv.pesees) {
    document.getElementById('labelmasse').textContent = masse_string;
  }
};

document.addEventListener('DOMContentLoaded', () => {
  $('#typeVente').bootstrapSwitch();
  $('#typeVente').on('switchChange.bootstrapSwitch', (event, checked) => {
    const args = (checked
      ? ['Vente au: ', 'Prix du lot: ', 'Masse du lot: ', 'white', '', 'hidden']
      : ['Vente à: ', 'Prix unitaire:', 'Masse unitaire: ', '#E8E6BC', 'Quantité:', 'text']
    );
    lot_or_unite(...args);
    state.vente_unite = !checked;
  });
  if (!window.OressourceEnv.lots) {
    document.getElementById('labelprix').textContent = 'Prix unitaire:'
    document.getElementById('labelquantite').textContent = 'Quantité:';
    document.getElementById('quantite').setAttribute('type', 'text');
    if (window.OressourceEnv.pesees) {
      document.getElementById('labelmasse').textContent = 'Masse unitaire: ';
    }
  };

  const url = '../api/ventes.php';
  const urlTransactions = '../api/transactions.php';
  const ventePrint = (
    (d, r) => impressionTicket(d, r, printTva)
  );

  const send = post_data(url, encaisse_vente, reset);
  const sendTransaction = post_data(urlTransactions, encaisse_transaction, reset_transaction);
  const sendAndPrint = post_data(url, encaisse_vente, reset, ventePrint);
  document.getElementById('encaissement').addEventListener('click', send, false);
  document.getElementById('impression').addEventListener('click', sendAndPrint, false);
  document.querySelector('.save-transaction').addEventListener('click', sendTransaction, false);
  document.querySelector('.btn-autres-transactions').addEventListener('click', update_chiffre_du_jour, false);
  document.querySelector("#type-transaction").addEventListener('change', () => { calculer_erreur_de_caisse(); afficher_selecteur_moyens_paiements() }, false);
}, false);



/**
 * Envoie coté serveur une "autre transaction" après quelques verifications locales.
 *
 * @returns {{} | Item}
 */
function encaisse_transaction() {
  const type = document.querySelector("#type-transaction");
  const somme = document.querySelector("#somme-transaction");
  const moyen = document.querySelector("#moyen-paiement");
  if (type.value !== '' && somme.value !== '' && (type.value === '1' || moyen.value !== '')) {
    const data = {
      id_user: window.OressourceEnv.id_user,
      id_type: type.value,
      id_moyen: (type.value === '1' ? null : moyen.value),
      id_point: window.OressourceEnv.point.id,
      somme: (type.value === '1' ? somme.value - window.OressourceEnv.chiffre_du_jour : somme.value),
      commentaire: document.getElementById('commentaire-transaction').value.trim()
    };
    return data;
  } else {
    return {};
  }
}

/**
 * Fait appel à la base de donnée pour connaitre le chiffre de vente du jour
 *
 */
function update_chiffre_du_jour() {
  fetch('../moteur/chiffre_du_jour.php', {
    method: 'POST',
    credidentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json; charset=utf-8',
    },
    body: {},
  }).then(status)
    .then((response) => {
      window.OressourceEnv.chiffre_du_jour = response.chiffre_du_jour;
    }).catch((ex) => {
      console.log('Error:', ex);
    });
}

let isListening = false;

/**
 * Gére l'affichage et les fonctionnalités associés au calcul de l'erreur de caisse
 *
 */
function calculer_erreur_de_caisse() {
  const value = document.querySelector("#type-transaction").value;
  const label = document.querySelector("#label-transaction");
  const somme = document.querySelector("#somme-transaction");

  if (value === '1') {
    label.innerText = "Montant perçu en espèce :";
    somme.setAttribute("placeholder", "Entrez le montant perçu en espèce du jour")
    somme.addEventListener('input', afficher_erreur_de_caisse);
    isListening = true;
  } else if (isListening) {
    label.innerText = "Somme perçue :";
    somme.removeEventListener('input', afficher_erreur_de_caisse);
    somme.setAttribute("placeholder", "")
    removeErrorCaisse();
  }
}

/**
 * Affiche dynamiquement l'erreur de caisse
 *
 */
function afficher_erreur_de_caisse() {
  const somme = document.querySelector("#somme-transaction");
  removeErrorCaisse();

  const errCaisseContainer = document.createElement('div');
  errCaisseContainer.classList.add('erreur-caisse');
  const errCaisse = somme.value - window.OressourceEnv.chiffre_du_jour;
  errCaisseContainer.innerHTML = `<p class='navbar-text' style='margin: 0; text-align: center; float: none'>L'erreur de caisse du jour s'élève à <strong>${errCaisse}</strong> €`;
  document.querySelector('#myModal .modal-body').append(errCaisseContainer);
}

function removeErrorCaisse() {
  const errCaisse = document.querySelector('.erreur-caisse');
  if (errCaisse) {
    errCaisse.remove();
  }
}

/**
 * Affiche dynamiquement l'erreur de caisse
 *
 */
function afficher_selecteur_moyens_paiements() {
  const mpContainer = document.querySelector(".mp-container");
  const value = document.querySelector("#type-transaction").value;
  mpContainer.innerHTML = "";
  if (value !== '1' && value !== '') {
    let contenu = `<label class="control-label col-md-4" for="moyen-paiement">Moyen de paiement :</label>
    <div class="col-md-7">
      <select class="form-control" name="moyen_paiement" id="moyen-paiement">
        <option value="">Veuillez sélectionner</option>`
    for (const moyen of window.OressourceEnv.moyens_paiement) {
      contenu += `<option value="${moyen['id']}">${moyen['nom']}</option>`
    }
    contenu += '</select></div >'
    mpContainer.innerHTML = contenu
  }
}

/**
 * Reset de la transaction
 *
 */
function reset_transaction() {
  const type = document.querySelector("#type-transaction");
  const somme = document.querySelector("#somme-transaction");
  const mpContainer = document.querySelector(".mp-container");
  if (isListening) {
    somme.removeEventListener('input', afficher_erreur_de_caisse);
    somme.setAttribute("placeholder", "")
    removeErrorCaisse();
    document.querySelector("#label-transaction").innerText = "Somme perçue :";
  }
  mpContainer.innerHTML = '';
  document.getElementById('commentaire-transaction').value = '';
  type.value = '';
  somme.value = '';
  document.querySelector(".close-transaction").click();
}
