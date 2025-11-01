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
        $appName = config('app.name', 'LINGUERE BANK');

        $mail = (new MailMessage)
                    ->subject($appName . ' - Informations de connexion à votre compte')
                    ->greeting('Bonjour ' . ($notifiable->titulaire ?? ''))
                    ->line('Votre compte a été créé avec succès.');

        if ($this->password) {
            // Attempt to include the login (user) if available via the client relation
            $login = null;
            try {
                $login = $notifiable->utilisateur->login ?? null;
            } catch (\Throwable $e) {
                // relation might not be loaded or user missing; ignore
                $login = null;
            }

            $mail->line('Voici vos informations de connexion :');
            if ($login) {
                $mail->line('Login : ' . $login);
            }
            $mail->line('Mot de passe : ' . $this->password)
                 ->line('Veuillez utiliser ces informations pour vous connecter.');
        }

        $mail->action('Se connecter', url('/login'))
             ->line('Merci d\'utiliser ' . $appName . ' !');

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