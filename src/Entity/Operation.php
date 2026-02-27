<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isGlobal = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, Locataire>
     */
    #[ORM\ManyToMany(targetEntity: Locataire::class)]
    private Collection $targetLocataires;

    /**
     * @var Collection<int, OperationFile>
     */
    #[ORM\OneToMany(mappedBy: 'operation', targetEntity: OperationFile::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $files;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->targetLocataires = new ArrayCollection();
        $this->files = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isGlobal(): ?bool
    {
        return $this->isGlobal;
    }

    public function setIsGlobal(bool $isGlobal): static
    {
        $this->isGlobal = $isGlobal;
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

    /**
     * @return Collection<int, Locataire>
     */
    public function getTargetLocataires(): Collection
    {
        return $this->targetLocataires;
    }

    public function addTargetLocataire(Locataire $targetLocataire): static
    {
        if (!$this->targetLocataires->contains($targetLocataire)) {
            $this->targetLocataires->add($targetLocataire);
        }
        return $this;
    }

    public function removeTargetLocataire(Locataire $targetLocataire): static
    {
        $this->targetLocataires->removeElement($targetLocataire);
        return $this;
    }

    /**
     * @return Collection<int, OperationFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(OperationFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setOperation($this);
        }
        return $this;
    }

    public function removeFile(OperationFile $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getOperation() === $this) {
                $file->setOperation(null);
            }
        }
        return $this;
    }
}
