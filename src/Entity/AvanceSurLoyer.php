<?php

namespace App\Entity;

use App\Repository\AvanceSurLoyerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvanceSurLoyerRepository::class)]
class AvanceSurLoyer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAccord = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $montantDetails = null;

    #[ORM\ManyToOne(inversedBy: 'avances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Locataire $locataire = null;

    #[ORM\OneToMany(mappedBy: 'avance', targetEntity: AvanceDocument::class, orphanRemoval: true)]
    private Collection $documents;

    public function __construct()
    {
        $this->dateAccord = new \DateTime();
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;
        return $this;
    }

    public function getDateAccord(): ?\DateTimeInterface
    {
        return $this->dateAccord;
    }

    public function setDateAccord(\DateTimeInterface $dateAccord): static
    {
        $this->dateAccord = $dateAccord;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getMontantDetails(): ?array
    {
        return $this->montantDetails;
    }

    public function setMontantDetails(?array $montantDetails): static
    {
        $this->montantDetails = $montantDetails;
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

    /**
     * @return Collection<int, AvanceDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(AvanceDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setAvance($this);
        }

        return $this;
    }

    public function removeDocument(AvanceDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getAvance() === $this) {
                $document->setAvance(null);
            }
        }

        return $this;
    }
}
