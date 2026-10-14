<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function findAccountsWaitingForCtNumber(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a')
            ->innerJoin(Order::class, 'o', 'WITH', 'o.account = a')
            ->where('o.sync = :sync')
            ->andWhere('(o.ctNumber IS NULL OR o.ctNumber = :empty)')
            ->andWhere('(a.ct_number IS NULL OR a.ct_number = :empty)')
            ->setParameter('sync', false)
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();
    }

    public function findAccountsWithoutCtNumber(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a')
            ->innerJoin(Order::class, 'o', 'WITH', 'o.account = a')
            ->where(
                'a.ct_number IS NULL OR a.ct_number = :empty'
            )
            ->andWhere('a.sync = :sync')
            ->andWhere('a.is_new = :isNew')
            ->andWhere('o.sync = :orderSync')
            ->setParameter('empty', '')
            ->setParameter('sync', false)
            ->setParameter('isNew', true)
            ->setParameter('orderSync', false)
            ->getQuery()
            ->getResult();
    }

    public function findAccountsWithoutCtNumberForMail(): array
    {
        $fourHoursAgo = new \DateTimeImmutable('-4 hours');

        return $this->createQueryBuilder('a')
            ->select('DISTINCT a')
            ->innerJoin(Order::class, 'o', 'WITH', 'o.account = a')
            ->where(
                '(a.ct_number IS NULL OR a.ct_number = :empty)'
            )
            ->andWhere('a.is_new = :isNew')
            ->andWhere('a.sync = :sync')
            ->andWhere('o.sync = :orderSync')
            ->andWhere(
                'a.notificationSentAt IS NULL
             OR a.notificationSentAt <= :fourHoursAgo'
            )
            ->setParameter('empty', '')
            ->setParameter('isNew', true)
            ->setParameter('sync', true)
            ->setParameter('orderSync', false)
            ->setParameter('fourHoursAgo', $fourHoursAgo)
            ->getQuery()
            ->getResult();
    }

    public function countWithoutCtNumber(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.ct_number IS NULL OR a.ct_number = :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
