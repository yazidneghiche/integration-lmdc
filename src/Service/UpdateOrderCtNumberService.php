<?php

namespace App\Service;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpdateOrderCtNumberService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function update(): int
    {
        $orders = $this->orderRepository->createQueryBuilder('o')
            ->innerJoin('o.account', 'a')
            ->addSelect('a')
            ->where('o.ctNumber IS NULL')
            ->andWhere('a.ct_number IS NOT NULL')
            ->andWhere('a.ct_number <> :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();

        foreach ($orders as $order) {
            $order->setCtNumber(
                $order->getAccount()->getCtNumber()
            );
        }

        $this->entityManager->flush();

        return count($orders);
    }
}
