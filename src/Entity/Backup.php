<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BackupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BackupRepository::class)]
#[ApiResource]
class Backup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'backups')]
    private ?Database $associatedDatabase = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

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

    public function getAssociatedDatabase(): ?Database
    {
        return $this->associatedDatabase;
    }

    public function setAssociatedDatabase(?Database $associatedDatabase): static
    {
        $this->associatedDatabase = $associatedDatabase;

        return $this;
    }
}
