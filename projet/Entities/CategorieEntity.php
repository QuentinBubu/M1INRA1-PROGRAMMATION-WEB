<?php

class CategorieEntity
{
    private ?int $categorie_id = null;
    private string $libelle_c;
    private float $taxe_c;

    /**
     * Retourne l'identifiant de la catégorie.
     *
     * @return int|null Identifiant de la catégorie.
     */
    public function getCategorieId(): ?int
    {
        return $this->categorie_id;
    }

    /**
     * Définit l'identifiant de la catégorie.
     *
     * @param int|null $categorie_id Identifiant de la catégorie.
     *
     * @return self Entité courante.
     */
    public function setCategorieId(?int $categorie_id): self
    {
        $this->categorie_id = $categorie_id;
        return $this;
    }

    /**
     * Retourne le libellé de la catégorie.
     *
     * @return string Libellé de la catégorie.
     */
    public function getLibelleC(): string
    {
        return $this->libelle_c;
    }

    /**
     * Définit le libellé de la catégorie.
     *
     * @param string $libelle_c Libellé de la catégorie.
     *
     * @return self Entité courante.
     */
    public function setLibelleC(string $libelle_c): self
    {
        $this->libelle_c = $libelle_c;
        return $this;
    }

    /**
     * Retourne la valeur de la taxe.
     *
     * @return float Valeur de la taxe.
     */
    public function getTaxeC(): float
    {
        return $this->taxe_c;
    }

    /**
     * Définit la valeur de la taxe.
     *
     * @param float $taxe_c Valeur de la taxe.
     *
     * @return self Entité courante.
     */
    public function setTaxeC(float $taxe_c): self
    {
        $this->taxe_c = $taxe_c;
        return $this;
    }
}