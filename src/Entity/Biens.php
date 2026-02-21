<?php

namespace App\Entity;

use App\Repository\BiensRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BiensRepository::class)]
class Biens
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    private ?string $code_postal = null;

    #[ORM\Column(length: 255)]
    private ?string $ville = null;

    #[ORM\Column(length: 255)]
    private ?string $secteur = null;

    #[ORM\Column(length: 255)]
    private ?string $pays = null;

    #[ORM\Column]
    private ?int $surface_habitable = null;

    #[ORM\Column]
    private ?int $surface_total = null;

    #[ORM\Column]
    private ?int $nbr_piece = null;

    #[ORM\Column]
    private ?int $nbr_chambre = null;

    #[ORM\Column(length: 255)]
    private ?string $salle_bain = null;

    #[ORM\Column]
    private ?int $wc = null;

    #[ORM\Column]
    private ?int $etage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ascenseur = null;

    #[ORM\Column(length: 255)]
    private ?string $balcon = null;

    #[ORM\Column(length: 255)]
    private ?string $typechauffage = null;

    #[ORM\Column(length: 255)]
    private ?string $eau = null;

    #[ORM\Column(length: 255)]
    private ?string $cuisine = null;

    #[ORM\Column]
    private ?bool $is_parking = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_interphone = null;

    #[ORM\Column]
    private ?bool $is_connexion = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_garage = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_jardin = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_gardien = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_chemine = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_meuble = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $notes_interne = null;

    #[ORM\Column(length: 255)]
    private ?string $points_attention = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'biens1')]
    private ?Categorie $categorie = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $prix = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    public function setCodePostal(string $code_postal): static
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getSecteur(): ?string
    {
        return $this->secteur;
    }

    public function setSecteur(string $secteur): static
    {
        $this->secteur = $secteur;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getSurfaceHabitable(): ?int
    {
        return $this->surface_habitable;
    }

    public function setSurfaceHabitable(int $surface_habitable): static
    {
        $this->surface_habitable = $surface_habitable;

        return $this;
    }

    public function getSurfaceTotal(): ?int
    {
        return $this->surface_total;
    }

    public function setSurfaceTotal(int $surface_total): static
    {
        $this->surface_total = $surface_total;

        return $this;
    }

    public function getNbrPiece(): ?int
    {
        return $this->nbr_piece;
    }

    public function setNbrPiece(int $nbr_piece): static
    {
        $this->nbr_piece = $nbr_piece;

        return $this;
    }

    public function getNbrChambre(): ?int
    {
        return $this->nbr_chambre;
    }

    public function setNbrChambre(int $nbr_chambre): static
    {
        $this->nbr_chambre = $nbr_chambre;

        return $this;
    }

    public function getSalleBain(): ?string
    {
        return $this->salle_bain;
    }

    public function setSalleBain(string $salle_bain): static
    {
        $this->salle_bain = $salle_bain;

        return $this;
    }

    public function getWc(): ?int
    {
        return $this->wc;
    }

    public function setWc(int $wc): static
    {
        $this->wc = $wc;

        return $this;
    }

    public function getEtage(): ?int
    {
        return $this->etage;
    }

    public function setEtage(int $etage): static
    {
        $this->etage = $etage;

        return $this;
    }

    public function getAscenseur(): ?string
    {
        return $this->ascenseur;
    }

    public function setAscenseur(string $ascenseur): static
    {
        $this->ascenseur = $ascenseur;

        return $this;
    }

    public function getBalcon(): ?string
    {
        return $this->balcon;
    }

    public function setBalcon(string $balcon): static
    {
        $this->balcon = $balcon;

        return $this;
    }

    public function getTypechauffage(): ?string
    {
        return $this->typechauffage;
    }

    public function setTypechauffage(string $typechauffage): static
    {
        $this->typechauffage = $typechauffage;

        return $this;
    }

    public function getEau(): ?string
    {
        return $this->eau;
    }

    public function setEau(string $eau): static
    {
        $this->eau = $eau;

        return $this;
    }

    public function getCuisine(): ?string
    {
        return $this->cuisine;
    }

    public function setCuisine(string $cuisine): static
    {
        $this->cuisine = $cuisine;

        return $this;
    }

    public function isParking(): ?bool
    {
        return $this->is_parking;
    }

    public function setIsParking(bool $is_parking): static
    {
        $this->is_parking = $is_parking;

        return $this;
    }

    public function isInterphone(): ?bool
    {
        return $this->is_interphone;
    }

    public function setIsInterphone(?bool $is_interphone): static
    {
        $this->is_interphone = $is_interphone;

        return $this;
    }

    public function isConnexion(): ?bool
    {
        return $this->is_connexion;
    }

    public function setIsConnexion(bool $is_connexion): static
    {
        $this->is_connexion = $is_connexion;

        return $this;
    }

    public function isGarage(): ?bool
    {
        return $this->is_garage;
    }

    public function setIsGarage(?bool $is_garage): static
    {
        $this->is_garage = $is_garage;

        return $this;
    }

    public function isJardin(): ?bool
    {
        return $this->is_jardin;
    }

    public function setIsJardin(?bool $is_jardin): static
    {
        $this->is_jardin = $is_jardin;

        return $this;
    }

    public function isGardien(): ?bool
    {
        return $this->is_gardien;
    }

    public function setIsGardien(?bool $is_gardien): static
    {
        $this->is_gardien = $is_gardien;

        return $this;
    }

    public function isChemine(): ?bool
    {
        return $this->is_chemine;
    }

    public function setIsChemine(?bool $is_chemine): static
    {
        $this->is_chemine = $is_chemine;

        return $this;
    }

    public function isMeuble(): ?bool
    {
        return $this->is_meuble;
    }

    public function setIsMeuble(?bool $is_meuble): static
    {
        $this->is_meuble = $is_meuble;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getNotesInterne(): ?string
    {
        return $this->notes_interne;
    }

    public function setNotesInterne(string $notes_interne): static
    {
        $this->notes_interne = $notes_interne;

        return $this;
    }

    public function getPointsAttention(): ?string
    {
        return $this->points_attention;
    }

    public function setPointsAttention(string $points_attention): static
    {
        $this->points_attention = $points_attention;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }
}
