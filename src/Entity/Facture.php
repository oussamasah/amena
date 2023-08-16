<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
class Facture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'facture')]
    private ?User $expeditor = null;



    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column]
    private ?int $tax = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createAt = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closedAt = null;

    #[ORM\OneToMany(mappedBy: 'facture' , targetEntity: Package::class)]
    private Collection $package;

    public function __construct()
    {
        $this->package = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpeditor(): ?User
    {
        return $this->expeditor;
    }

    public function setExpeditor(?User $expeditor): static
    {
        $this->expeditor = $expeditor;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getTax(): ?int
    {
        return $this->tax;
    }

    public function setTax(int $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeInterface $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeInterface
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeInterface $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * @return Collection<int, Package>
     */
    public function getPackage(): Collection
    {
        return $this->package;
    }

    public function addPackage(Package $package): static
    {
        if (!$this->package->contains($package)) {
            $this->package->add($package);
            $package->setFacture($this);
        }

        return $this;
    }

    public function removePackage(Package $package): static
    {
        if ($this->package->removeElement($package)) {
            // set the owning side to null (unless already changed)
            if ($package->getFacture() === $this) {
                $package->setFacture(null);
            }
        }

        return $this;
    }
}
