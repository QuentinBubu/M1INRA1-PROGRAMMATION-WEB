<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/html">
    <head>
        <meta charset="UTF-8">
        <title>Ajout de produit</title>
    </head>
    <body>
        <form id="formAjoutProduit">
            <!-- Le menu déroulant généré à l'étape précédente -->
            <div class="form-group">
                <label for="type_p">Type de produit</label>
                <select id="type_p" name="type_p" required></select>
            </div>

            <div class="form-group">
                <label for="designation_p">Désignation</label>
                <!-- Validation Required HTML5 -->
                <input type="text" id="designation_p" name="designation_p" required maxlength="255">
            </div>

            <div class="form-group">
                <label for="prix_ht">Prix HT (€)</label>
                <!-- Validation Min HTML5 -->
                <input type="number" step="0.01" id="prix_ht" name="prix_ht" required min="0">
            </div>

            <div class="form-group">
                <label for="stock_p">Stock initial</label>
                <input type="number" id="stock_p" name="stock_p" value="0" min="0">
            </div>

            <div class="form-group">
                <label for="date_in">Date d'entrée</label>
                <input type="date" id="date_in" name="date_in" required>
            </div>

            <button type="submit">Ajouter le produit</button>
        </form>

        <!-- Zone pour le feedback immédiat -->
        <div id="feedbackMessage"></div>
    </body>
</html>
