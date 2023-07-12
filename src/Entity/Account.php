<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: User::class)]
    private Collection $account;

    public function __construct()
    {
        $this->code = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getCode(): Collection
    {
        return $this->code;
    }

    public function addCode(User $account): self
    {
        if (!$this->code->contains($account)) {
            $this->code->add($account);
            $account->setAccount($this);
        }

        return $this;
    }

    public function removeCode(User $account): self
    {
        if ($this->code->removeElement($account)) {
            // set the owning side to null (unless already changed)
            if ($account->getAccount() === $this) {
                $account->setAccount(null);
            }
        }

        return $this;
    }
}