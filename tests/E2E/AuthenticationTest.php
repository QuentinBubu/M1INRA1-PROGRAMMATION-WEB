<?php

declare(strict_types=1);

namespace Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Tests\Support\SeleniumTestCase;

final class AuthenticationTest extends SeleniumTestCase
{
    public function testConnexionAdminEtAccesAuBackoffice(): void
    {
        $this->connexionAdmin();

        $this->assertStringContainsString('/profil.php', $this->driver->getCurrentURL());
        $this->assertStringContainsString('Gestion Produits', $this->driver->getPageSource());

        $this->visiter('/produits/liste.php');

        $this->assertStringContainsString('Gestion des produits', $this->driver->getPageSource());
        $this->assertStringContainsString('Ajouter un produit', $this->driver->getPageSource());
    }

    public function testBackofficeRefuseSansConnexion(): void
    {
        $this->visiter('/produits/liste.php');
        $this->attendreUrl('/connexion.php');

        $this->assertStringContainsString('Connexion', $this->driver->getTitle());
        $this->assertStringContainsString(
            'Accès réservé aux administrateurs.',
            $this->driver->getPageSource()
        );
    }
}
