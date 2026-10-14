<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findLastUpdateBySource(string $source): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('o')
            ->select('MAX(o.lastUpdate)')
            ->andWhere('o.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->getSingleScalarResult();

        if (!$result) {
            return null;
        }

        return new \DateTimeImmutable($result);
    }

    public function findOrdersReadyToSync(): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.account', 'a')
            ->where('o.sync = false')
            ->andWhere('a.is_new = false')
            ->andWhere('o.ctNumber IS NOT NULL')
            ->andWhere('o.ctNumber != \'\'')
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.sync = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSynchronized(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.sync = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
