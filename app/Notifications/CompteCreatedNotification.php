<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompteCreatedNotification extends Notification
{

    use Queueable;

    public $password;
    public $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(?string $password, string $code)
    {
        $this->password = $password;
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'sms'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
                    ->subject('Informations de connexion à votre compte')
                    ->greeting('Bonjour ' . ($notifiable->titulaire ?? ''))
                    ->line('Votre compte a été créé avec succès.');

        if ($this->password) {
            $mail->line('Voici vos informations de connexion :')
                 ->line('Mot de passe : ' . $this->password)
                 ->line('Veuillez utiliser ce mot de passe pour vous connecter.');
        }

        $mail->action('Se connecter', url('/login'))
             ->line('Merci d\'utiliser notre service !');

        return $mail;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "Votre code de verification est: {$this->code}. Utilisez ce code lors de votre premiere connexion.";
    }

    /**
     * Make this notification queueable.
     */
    public function shouldQueue()
    {
        return true;
    }
}