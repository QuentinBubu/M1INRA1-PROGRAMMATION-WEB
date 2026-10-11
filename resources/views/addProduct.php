<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/html">
    <head>
        <meta charset="UTF-8">
        <meta name="csrf-token" content="<?= csrf_token() ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= asset('css/login.css') ?>">
        <link rel="stylesheet" href="<?= asset('css/products.css') ?>">
        <title>Ajout de produit</title>
    </head>
    <body class="products-page">
        <main class="product-card">
            <h1>Ajouter un produit</h1>
            <p><a href="/produits">Voir la liste des produits</a></p>
            <form id="formAjoutProduit">
                <div class="form-group">
                    <label for="type_p">Type de produit</label>
                    <select id="type_p" name="type_p" required>
                        <option value="" disabled selected>Choisir un type</option>
                        <option value="Informatique">Informatique</option>
                        <option value="Accessoires">Accessoires</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="designation_p">Désignation</label>
                    <input type="text" id="designation_p" name="designation_p" required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="prix_ht">Prix HT (€)</label>
                    <input type="number" step="0.01" id="prix_ht" name="prix_ht" required min="0">
                </div>

                <div class="form-group">
                    <label for="stock_p">Stock initial</label>
                    <input type="number" id="stock_p" name="stock_p" value="0" min="0">
                </div>

                <div class="form-group">
                    <label for="date_in">Date d'entrée</label>
                    <input type="date" id="date_in" name="date_in" required>
                </div>

                <button type="submit">Ajouter le produit</button>
            </form>

            <div id="feedbackMessage" class="feedback" role="status" aria-live="polite"></div>
        </main>

        <script>
            const form = document.getElementById('formAjoutProduit');
            const feedback = document.getElementById('feedbackMessage');
            const submitButton = form.querySelector('button[type="submit"]');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                feedback.textContent = '';
                feedback.setAttribute('role', 'status');
                submitButton.disabled = true;

                const produit = {
                    type_p: form.elements.type_p.value,
                    designation_p: form.elements.designation_p.value.trim(),
                    prix_ht: Number(form.elements.prix_ht.value),
                    stock_p: Number(form.elements.stock_p.value || 0),
                    date_in: form.elements.date_in.value
                };

                try {
                    const response = await fetch('/api/produits-form', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(produit)
                    });
                    const responseText = await response.text();
                    const data = responseText ? JSON.parse(responseText) : {};

                    if (!response.ok) {
                        const violations = data.violations
                            ?.map((violation) => violation.message)
                            .join(' ');
                        feedback.textContent = violations
                            || data.detail
                            || data.message
                            || `L’ajout a échoué (erreur ${response.status}).`;
                        feedback.setAttribute('role', 'alert');
                        return;
                    }

                    feedback.textContent = 'Produit ajouté avec succès.';
                    form.reset();
                } catch (error) {
                    feedback.textContent = error instanceof SyntaxError
                        ? 'Le serveur a renvoyé une réponse illisible.'
                        : 'Impossible de contacter le serveur. Vérifie ta connexion et réessaie.';
                    feedback.setAttribute('role', 'alert');
                } finally {
                    submitButton.disabled = false;
                }
            });
        </script>
    </body>
</html>
