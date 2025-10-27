<?php

namespace App\Notifications;

use App\Services\MailService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompteCreatedNotification extends Notification
{

    public $password;
    public $code;
    protected $mailService;
    protected $smsService;

    /**
     * Create a new notification instance.
     */
    public function __construct($password, $code, MailService $mailService, SmsService $smsService)
    {
        $this->password = $password;
        $this->code = $code;
        $this->mailService = $mailService;
        $this->smsService = $smsService;
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
        return (new MailMessage)
                    ->subject('Informations de connexion à votre compte')
                    ->greeting('Bonjour ' . $notifiable->titulaire)
                    ->line('Votre compte a été créé avec succès.')
                    ->line('Voici vos informations de connexion :')
                    ->line('Mot de passe : ' . $this->password)
                    ->line('Veuillez utiliser ce mot de passe pour vous connecter.')
                    ->action('Se connecter', url('/login'))
                    ->line('Merci d\'utiliser notre service !');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "Votre code de verification est: {$this->code}. Utilisez ce code lors de votre premiere connexion.";
    }
}