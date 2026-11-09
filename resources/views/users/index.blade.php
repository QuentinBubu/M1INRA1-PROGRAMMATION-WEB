<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>BackOffice - Gestion des Utilisateurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-6">Liste des Utilisateurs (BackOffice)</h1>

        @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">{{ session('success') }}</div>
        @endif

        <table class="w-full border-collapse border border-gray-200">
            <thead>
                <tr class="bg-gray-50">
                    <th class="border p-2">ID</th>
                    <th class="border p-2">Login</th>
                    <th class="border p-2">Email</th>
                    <th class="border p-2">Dernière connexion</th> <!-- Nouvelle colonne -->
                    <th class="border p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="border p-2 text-center">{{ $user->user_id }}</td>
                    <td class="border p-2">{{ $user->user_login }}</td>
                    <td class="border p-2">{{ $user->user_mail }}</td>
                    <td class="border p-2 text-center text-sm text-gray-600">{{ $user->user_date_login }}</td> <!-- Affichage en lecture seule -->
                    <td class="border p-2 text-center space-x-2">
                        <a href="{{ route('users.edit', $user->user_id) }}" class="bg-blue-500 text-white px-3 py-1 rounded text-sm">Éditer</a>
                        <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-sm" onclick="return confirm('Confirmer la suppression ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>