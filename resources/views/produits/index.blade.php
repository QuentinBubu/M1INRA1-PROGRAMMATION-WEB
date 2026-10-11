<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/products.css') }}">
    <title>Gestion des produits</title>
</head>
<body class="products-page">
    <main class="product-card">
        <h1>Gestion des produits</h1>

        @if (session('success'))
            <p class="success-message" role="status">{{ session('success') }}</p>
        @endif

        <p><a href="/addProduct">Ajouter un produit</a></p>

        @if ($produits->isEmpty())
            <p>Aucun produit enregistré pour le moment.</p>
        @else
            <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Désignation</th>
                        <th scope="col">Prix HT</th>
                        <th scope="col">Stock</th>
                        <th scope="col">Date d'entrée</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produits as $produit)
                        <tr>
                            <td>{{ $produit->type_p }}</td>
                            <td>{{ $produit->designation_p }}</td>
                            <td>{{ number_format((float) $produit->prix_ht, 2, ',', ' ') }} €</td>
                            <td>{{ $produit->stock_p }}</td>
                            <td>{{ $produit->date_in }}</td>
                            <td class="actions">
                                <a href="/produits/{{ $produit->id_p }}/edit">Modifier</a>
                                <form action="/produits/{{ $produit->id_p }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button class="delete-button" type="submit" onclick="return confirm('Supprimer ce produit ?')">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </main>
</body>
</html>
