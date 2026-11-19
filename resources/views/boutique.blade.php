<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <title>M1 Shop - Boutique</title>
</head>
<body class="shop-page">
    @include('partials.navbar')

    <header class="shop-hero">
        <div>
            <p class="shop-eyebrow">M1 SHOP</p>
            <h1>Tout ce qu’il vous faut, au même endroit.</h1>
            <p>Découvrez notre sélection et composez votre panier en quelques clics.</p>
        </div>
        <a class="shop-hero__button" href="#catalogue">Voir les produits</a>
    </header>

    <main class="shop-layout">
        <section id="catalogue" class="catalogue" aria-labelledby="catalogue-title">
            <div class="section-heading">
                <div>
                    <p class="shop-eyebrow">CATALOGUE</p>
                    <h2 id="catalogue-title">Nos produits</h2>
                </div>
                <span>{{ $produits->count() }} produit(s) disponible(s)</span>
            </div>

            @if ($produits->isEmpty())
                <p class="shop-message">Aucun produit n’est disponible pour le moment.</p>
            @else
                <div class="product-grid">
                    @foreach ($produits as $produit)
                        <article class="shop-product">
                            <div class="shop-product__visual">
                                <span>{{ strtoupper(substr($produit->type_p, 0, 1)) }}</span>
                            </div>
                            <div class="shop-product__body">
                                <p class="shop-product__category">{{ $produit->type_p }}</p>
                                <h3>{{ $produit->designation_p }}</h3>
                                <p class="shop-product__stock">{{ $produit->stock_p }} disponible(s)</p>
                                <div class="shop-product__footer">
                                    <strong>{{ number_format((float) $produit->prix_final, 2, ',', ' ') }} €</strong>
                                    <button
                                        type="button"
                                        class="add-to-cart"
                                        data-product-id="{{ $produit->id_p }}"
                                    >
                                        Ajouter
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside id="panier" class="cart-panel" aria-labelledby="cart-title">
            <div class="section-heading section-heading--cart">
                <div>
                    <p class="shop-eyebrow">VOTRE SÉLECTION</p>
                    <h2 id="cart-title">Mon panier</h2>
                </div>
                <span id="cart-count-label">0 article</span>
            </div>

            <div id="cart-items" class="cart-items">
                <p class="cart-empty">Votre panier est vide.</p>
            </div>

            <div class="cart-summary">
                <span>Sous-total</span>
                <strong id="cart-total">0,00 €</strong>
            </div>

            <form id="checkout-form" class="checkout-form">
                <h3>Finaliser la commande</h3>
                <label for="checkout-name">Nom complet</label>
                <input id="checkout-name" type="text" maxlength="255" required>
                <label for="checkout-email">E-mail</label>
                <input id="checkout-email" type="email" maxlength="255" required>
                <label for="checkout-card">Numéro de carte (simulation)</label>
                <input id="checkout-card" type="text" inputmode="numeric" autocomplete="cc-number" minlength="16" maxlength="19" placeholder="4242 4242 4242 4242" required>
                <div class="checkout-form__row">
                    <div>
                        <label for="checkout-expiry">Expiration</label>
                        <input id="checkout-expiry" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/AA" pattern="(0[1-9]|1[0-2])\/[0-9]{2}" required>
                    </div>
                    <div>
                        <label for="checkout-cvv">CVV</label>
                        <input id="checkout-cvv" type="password" inputmode="numeric" autocomplete="cc-csc" minlength="3" maxlength="4" required>
                    </div>
                </div>
                <small class="checkout-form__notice">Paiement fictif : aucune donnée bancaire n’est enregistrée.</small>
                <button id="checkout-submit" type="submit" disabled>Valider ma commande</button>
            </form>

            <div id="checkout-feedback" class="shop-message" role="status" aria-live="polite"></div>
        </aside>
    </main>

    <script>
        const products = @json($produitsData);
        const productById = new Map(products.map((product) => [product.id, product]));
        const cartKey = 'm1-shop-cart';
        let cart = JSON.parse(localStorage.getItem(cartKey) || '[]')
            .filter((item) => productById.has(item.id));

        const money = (value) => `${Number(value).toFixed(2).replace('.', ',')} €`;
        const saveCart = () => localStorage.setItem(cartKey, JSON.stringify(cart));
        const cartCount = () => cart.reduce((total, item) => total + item.quantity, 0);

        const renderCart = () => {
            const container = document.getElementById('cart-items');
            const totalElement = document.getElementById('cart-total');
            const countElement = document.getElementById('cart-count');
            const countLabel = document.getElementById('cart-count-label');
            const submit = document.getElementById('checkout-submit');
            const count = cartCount();

            countElement.textContent = count;
            countLabel.textContent = `${count} article${count > 1 ? 's' : ''}`;
            submit.disabled = cart.length === 0;

            if (cart.length === 0) {
                container.innerHTML = '<p class="cart-empty">Votre panier est vide.</p>';
                totalElement.textContent = money(0);
                return;
            }

            let total = 0;
            container.innerHTML = cart.map((item) => {
                const product = productById.get(item.id);
                const lineTotal = product.price * item.quantity;
                total += lineTotal;

                return `
                    <div class="cart-line">
                        <div>
                            <strong>${product.name}</strong>
                            <small>${money(product.price)} l’unité</small>
                        </div>
                        <div class="cart-line__actions">
                            <button type="button" data-action="decrease" data-id="${product.id}" aria-label="Retirer une unité">−</button>
                            <span>${item.quantity}</span>
                            <button type="button" data-action="increase" data-id="${product.id}" aria-label="Ajouter une unité">+</button>
                            <button type="button" class="cart-remove" data-action="remove" data-id="${product.id}">Supprimer</button>
                        </div>
                    </div>
                `;
            }).join('');

            totalElement.textContent = money(total);
        };

        document.querySelectorAll('.add-to-cart').forEach((button) => {
            button.addEventListener('click', () => {
                const id = Number(button.dataset.productId);
                const product = productById.get(id);
                const line = cart.find((item) => item.id === id);

                if (line) {
                    if (line.quantity < product.stock) line.quantity += 1;
                } else {
                    cart.push({ id, quantity: 1 });
                }

                saveCart();
                renderCart();
                document.getElementById('panier').scrollIntoView({ behavior: 'smooth' });
            });
        });

        document.getElementById('cart-items').addEventListener('click', (event) => {
            const button = event.target.closest('[data-action]');
            if (!button) return;

            const id = Number(button.dataset.id);
            const line = cart.find((item) => item.id === id);
            const product = productById.get(id);
            if (!line || !product) return;

            if (button.dataset.action === 'increase' && line.quantity < product.stock) line.quantity += 1;
            if (button.dataset.action === 'decrease') line.quantity -= 1;
            if (button.dataset.action === 'remove' || line.quantity < 1) {
                cart = cart.filter((item) => item.id !== id);
            }

            saveCart();
            renderCart();
        });

        document.getElementById('checkout-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = document.getElementById('checkout-submit');
            const feedback = document.getElementById('checkout-feedback');
            submit.disabled = true;
            feedback.className = 'shop-message';
            feedback.textContent = '';

            try {
                const response = await fetch('/api/vente', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        nom: document.getElementById('checkout-name').value.trim(),
                        email: document.getElementById('checkout-email').value.trim(),
                        numero_carte: document.getElementById('checkout-card').value.replace(/\s/g, ''),
                        expiration: document.getElementById('checkout-expiry').value,
                        cvv: document.getElementById('checkout-cvv').value,
                        items: cart.map((item) => ({
                            produit_id: item.id,
                            quantite: item.quantity
                        }))
                    })
                });
                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                    throw new Error(errors || 'La commande n’a pas pu être créée.');
                }

                const confirmation = await fetch(`/api/vente/${data.commande.id}/confirmer`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!confirmation.ok) {
                    throw new Error('La commande a été créée mais sa confirmation a échoué.');
                }

                cart = [];
                saveCart();
                renderCart();
                feedback.classList.add('shop-message--success');
                feedback.textContent = `Commande #${data.commande.id} confirmée. Merci pour votre achat !`;
                event.target.reset();
            } catch (error) {
                feedback.classList.add('shop-message--error');
                feedback.textContent = error.message;
                submit.disabled = false;
            }
        });

        renderCart();
    </script>
</body>
</html>
