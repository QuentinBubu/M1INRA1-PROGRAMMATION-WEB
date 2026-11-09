<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Editer l'utilisateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-6">Modifier l'utilisateur #{{ $user->user_id }}</h1>

        <form action="{{ route('users.update', $user->user_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Login :</label>
                <input type="text" name="user_login" value="{{ old('user_login', $user->user_login) }}" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Email :</label>
                <input type="email" name="user_mail" value="{{ old('user_mail', $user->user_mail) }}" class="w-full border p-2 rounded" required>
            </div>

            <!-- Champ en lecture seule (Information pure) -->
            <div class="mb-6 bg-gray-50 p-4 rounded border border-gray-200">
                <span class="block text-sm font-medium text-gray-500">Dernière connexion :</span>
                <span class="text-gray-800 font-semibold">{{ $user->user_date_login ?? 'Jamais connecté' }}</span>

                <span class="block text-sm font-medium text-gray-500 mt-2">Compte créé le :</span>
                <span class="text-gray-800 font-semibold">{{ $user->user_date_new }}</span>
            </div>

            <div class="flex justify-between">
                <a href="{{ route('users.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded">Retour</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Mettre à jour</button>
            </div>
        </form>
    </div>
</body>

</html>