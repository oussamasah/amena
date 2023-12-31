<?php

namespace App\Entity;

use App\Repository\PackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: PackageRepository::class)]
class Package
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $label = null;

    #[ORM\Column]
    private ?int $price = null;

 

    #[ORM\Column(length: 255)]
    private ?string $dest_name = null;

    #[ORM\Column]
    private ?int $dest_phone = null;

    #[ORM\Column]
    private ?int $nb_product = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\ManyToOne(inversedBy: 'packages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $expeditor = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $create_date = null;

    #[ORM\Column(length: 255)]
    private ?string $adress = null;

    #[ORM\ManyToOne(inversedBy: 'packages')]
    private ?Region $region = null;

    #[ORM\ManyToOne(inversedBy: 'packages')]
    private ?City $city = null;

    #[ORM\ManyToOne(inversedBy: 'deliverypackages')]
    private ?User $delivery = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validated_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $pickedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $pendingAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $processingAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closedAt = null;

    #[ORM\ManyToOne(inversedBy: 'package')]
    private ?Facture $facture = null;





    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }



    public function getDestName(): ?string
    {
        return $this->dest_name;
    }

    public function setDestName(string $dest_name): self
    {
        $this->dest_name = $dest_name;

        return $this;
    }

    public function getDestPhone(): ?int
    {
        return $this->dest_phone;
    }

    public function setDestPhone(int $dest_phone): self
    {
        $this->dest_phone = $dest_phone;

        return $this;
    }

    public function getNbProduct(): ?int
    {
        return $this->nb_product;
    }

    public function setNbProduct(int $nb_product): self
    {
        $this->nb_product = $nb_product;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getExpeditor(): ?User
    {
        return $this->expeditor;
    }

    public function setExpeditor(?User $expeditor): self
    {
        $this->expeditor = $expeditor;

        return $this;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->create_date;
    }

    public function setCreateDate(\DateTimeInterface $create_date): self
    {
        $this->create_date = $create_date;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(string $adress): self
    {
        $this->adress = $adress;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }
    public function getRegionName(): ?string
    {
        return $this->region->label;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function getCityName(): ?string
    {
        return $this->city->label;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getDelivery(): ?User
    {
        return $this->delivery;
    }

    public function setDelivery(?User $delivery): self
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeInterface
    {
        return $this->validated_at;
    }

    public function setValidatedAt(?\DateTimeInterface $validated_at): self
    {
        $this->validated_at = $validated_at;

        return $this;
    }

    public function getPickedAt(): ?\DateTimeInterface
    {
        return $this->pickedAt;
    }

    public function setPickedAt(?\DateTimeInterface $pickedAt): self
    {
        $this->pickedAt = $pickedAt;

        return $this;
    }

    public function getPendingAt(): ?\DateTimeInterface
    {
        return $this->pendingAt;
    }

    public function setPendingAt(?\DateTimeInterface $pendingAt): self
    {
        $this->pendingAt = $pendingAt;

        return $this;
    }

    public function getProcessingAt(): ?\DateTimeInterface
    {
        return $this->processingAt;
    }

    public function setProcessingAt(?\DateTimeInterface $processingAt): self
    {
        $this->processingAt = $processingAt;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeInterface
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeInterface $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }


  

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;

        return $this;
    }
}
