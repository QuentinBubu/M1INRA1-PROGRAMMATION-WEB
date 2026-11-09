// Tri automatique du catalogue dès qu'un type de produit est sélectionné.
document.addEventListener('DOMContentLoaded', () => {
  const select = document.querySelector('#categorie');
  if (!(select instanceof HTMLSelectElement)) {
    return;
  }

  select.addEventListener('change', () => {
    const form = select.form;
    if (!form) {
      return;
    }

    // requestSubmit déclenche la soumission normale du formulaire.
    // Repli sur submit() pour les navigateurs plus anciens.
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.submit();
    }
  });
});
