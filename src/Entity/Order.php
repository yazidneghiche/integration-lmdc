<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\Index(name: 'IDX_ORDER_SOURCE', columns: ['source', 'source_order_id'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $ctNumber = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, OrderProduct>
     */
    #[ORM\OneToMany(
        targetEntity: OrderProduct::class,
        mappedBy: 'order',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $orderProducts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $csvfile = null;

    #[ORM\Column(nullable: true)]
    private ?float $total = null;

    #[ORM\Column(nullable: true)]
    private ?int $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUpdate = null;

    #[ORM\Column]
    private ?bool $sync = false;

    #[ORM\Column(nullable: true)]
    private ?float $sageTotalHt = null;

    #[ORM\Column(nullable: true)]
    private ?float $sageTotalTtc = null;

    #[ORM\Column(nullable: true)]
    private ?int $sageType = null;

    #[ORM\Column(nullable: true)]
    private ?int $sageStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sagePiece = null;

    #[ORM\Column(nullable: true)]
    private ?float $sageNetAPayer = null;

    #[ORM\Column(length: 100)]
    private ?string $source = null;

    #[ORM\Column(length: 20, options: ['default' => 'b2c'])]
    private string $platform = 'b2c';

    #[ORM\Column(length: 100)]
    private ?string $sourceOrderId = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalHt = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalTtc = null;


    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Account $account = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    private ?int $type = null; // 0 = devis, 1 = commande, 2 = facture



    public function __construct()
    {
        $this->orderProducts = new ArrayCollection();
       // $this->createdAt = new \DateTimeImmutable();
        $this->sync = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getCtNumber(): ?string
    {
        return $this->ctNumber;
    }

    public function setCtNumber(?string $ctNumber): static
    {
        $this->ctNumber = $ctNumber;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, OrderProduct>
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProduct $orderProduct): static
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->add($orderProduct);
            $orderProduct->setOrder($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): static
    {
        if (
            $this->orderProducts->removeElement($orderProduct)
            && $orderProduct->getOrder() === $this
        ) {
            $orderProduct->setOrder(null);
        }

        return $this;
    }

    public function getCsvfile(): ?string
    {
        return $this->csvfile;
    }

    public function setCsvfile(?string $csvfile): static
    {
        $this->csvfile = $csvfile;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(?float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeImmutable
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(?\DateTimeImmutable $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function isSync(): ?bool
    {
        return $this->sync;
    }

    public function setSync(bool $sync): static
    {
        $this->sync = $sync;

        return $this;
    }

    public function getSageTotalHt(): ?float
    {
        return $this->sageTotalHt;
    }

    public function setSageTotalHt(?float $sageTotalHt): static
    {
        $this->sageTotalHt = $sageTotalHt;

        return $this;
    }

    public function getSageTotalTtc(): ?float
    {
        return $this->sageTotalTtc;
    }

    public function setSageTotalTtc(?float $sageTotalTtc): static
    {
        $this->sageTotalTtc = $sageTotalTtc;

        return $this;
    }

    public function getSageType(): ?int
    {
        return $this->sageType;
    }

    public function setSageType(?int $sageType): static
    {
        $this->sageType = $sageType;

        return $this;
    }

    public function getSageStatus(): ?int
    {
        return $this->sageStatus;
    }

    public function setSageStatus(?int $sageStatus): static
    {
        $this->sageStatus = $sageStatus;

        return $this;
    }

    public function getSagePiece(): ?string
    {
        return $this->sagePiece;
    }

    public function setSagePiece(?string $sagePiece): static
    {
        $this->sagePiece = $sagePiece;

        return $this;
    }

    public function getSageNetAPayer(): ?float
    {
        return $this->sageNetAPayer;
    }

    public function setSageNetAPayer(?float $sageNetAPayer): static
    {
        $this->sageNetAPayer = $sageNetAPayer;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): static
    {
        $this->platform = $platform;

        return $this;
    }

    public function getSourceOrderId(): ?string
    {
        return $this->sourceOrderId;
    }

    public function setSourceOrderId(string $sourceOrderId): static
    {
        $this->sourceOrderId = $sourceOrderId;

        return $this;
    }

    public function getTotalHt(): ?float
    {
        return $this->totalHt;
    }

    public function setTotalHt(?float $totalHt): static
    {
        $this->totalHt = $totalHt;

        return $this;
    }

    public function getTotalTtc(): ?float
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(?float $totalTtc): static
    {
        $this->totalTtc = $totalTtc;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        if (!in_array($type, [0, 1, 2], true)) {
            throw new \InvalidArgumentException('Type attendu : 0, 1 ou 2.');
        }

        $this->type = $type;

        return $this;
    }
}
