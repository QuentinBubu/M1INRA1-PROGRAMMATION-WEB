<?php

/**
 * Représente un prix.
 */

class PrixEntity
{
    private ?int $prix_id = null;
    private int $produit_id;
    private float $prix_ht;

    /**
     * Retourne l'identifiant du prix.
     *
     * @return int|null Identifiant du prix.
     */
    public function getPrixId(): ?int
    {
        return $this->prix_id;
    }

    /**
     * Définit l'identifiant du prix.
     *
     * @param int|null $prix_id Identifiant du prix.
     *
     * @return self Entité courante.
     */
    public function setPrixId(?int $prix_id): self
    {
        $this->prix_id = $prix_id;

        return $this;
    }

    /**
     * Retourne l'identifiant du produit associé au prix.
     *
     * @return int Identifiant du produit.
     */
    public function getProduitId(): int
    {
        return $this->produit_id;
    }

    /**
     * Définit l'identifiant du produit associé au prix.
     *
     * @param int $produit_id Identifiant du produit.
     *
     * @return self Entité courante.
     */
    public function setProduitId(int $produit_id): self
    {
        $this->produit_id = $produit_id;

        return $this;
    }

    /**
     * Retourne le prix hors taxes.
     *
     * @return float Prix hors taxes.
     */
    public function getPrixHt(): float
    {
        return $this->prix_ht;
    }

    /**
     * Définit le prix hors taxes.
     *
     * @param float $prix_ht Prix hors taxes.
     *
     * @return self Entité courante.
     */
    public function setPrixHt(float $prix_ht): self
    {
        $this->prix_ht = $prix_ht;

        return $this;
    }

    /**
     * Calcule le prix TTC à partir du prix HT et d'un taux de TVA.
     *
     * @param float $tvaRate Taux de TVA (par défaut 20%).
     *
     * @return float Prix TTC.
     */
    public function getPrixTtc(float $tvaRate = 1.2): float
    {
        return $this->prix_ht * (1 + $tvaRate);
    }
}