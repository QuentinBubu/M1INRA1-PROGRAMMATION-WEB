<?php

declare(strict_types=1);

namespace Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Tests\Support\Database;
use Tests\Support\SeleniumTestCase;

final class ProductAdminTest extends SeleniumTestCase
{
    private string $designation = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->designation = 'Test Selenium ' . date('YmdHis') . ' ' . bin2hex(random_bytes(2));
    }

    protected function tearDown(): void
    {
        try {
            if ($this->designation !== '') {
                Database::supprimerProduitParDesignation($this->designation);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function testAjoutProduitDepuisLeBackoffice(): void
    {
        $this->connexionAdmin();
        $this->visiter('/produits/ajout.php');

        $this->remplir('designation', $this->designation);
        $this->remplir('stock', '7');
        $this->remplir('prix', '49.90');
        $this->remplir('promo', '10');
        $this->selectionner('categorie_id', 'Smartphone');

        // Cible le formulaire produit, pas le bouton de déconnexion du header.
        $this->cliquer(WebDriverBy::xpath(
            '//form[.//input[@name="designation"]]//button[@type="submit"]'
        ));
        $this->attendreUrl('/produits/liste.php');
        $this->attendreTexte(WebDriverBy::cssSelector('.info'), 'Produit ajouté avec succès.');

        $page = $this->driver->getPageSource();
        $this->assertStringContainsString($this->designation, $page);
        $this->assertStringContainsString('49,90', $page);
        $this->assertStringContainsString('10 %', $page);
    }
}
