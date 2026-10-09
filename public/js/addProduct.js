document.getElementById('formAjoutProduit').addEventListener('submit', async (event) => {
    event.preventDefault();

    const feedback = document.getElementById('feedbackMessage');
    const formData = new FormData(event.target);

    // 1. Préparation du JSON
    const produitData = {
        type_p: formData.get('type_p'),
        designation_p: formData.get('designation_p'),
        prix_ht: parseFloat(formData.get('prix_ht')),
        stock_p: parseInt(formData.get('stock_p')),
        date_in: formData.get('date_in')
    };

    try {
        // 2. Méthode POST vers l'endpoint généré par API Platform
        const response = await fetch('/api/produits', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(produitData)
        });

        const result = await response.json();

        // 3. Gestion des codes HTTP
        if (response.status === 201) {
            // 201 Created : Succès de l'opération
            feedback.textContent = 'Produit ajouté avec succès !';
            feedback.style.color = 'green';
            event.target.reset(); // Vide le formulaire
        } else if (response.status === 400 || response.status === 422) {
            // 400 Bad Request : Erreur de validation serveur
            feedback.textContent = 'Erreur : Données invalides.';
            feedback.style.color = 'red';
        } else {
            throw new Error('Erreur serveur non gérée');
        }
    } catch (error) {
        // 500 Internal Server Error (Crash)
        feedback.textContent = 'Erreur critique lors de la création du produit.';
        feedback.style.color = 'red';
    }
});
