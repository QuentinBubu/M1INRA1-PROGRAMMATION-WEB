<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <title>Mes commandes - M1 Shop</title>
</head>
<body class="shop-page">
    @include('partials.navbar')

    <main class="orders-page">
        <div class="section-heading">
            <div>
                <p class="shop-eyebrow">ESPACE CLIENT</p>
                <h1>Mes commandes</h1>
            </div>
            <a class="shop-hero__button" href="/boutique">Continuer mes achats</a>
        </div>

        @forelse ($commandes as $commande)
            <article class="order-card">
                <div class="order-card__header">
                    <div>
                        <h2>Commande #{{ $commande->id }}</h2>
                        <p>Passée le {{ $commande->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    <span class="order-status order-status--{{ $commande->statut }}">{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</span>
                </div>
                <ul>
                    @foreach ($commande->lignes as $ligne)
                        <li>
                            <span>{{ $ligne->designation }} × {{ $ligne->quantite }}</span>
                            <strong>{{ number_format((float) $ligne->total_ligne_ht, 2, ',', ' ') }} €</strong>
                        </li>
                    @endforeach
                </ul>
                <div class="order-card__total">
                    <span>Total HT</span>
                    <strong>{{ number_format((float) $commande->total_ht, 2, ',', ' ') }} €</strong>
                </div>
            </article>
        @empty
            <p class="shop-message">Vous n’avez pas encore passé de commande.</p>
        @endforelse
    </main>
</body>
</html>
