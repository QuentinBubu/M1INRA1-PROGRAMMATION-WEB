<nav class="site-navbar" aria-label="Navigation principale">
    <a class="site-navbar__brand" href="/boutique">M1 Shop</a>
    <div class="site-navbar__links">
        <a href="/boutique">Boutique</a>
        @if (auth()->user()?->role === 'admin')
            <a href="/produits">Catalogue</a>
            <a href="/addProduct">Ajouter un produit</a>
            <a href="/admin/users">Utilisateurs</a>
        @endif
        <a href="#panier">Panier <span id="cart-count" class="cart-count">0</span></a>
        <a href="/mes-commandes">Mes commandes</a>
        <button type="button" class="site-navbar__logout" data-logout>Se déconnecter</button>
    </div>
</nav>

<script>
    document.querySelector('[data-logout]').addEventListener('click', async () => {
        const button = document.querySelector('[data-logout]');
        button.disabled = true;

        try {
            const response = await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) {
                throw new Error(`Déconnexion impossible (${response.status}).`);
            }

            window.location.href = '/';
        } catch (error) {
            button.disabled = false;
            alert(error.message);
        }
    });
</script>
