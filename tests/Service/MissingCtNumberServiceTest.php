<?php

namespace App\Tests\Service;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Service\MissingCtNumberService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MissingCtNumberServiceTest extends TestCase
{
    public function testMailListsNameEmailAndAddress(): void
    {
        $account = (new Account())
            ->setFirstname('Jeanne')
            ->setLastname('Martin')
            ->setEmail('jeanne@example.test')
            ->setAddress('12 rue des Lilas');

        $repository = $this->createMock(AccountRepository::class);
        $repository->method('findAccountsWithoutCtNumberForMail')->willReturn([$account]);

        $sent = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->willReturnCallback(
            static function (Email $email) use (&$sent): void {
                $sent = $email;
            }
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = new MissingCtNumberService(
            'from@example.test',
            'to@example.test',
            $repository,
            $mailer,
            $entityManager,
        );

        self::assertSame(1, $service->sendNotification());
        self::assertInstanceOf(Email::class, $sent);
        self::assertStringContainsString(
            '- Jeanne Martin — jeanne@example.test — 12 rue des Lilas',
            (string) $sent->getTextBody()
        );
        self::assertNotNull($account->getNotificationSentAt());
    }

    public function testNotificationDateIsNotSetWhenMailFails(): void
    {
        $account = (new Account())->setEmail('jeanne@example.test');

        $repository = $this->createMock(AccountRepository::class);
        $repository->method('findAccountsWithoutCtNumberForMail')->willReturn([$account]);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')->willThrowException(new TransportException('SMTP indisponible'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = new MissingCtNumberService(
            'from@example.test',
            'to@example.test',
            $repository,
            $mailer,
            $entityManager,
        );

        try {
            $service->sendNotification();
            self::fail('Une exception était attendue.');
        } catch (TransportException) {
        }

        self::assertNull($account->getNotificationSentAt());
    }
}
