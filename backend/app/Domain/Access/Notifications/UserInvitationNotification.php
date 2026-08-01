<?php

declare(strict_types=1);

namespace App\Domain\Access\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Invitation : lien signé (72 h via le broker de mot de passe) pour que
 * l'utilisateur définisse lui-même son mot de passe. L'admin n'en saisit aucun.
 */
final class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();
        $url = rtrim((string) config('app.frontend_url', config('app.url')), '/')
            .'/invitation?token='.$this->token.'&email='.urlencode($email);

        return (new MailMessage)
            ->subject('Invitation IGOUTECH — définir votre mot de passe')
            ->greeting('Bienvenue sur IGOUTECH')
            ->line('Un compte vient de vous être créé. Définissez votre mot de passe pour l\'activer.')
            ->action('Définir mon mot de passe', $url)
            ->line('Ce lien expire dans 72 heures.');
    }

    /**
     * Jeton d'invitation (broker de mot de passe).
     */
    public static function tokenFor(CanResetPassword $user): string
    {
        return Password::broker()->createToken($user);
    }
}
