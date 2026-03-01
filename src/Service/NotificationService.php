<?php

namespace App\Service;

use App\Entity\AvanceSurLoyer;
use App\Entity\Locataire;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{
    private const ADMIN_EMAIL = 'tsarandropaul97@gmail.com';
    private const ADMIN_NAME = 'SILENY Tsarandro Paul';
    private const ADMIN_PHONE = '03433 79087';
    private const APP_BASE_URL = 'http://bien.sileny.isfpp-madagascar.com';

    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function notifyOwnerOnNewAvance(AvanceSurLoyer $avance): void
    {
        $locataire = $avance->getLocataire();
        $email = (new TemplatedEmail())
            ->from(new Address(self::ADMIN_EMAIL, 'BM Manager'))
            ->to(self::ADMIN_EMAIL)
            ->subject('Nouvelle déclaration d\'avance sur loyer - ' . $locataire->getNomComplet())
            ->htmlTemplate('emails/avance_new_to_owner.html.twig')
            ->context([
                'avance' => $avance,
                'locataire' => $locataire,
                'ownerName' => self::ADMIN_NAME,
            ]);

        $this->mailer->send($email);
    }

    public function notifyTenantOnNewAvance(AvanceSurLoyer $avance): void
    {
        $locataire = $avance->getLocataire();
        $user = $locataire->getUser();

        if (!$user || !$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address(self::ADMIN_EMAIL, self::ADMIN_NAME))
            ->to($user->getEmail())
            ->subject('Nouvelle avance sur loyer enregistrée par le propriétaire')
            ->htmlTemplate('emails/avance_new_to_tenant.html.twig')
            ->context([
                'avance' => $avance,
                'locataire' => $locataire,
                'ownerName' => self::ADMIN_NAME,
                'ownerPhone' => self::ADMIN_PHONE,
            ]);

        $this->mailer->send($email);
    }

    public function sendReceiptToTenant(AvanceSurLoyer $avance): void
    {
        $locataire = $avance->getLocataire();
        $user = $locataire->getUser();

        if (!$user || !$user->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address(self::ADMIN_EMAIL, self::ADMIN_NAME))
            ->to($user->getEmail())
            ->subject('Reçu d\'avance sur loyer - Confirmation de validation')
            ->htmlTemplate('emails/avance_receipt.html.twig')
            ->context([
                'avance' => $avance,
                'locataire' => $locataire,
                'ownerName' => self::ADMIN_NAME,
                'ownerPhone' => self::ADMIN_PHONE,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie les identifiants de connexion à un nouvel utilisateur.
     */
    public function sendWelcomeEmail(string $toEmail, string $plainPassword, ?string $fullName = null): void
    {
        $loginUrl = self::APP_BASE_URL;

        $email = (new TemplatedEmail())
            ->from(new Address(self::ADMIN_EMAIL, self::ADMIN_NAME))
            ->to($toEmail)
            ->subject('Bienvenue sur BM Manager - Vos identifiants de connexion')
            ->htmlTemplate('emails/user_welcome.html.twig')
            ->context([
                'userEmail'  => $toEmail,
                'password'   => $plainPassword,
                'fullName'   => $fullName,
                'loginUrl'   => $loginUrl,
                'ownerName'  => self::ADMIN_NAME,
                'ownerPhone' => self::ADMIN_PHONE,
            ]);

        $this->mailer->send($email);
    }
}
