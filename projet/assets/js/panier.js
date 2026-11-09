// Mise à jour dynamique des sous-totaux et du total TTC du panier.
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.panier-form');
  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  const inputs = form.querySelectorAll('input[data-quantite]');
  const totalElement = document.querySelector('[data-total-panier]');
  if (!totalElement || inputs.length === 0) {
    return;
  }

  const formatEuro = (valeur) => new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(valeur) + ' €';

  const mettreAJourTotal = () => {
    let total = 0;

    inputs.forEach((input) => {
      const ligne = input.closest('.ligne-panier');
      if (!(ligne instanceof HTMLElement)) {
        return;
      }

      const prixUnitaire = Number.parseFloat(ligne.dataset.prixUnitaire || '0');
      const max = Number.parseInt(input.max, 10);
      let quantite = Number.parseInt(input.value, 10);

      if (Number.isNaN(quantite)) {
        quantite = 0;
      }
      if (quantite < 0) {
        quantite = 0;
        input.value = '0';
      }
      if (Number.isFinite(max) && quantite > max) {
        quantite = max;
        input.value = String(max);
      }

      const sousTotal = Math.round(prixUnitaire * quantite * 100) / 100;
      const ligneTotal = ligne.querySelector('[data-ligne-total]');
      if (ligneTotal) {
        ligneTotal.textContent = formatEuro(sousTotal);
      }

      total += sousTotal;
    });

    totalElement.textContent = formatEuro(Math.round(total * 100) / 100);
  };

  inputs.forEach((input) => {
    input.addEventListener('input', mettreAJourTotal);
  });

  // Synchronise l'affichage au chargement de la page.
  mettreAJourTotal();
});
