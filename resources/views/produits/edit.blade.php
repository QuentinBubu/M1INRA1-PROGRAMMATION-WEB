<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/products.css') }}">
    <title>Modifier un produit</title>
</head>
<body class="products-page">
    <main class="product-card">
        <h1>Modifier le produit</h1>

        @if ($errors->any())
            <div class="error-message" role="alert">
                <p>Corrige les erreurs suivantes :</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/produits/{{ $produit->id_p }}" method="post">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="type_p">Type de produit</label>
                <input type="text" id="type_p" name="type_p" maxlength="100"
                       value="{{ old('type_p', $produit->type_p) }}" required>
            </div>

            <div class="form-group">
                <label for="designation_p">Désignation</label>
                <input type="text" id="designation_p" name="designation_p" maxlength="255"
                       value="{{ old('designation_p', $produit->designation_p) }}" required>
            </div>

            <div class="form-group">
                <label for="prix_ht">Prix HT (€)</label>
                <input type="number" id="prix_ht" name="prix_ht" step="0.01" min="0"
                       value="{{ old('prix_ht', $produit->prix_ht) }}" required>
            </div>

            <div class="form-group">
                <label for="stock_p">Stock</label>
                <input type="number" id="stock_p" name="stock_p" min="0"
                       value="{{ old('stock_p', $produit->stock_p) }}">
            </div>

            <div class="form-group">
                <label for="date_in">Date d'entrée</label>
                <input type="date" id="date_in" name="date_in"
                       value="{{ old('date_in', $produit->date_in) }}" required>
            </div>

            <button type="submit">Enregistrer les modifications</button>
        </form>

        <p><a href="/produits">Retour à la liste des produits</a></p>
    </main>
</body>
</html>
