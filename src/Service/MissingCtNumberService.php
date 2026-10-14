<?php

namespace App\Service;

use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MissingCtNumberService
{
    public function __construct(
        private readonly string $sender ,
        private readonly string $recipient,
        private readonly AccountRepository $accountRepository,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function sendNotification(): int
    {
        $accounts = $this->accountRepository->findAccountsWithoutCtNumberForMail();

        if ($accounts === []) {
            return 0;
        }

        $message = <<<TEXT
Bonjour,

Les clients B2C suivants ne possèdent pas encore de numéro de compte Sage (CT_Num).

Merci de vérifier qu'ils n'existent pas déjà dans Sage, puis de les créer si nécessaire.

TEXT;

        foreach ($accounts as $account) {
            $message .= sprintf(
                "- %s %s — %s\n",
                $account->getFirstname() ?? '',
                $account->getLastname() ?? '',
                $account->getEmail() ?? '',
                $account->getAddress() ?? ''
            );
        }

        $message .= <<<TEXT


Les commandes associées restent en attente. Elles seront envoyées automatiquement après la prochaine synchronisation des comptes Sage.

Cordialement,
Le service d'intégration
TEXT;




        $email = (new Email())
            ->from($this->sender)
            ->to($this->recipient)
            ->subject('Clients B2C en attente de création dans Sage')
            ->text($message);

        $this->mailer->send($email);

        $notificationDate = new \DateTimeImmutable();

        foreach ($accounts as $account) {
            $account->setNotificationSentAt($notificationDate);
        }

        $this->entityManager->flush();

        return count($accounts);
    }
}
