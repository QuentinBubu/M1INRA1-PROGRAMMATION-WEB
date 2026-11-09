<?php

declare(strict_types=1);

namespace Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Tests\Support\Database;
use Tests\Support\SeleniumTestCase;

final class CartTest extends SeleniumTestCase
{
    private string $designation = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->designation = 'Test Panier ' . date('YmdHis') . ' ' . bin2hex(random_bytes(2));
        Database::creerProduit(
            $this->designation,
            100.00,
            10,
            Database::premiereCategorieId(),
            10
        );
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

    public function testTotalPanierSeMetAJourAvecLaQuantite(): void
    {
        $this->visiter('/produits/catalogue.php');

        $carte = $this->element(WebDriverBy::xpath(
            '//article[contains(@class, "produit")][.//h2[contains(., "' . $this->designation . '")]]'
        ));
        $carte->findElement(WebDriverBy::cssSelector('form button[type="submit"]'))->click();
        $this->attendreTexte(WebDriverBy::cssSelector('.info'), 'Produit ajouté au panier.');

        $this->visiter('/panier.php');

        $ligne = $this->element(WebDriverBy::cssSelector('.ligne-panier'));
        $prixUnitaire = (float) $ligne->getAttribute('data-prix-unitaire');
        $this->assertGreaterThan(0, $prixUnitaire);

        $quantite = $this->element(WebDriverBy::cssSelector('input[data-quantite]'));
        $quantite->clear();
        $quantite->sendKeys('3');

        $attendu = number_format($prixUnitaire * 3, 2, ',', ' ') . ' €';
        $this->attendreTexte(WebDriverBy::cssSelector('[data-total-panier]'), $attendu);

        $total = $this->element(WebDriverBy::cssSelector('[data-total-panier]'))->getText();
        $sousTotal = $this->element(WebDriverBy::cssSelector('[data-ligne-total]'))->getText();

        $this->assertSame(self::normaliserTexte($attendu), self::normaliserTexte($total));
        $this->assertSame(self::normaliserTexte($attendu), self::normaliserTexte($sousTotal));
    }
}
