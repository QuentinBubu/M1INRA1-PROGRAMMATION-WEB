<?php

declare(strict_types=1);

/**
 * Représente un produit.
 */
class ProductEntity
{
    private ?int $p_id = null;
    private string $designation_p;
    private string $date_in;
    private ?string $times_in = null;
    private int $stock_p = 0;
    private ?string $url_image_p = null;
    private ?int $promo_p = null;
    private int $categorie_id;

    /**
     * Retourne l'identifiant du produit.
     *
     * @return int|null Identifiant du produit.
     */
    public function getPId(): ?int
    {
        return $this->p_id;
    }

    /**
     * Retourne la désignation du produit.
     *
     * @return string Désignation du produit.
     */
    public function getDesignationP(): string
    {
        return $this->designation_p;
    }

    /**
     * Définit la désignation du produit.
     *
     * @param string $designation_p Désignation du produit.
     *
     * @return self Entité courante.
     */
    public function setDesignationP(string $designation_p): self
    {
        $this->designation_p = $designation_p;

        return $this;
    }

    /**
     * Retourne la date d'entrée.
     *
     * @return string Date d'entrée.
     */
    public function getDateIn(): string
    {
        return $this->date_in;
    }

    /**
     * Définit la date d'entrée.
     *
     * @param string $date_in Date d'entrée.
     *
     * @return self Entité courante.
     */
    public function setDateIn(string $date_in): self
    {
        $this->date_in = $date_in;

        return $this;
    }

    /**
     * Retourne l'heure d'entrée.
     *
     * @return string|null Heure d'entrée.
     */
    public function getTimesIn(): ?string
    {
        return $this->times_in;
    }

    /**
     * Définit l'heure d'entrée.
     *
     * @param string|null $times_in Heure d'entrée.
     *
     * @return self Entité courante.
     */
    public function setTimesIn(?string $times_in): self
    {
        $this->times_in = $times_in;

        return $this;
    }

    /**
     * Retourne la quantité en stock.
     *
     * @return int Quantité en stock.
     */
    public function getStockP(): int
    {
        return $this->stock_p;
    }

    /**
     * Définit la quantité en stock.
     *
     * @param int $stock_p Quantité en stock.
     *
     * @return self Entité courante.
     */
    public function setStockP(int $stock_p): self
    {
        $this->stock_p = $stock_p;

        return $this;
    }

    /**
     * Retourne l'URL de l'image du produit.
     *
     * @return string|null URL de l'image du produit.
     */
    public function getUrlImageP(): ?string
    {
        return $this->url_image_p;
    }

    /**
     * Définit l'URL de l'image du produit.
     *
     * @param string|null $url_image_p URL de l'image du produit.
     *
     * @return self Entité courante.
     */
    public function setUrlImageP(?string $url_image_p): self
    {
        $this->url_image_p = $url_image_p;

        return $this;
    }

    /**
     * Retourne la valeur de la promotion.
     *
     * @return int|null Valeur de la promotion.
     */
    public function getPromoP(): ?int
    {
        return $this->promo_p;
    }

    /**
     * Définit la valeur de la promotion.
     *
     * @param int|null $promo_p Valeur de la promotion.
     *
     * @return self Entité courante.
     */
    public function setPromoP(?int $promo_p): self
    {
        $this->promo_p = $promo_p;

        return $this;
    }

    /**
     * Retourne l'identifiant de la catégorie.
     *
     * @return int Identifiant de la catégorie.
     */
    public function getCategorieId(): int
    {
        return $this->categorie_id;
    }

    /**
     * Définit l'identifiant de la catégorie.
     *
     * @param int $categorie_id Identifiant de la catégorie.
     *
     * @return self Entité courante.
     */
    public function setCategorieId(int $categorie_id): self
    {
        $this->categorie_id = $categorie_id;

        return $this;
    }
}
