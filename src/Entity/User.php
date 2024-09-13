<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $roles = null;

    /**
     * @var Collection<int, Database>
     */
    #[ORM\OneToMany(targetEntity: Database::class, mappedBy: 'user')]
    private Collection $userDB;

    public function __construct()
    {
        $this->userDB = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): ?string
    {
        return $this->roles;
    }

    public function setRoles(string $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection<int, Database>
     */
    public function getUserDB(): Collection
    {
        return $this->userDB;
    }

    public function addUserDB(Database $userDB): static
    {
        if (!$this->userDB->contains($userDB)) {
            $this->userDB->add($userDB);
            $userDB->setUser($this);
        }

        return $this;
    }

    public function removeUserDB(Database $userDB): static
    {
        if ($this->userDB->removeElement($userDB)) {
            // set the owning side to null (unless already changed)
            if ($userDB->getUser() === $this) {
                $userDB->setUser(null);
            }
        }

        return $this;
    }
}
