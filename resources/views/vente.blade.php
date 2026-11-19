<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/products.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vente.css') }}">
    <title>Acheter un produit</title>
</head>
<body class="products-page">
    @include('partials.navbar')

    <main class="product-card sale-card">
        <h1>Passer une commande</h1>
        <p>Choisissez un produit et renseignez vos coordonnées pour créer une commande fictive.</p>

        @if ($produits->isEmpty())
            <p class="feedback" role="status">Aucun produit n'est actuellement disponible.</p>
        @else
            <form id="sale-form">
                <div class="form-group">
                    <label for="produit_id">Produit</label>
                    <select id="produit_id" name="produit_id" required>
                        <option value="" data-price="0" data-stock="0">Choisir un produit</option>
                        @foreach ($produits as $produit)
                            <option
                                value="{{ $produit->id_p }}"
                                data-price="{{ $produit->prix_final }}"
                                data-stock="{{ $produit->stock_p }}"
                            >
                                {{ $produit->designation_p }} - {{ number_format((float) $produit->prix_final, 2, ',', ' ') }} €
                            </option>
                        @endforeach
                    </select>
                    <small id="stock-info">Sélectionnez un produit pour voir le stock disponible.</small>
                </div>

                <div class="form-group">
                    <label for="quantite">Quantité</label>
                    <input type="number" id="quantite" name="quantite" min="1" value="1" required>
                </div>

                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" maxlength="255" required>
                </div>

                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" maxlength="255" required>
                </div>

                <p class="sale-total">Total estimé : <strong id="total">0,00 €</strong></p>
                <button type="submit" id="submit-sale">Créer la commande</button>
            </form>
        @endif

        <div id="sale-feedback" class="feedback" role="status" aria-live="polite"></div>
    </main>

    <script>
        const form = document.getElementById('sale-form');
        const product = document.getElementById('produit_id');
        const quantity = document.getElementById('quantite');
        const stockInfo = document.getElementById('stock-info');
        const total = document.getElementById('total');
        const feedback = document.getElementById('sale-feedback');

        const updateSummary = () => {
            if (!product || !quantity || !stockInfo || !total) {
                return;
            }

            const option = product.options[product.selectedIndex];
            const price = Number(option?.dataset.price || 0);
            const stock = Number(option?.dataset.stock || 0);
            quantity.max = stock || 1;
            stockInfo.textContent = stock
                ? `${stock} unité(s) disponible(s).`
                : 'Sélectionnez un produit pour voir le stock disponible.';
            total.textContent = `${(price * Number(quantity.value || 0)).toFixed(2).replace('.', ',')} €`;
        };

        product?.addEventListener('change', updateSummary);
        quantity?.addEventListener('input', updateSummary);

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            feedback.className = 'feedback';
            feedback.textContent = '';

            const button = document.getElementById('submit-sale');
            button.disabled = true;

            try {
                const response = await fetch('/api/vente', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        nom: document.getElementById('nom').value.trim(),
                        email: document.getElementById('email').value.trim(),
                        produit_id: Number(product.value),
                        quantite: Number(quantity.value)
                    })
                });
                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    throw new Error(errors || 'La commande n’a pas pu être créée.');
                }

                const commande = data.commande;
                feedback.classList.add('feedback--success');
                feedback.innerHTML = `
                    <strong>Commande #${commande.id} créée.</strong>
                    Total HT : ${Number(commande.total_ht).toFixed(2).replace('.', ',')} €.
                    <button type="button" id="confirm-sale">Confirmer la commande</button>
                `;

                document.getElementById('confirm-sale').addEventListener('click', async (confirmationEvent) => {
                    const confirmButton = confirmationEvent.currentTarget;
                    confirmButton.disabled = true;

                    try {
                        const confirmation = await fetch(`/api/vente/${commande.id}/confirmer`, {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (!confirmation.ok) {
                            throw new Error('La confirmation a échoué.');
                        }

                        feedback.textContent = `Commande #${commande.id} confirmée. Merci pour votre achat fictif !`;
                    } catch (error) {
                        confirmButton.disabled = false;
                        feedback.classList.add('feedback--error');
                        feedback.textContent = error.message;
                    }
                });

                form.reset();
                updateSummary();
            } catch (error) {
                feedback.classList.add('feedback--error');
                feedback.textContent = error.message;
            } finally {
                button.disabled = false;
            }
        });

        updateSummary();
    </script>
</body>
</html>
