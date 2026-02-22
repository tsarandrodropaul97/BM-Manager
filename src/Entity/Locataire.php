<?php

namespace App\Entity;

use App\Repository\LocataireRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocataireRepository::class)]
class Locataire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 255)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 255)]
    private ?string $nationalite = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $adresseActuelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cinRecto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cinVerso = null;

    #[ORM\Column(length: 255)]
    private ?string $profession = null;

    #[ORM\Column(length: 255)]
    private ?string $employeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\ManyToOne]
    private ?Biens $bien = null;

    #[ORM\OneToOne(inversedBy: 'locataire', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'locataire', targetEntity: Contrat::class)]
    private $contrats;

    #[ORM\OneToMany(mappedBy: 'locataire', targetEntity: AvanceSurLoyer::class, orphanRemoval: true)]
    private Collection $avances;

    public function __construct()
    {
        $this->contrats = new \Doctrine\Common\Collections\ArrayCollection();
        $this->avances = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return Collection<int, AvanceSurLoyer>
     */
    public function getAvances(): Collection
    {
        return $this->avances;
    }

    public function addAvance(AvanceSurLoyer $avance): static
    {
        if (!$this->avances->contains($avance)) {
            $this->avances->add($avance);
            $avance->setLocataire($this);
        }

        return $this;
    }

    public function removeAvance(AvanceSurLoyer $avance): static
    {
        if ($this->avances->removeElement($avance)) {
            // set the owning side to null (unless already changed)
            if ($avance->getLocataire() === $this) {
                $avance->setLocataire(null);
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(string $lieuNaissance): static
    {
        $this->lieuNaissance = $lieuNaissance;
        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(string $nationalite): static
    {
        $this->nationalite = $nationalite;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getAdresseActuelle(): ?string
    {
        return $this->adresseActuelle;
    }

    public function setAdresseActuelle(string $adresseActuelle): static
    {
        $this->adresseActuelle = $adresseActuelle;
        return $this;
    }

    public function getCinRecto(): ?string
    {
        return $this->cinRecto;
    }

    public function setCinRecto(?string $cinRecto): static
    {
        $this->cinRecto = $cinRecto;
        return $this;
    }

    public function getCinVerso(): ?string
    {
        return $this->cinVerso;
    }

    public function setCinVerso(?string $cinVerso): static
    {
        $this->cinVerso = $cinVerso;
        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(string $profession): static
    {
        $this->profession = $profession;
        return $this;
    }

    public function getEmployeur(): ?string
    {
        return $this->employeur;
    }

    public function setEmployeur(string $employeur): static
    {
        $this->employeur = $employeur;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
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

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(\DateTimeInterface $dateEntree): static
    {
        $this->dateEntree = $dateEntree;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Contrat>
     */
    public function getContrats(): \Doctrine\Common\Collections\Collection
    {
        return $this->contrats;
    }
}
