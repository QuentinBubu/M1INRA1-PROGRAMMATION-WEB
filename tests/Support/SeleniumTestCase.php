<?php

declare(strict_types=1);

namespace Tests\Support;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverWait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Base commune aux tests E2E Selenium.
 *
 * Le navigateur est exécuté dans le conteneur Selenium. Les URL de l'application
 * doivent donc utiliser le nom du service Docker (`http://web` par défaut).
 */
abstract class SeleniumTestCase extends TestCase
{
    protected RemoteWebDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        self::attendreSelenium();

        $options = new ChromeOptions();
        $options->addArguments([
            '--headless=new',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--window-size=1920,1080',
            '--lang=fr-FR',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $this->driver = RemoteWebDriver::create(
            self::seleniumUrl(),
            $capabilities,
            60000,
            60000
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->driver)) {
            $this->driver->quit();
        }

        parent::tearDown();
    }

    /**
     * Construit une URL absolue de l'application testée.
     */
    protected function url(string $chemin): string
    {
        return rtrim(self::env('APP_URL', 'http://web'), '/') . '/' . ltrim($chemin, '/');
    }

    /**
     * Ouvre une page de l'application.
     */
    protected function visiter(string $chemin): void
    {
        $this->driver->get($this->url($chemin));
    }

    /**
     * Attend la présence d'un élément et le retourne.
     */
    protected function element(WebDriverBy $by, int $timeout = 15): RemoteWebElement
    {
        /** @var RemoteWebElement $element */
        $element = (new WebDriverWait($this->driver, $timeout))
            ->until(static fn (RemoteWebDriver $driver): RemoteWebElement => $driver->findElement($by));

        return $element;
    }

    /**
     * Attend qu'un fragment soit présent dans l'URL courante.
     */
    protected function attendreUrl(string $fragment, int $timeout = 15): void
    {
        (new WebDriverWait($this->driver, $timeout))
            ->until(static fn (RemoteWebDriver $driver): bool => str_contains($driver->getCurrentURL(), $fragment));
    }

    /**
     * Attend qu'un texte soit présent dans un élément.
     */
    protected function attendreTexte(WebDriverBy $by, string $texte, int $timeout = 15): void
    {
        (new WebDriverWait($this->driver, $timeout))
            ->until(function () use ($by, $texte): bool {
                $element = $this->driver->findElement($by);

                return str_contains(
                    self::normaliserTexte($element->getText()),
                    self::normaliserTexte($texte)
                );
            });
    }

    /**
     * Remplit un champ texte ou numérique.
     */
    protected function remplir(string $name, string $valeur): void
    {
        $champ = $this->element(WebDriverBy::name($name));
        $champ->clear();
        $champ->sendKeys($valeur);
    }

    /**
     * Sélectionne une option d'un <select> par son libellé visible.
     */
    protected function selectionner(string $name, string $libelle): void
    {
        $select = new WebDriverSelect($this->element(WebDriverBy::name($name)));
        $select->selectByVisibleText($libelle);
    }

    /**
     * Clique sur un élément après avoir attendu sa présence.
     */
    protected function cliquer(WebDriverBy $by): void
    {
        $this->element($by)->click();
    }

    /**
     * Connecte l'administrateur de test.
     *
     * Les identifiants peuvent être surchargés avec TEST_ADMIN_LOGIN et
     * TEST_ADMIN_PASSWORD si le mot de passe par défaut a été changé.
     */
    protected function connexionAdmin(): void
    {
        $this->visiter('/connexion.php');
        $this->remplir('identifiant', self::env('TEST_ADMIN_LOGIN', 'admin'));
        $this->remplir('mdp', self::env('TEST_ADMIN_PASSWORD', 'Admin1234!2026'));
        $this->cliquer(WebDriverBy::cssSelector('form button[type="submit"]'));
        $this->attendreUrl('/profil.php');
    }

    /**
     * Normalise les espaces pour comparer les montants formatés en français.
     */
    protected static function normaliserTexte(string $texte): string
    {
        return preg_replace('/\s+/u', ' ', trim($texte)) ?? trim($texte);
    }

    private static function env(string $nom, string $defaut): string
    {
        $valeur = getenv($nom);

        return $valeur !== false && $valeur !== '' ? $valeur : $defaut;
    }

    private static function seleniumUrl(): string
    {
        return rtrim(self::env('SELENIUM_URL', 'http://localhost:4444'), '/');
    }

    /**
     * Attend que le conteneur Selenium soit prêt avant de créer la session.
     */
    private static function attendreSelenium(): void
    {
        $statusUrl = self::seleniumUrl() . '/status';
        $limite = time() + 30;

        do {
            $ch = curl_init($statusUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 2,
            ]);

            $reponse = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($code === 200 && is_string($reponse)) {
                $donnees = json_decode($reponse, true);
                if (is_array($donnees) && (($donnees['value']['ready'] ?? false) === true)) {
                    return;
                }
            }

            sleep(1);
        } while (time() < $limite);

        throw new RuntimeException(
            'Selenium n\'est pas prêt. Lancez "docker compose up -d selenium" puis relancez les tests.'
        );
    }
}
