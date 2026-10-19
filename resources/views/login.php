<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Application Client</title>

    <link rel="stylesheet" href="<?= asset('css/login.css') ?>">
</head>
<body>

<main>
    <section class="login-card">
        <header>
            <h1>Connexion</h1>
            <p class="instruction">Veuillez saisir vos identifiants pour accéder à votre espace client et gérer vos commandes.</p>
        </header>

        <form id="loginForm">
            <div class="form-group">
                <label for="login">Identifiant utilisateur</label>
                <input type="text" id="login" name="login" required placeholder="Ex: jean.dupont">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                       title="8 caractères minimum, au moins une majuscule, une minuscule et un chiffre."
                       placeholder="Votre mot de passe sécurisé">
            </div>

            <button type="submit" id="submitBtn" aria-live="polite">
                Se connecter
            </button>
        </form>
        <p>
            <a href="/register">Créer un compte</a>
        </p>
        <div id="toastMessage" class="toast" role="alert" aria-live="assertive"></div>
    </section>
</main>

<script>
    document.getElementById('loginForm').addEventListener('submit', async function(event) {
        event.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const toast = document.getElementById('toastMessage');

        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Connexion en cours...';

        toast.className = 'toast';
        toast.textContent = '';

        const formData = {
            login: event.target.login.value,
            password: event.target.password.value
        };

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                toast.textContent = 'Connexion réussie ! Redirection...';
                toast.classList.add('show', 'success');

                setTimeout(() => {
                    window.location.href = '/addProduct';
                    }, 1500);
            } else {
                toast.textContent = data.message || 'Identifiants incorrects.';
                toast.classList.add('show', 'error');
            }
        } catch (error) {
            toast.textContent = 'Erreur critique de connexion au serveur API.';
            toast.classList.add('show', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Se connecter';
        }
    });
</script>
</body>
</html>
