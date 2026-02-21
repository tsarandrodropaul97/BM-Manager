<?php

namespace App\Entity;

use App\Repository\ContratRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
class Contrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'contrats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Locataire $locataire = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Biens $bien = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank]
    private ?string $loyerHorsCharges = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $charges = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $depotGarantie = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'brouillon'; // brouillon, actif, termine, resilie

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentPdf = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLocataire(): ?Locataire
    {
        return $this->locataire;
    }

    public function setLocataire(?Locataire $locataire): static
    {
        $this->locataire = $locataire;
        return $this;
    }

    public function getBien(): ?Biens
    {
        return $this->bien;
    }

    public function setBien(?Biens $bien): static
    {
        $this->bien = $bien;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getLoyerHorsCharges(): ?string
    {
        return $this->loyerHorsCharges;
    }

    public function setLoyerHorsCharges(string $loyerHorsCharges): static
    {
        $this->loyerHorsCharges = $loyerHorsCharges;
        return $this;
    }

    public function getCharges(): ?string
    {
        return $this->charges;
    }

    public function setCharges(string $charges): static
    {
        $this->charges = $charges;
        return $this;
    }

    public function getDepotGarantie(): ?string
    {
        return $this->depotGarantie;
    }

    public function setDepotGarantie(string $depotGarantie): static
    {
        $this->depotGarantie = $depotGarantie;
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

    public function getDocumentPdf(): ?string
    {
        return $this->documentPdf;
    }

    public function setDocumentPdf(?string $documentPdf): static
    {
        $this->documentPdf = $documentPdf;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
