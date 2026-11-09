<?php

declare(strict_types=1);

namespace Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Tests\Support\SeleniumTestCase;

final class CatalogueTest extends SeleniumTestCase
{
    public function testFiltreCatalogueParTypeDeProduit(): void
    {
        $this->visiter('/produits/catalogue.php');

        $select = new WebDriverSelect($this->element(WebDriverBy::name('categorie')));
        $select->selectByVisibleText('Smartphone');

        $this->attendreUrl('categorie=');
        $this->element(WebDriverBy::cssSelector('.produit'));

        $produits = $this->driver->findElements(WebDriverBy::cssSelector('.produit'));
        $this->assertNotEmpty($produits, 'Le filtre Smartphone doit retourner au moins un produit.');

        $contenu = '';
        foreach ($produits as $produit) {
            $texte = $produit->getText();
            $contenu .= "\n" . $texte;
            $this->assertStringContainsString('Catégorie : Smartphone', $texte);
        }

        $this->assertStringNotContainsString('Catégorie : Ordinateur portable', $contenu);
    }
}
