<nav class="site-navbar" aria-label="Navigation principale">
    <a class="site-navbar__brand" href="/addProduct">Gestion produits</a>
    <div class="site-navbar__links">
        <a href="/addProduct">Ajouter</a>
        <a href="/produits">Produits</a>
        <a href="/admin/users">Utilisateurs</a>
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
