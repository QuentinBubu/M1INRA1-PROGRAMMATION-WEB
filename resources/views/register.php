<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Application Client</title>

    <link rel="stylesheet" href="<?= asset('css/login.css') ?>">
</head>
<body>

<main>
    <section class="login-card">
        <header>
            <h1>Créer un compte</h1>
            <p class="instruction">
                Remplissez le formulaire pour créer votre compte.
            </p>
        </header>

        <form id="registerForm">
            <div class="form-group">
                <label for="login">Identifiant</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    required
                    maxlength="255"
                    placeholder="Ex : jean.dupont"
                >
            </div>

            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    maxlength="255"
                    placeholder="jean.dupont@example.com"
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                    title="8 caractères minimum, une majuscule, une minuscule et un chiffre."
                >
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    minlength="8"
                >
            </div>

            <button type="submit" id="submitBtn">
                Créer mon compte
            </button>
        </form>

        <p>
            <a href="/">Déjà un compte ? Se connecter</a>
        </p>

        <div id="toastMessage" class="toast" role="alert" aria-live="assertive"></div>
    </section>
</main>

<script>
    document.getElementById('registerForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = document.getElementById('submitBtn');
        const toast = document.getElementById('toastMessage');

        submitBtn.disabled = true;
        toast.className = 'toast';
        toast.textContent = '';

        const formData = {
            login: form.login.value.trim(),
            email: form.email.value.trim(),
            password: form.password.value,
            password_confirmation: form.password_confirmation.value
        };

        try {
            const response = await fetch('/api/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok) {
                const errors = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : data.message;

                toast.textContent = errors || 'Impossible de créer le compte.';
                toast.classList.add('show', 'error');
                return;
            }

            toast.textContent = 'Compte créé avec succès. Redirection...';
            toast.classList.add('show', 'success');

            setTimeout(() => {
                window.location.href = '/';
            }, 1500);
        } catch (error) {
            toast.textContent = 'Erreur de connexion avec le serveur.';
            toast.classList.add('show', 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });
</script>

</body>
</html><?php
