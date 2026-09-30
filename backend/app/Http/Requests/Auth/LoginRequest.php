<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\Auth\IdentifiantConnexion;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Le champ accepte une adresse ou un numéro : la contrainte « email »
        // rejetterait le second avant même de chercher le compte.
        return [
            'email' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        /** @var string $saisie */
        $saisie = $this->input('email');
        $user = IdentifiantConnexion::trouver($saisie);

        if ($user !== null && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => 'Compte temporairement verrouillé suite à trop de tentatives. Réessayez plus tard.',
            ]);
        }

        if ($user !== null && ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Ce compte est désactivé. Contactez un administrateur.',
            ]);
        }

        // La tentative porte sur l'adresse du compte retrouvé : le gardien
        // ne sait pas chercher par téléphone, c'est nous qui avons fait la
        // correspondance juste au-dessus.
        $identifiants = [
            'email' => $user?->email ?? $saisie,
            'password' => (string) $this->input('password'),
        ];

        if (! Auth::attempt($identifiants, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $this->registerFailedAttempt($user);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        /** @var User $authenticated */
        $authenticated = Auth::user();
        $authenticated->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();
    }

    /**
     * Incrémente le compteur d'échecs et verrouille le compte au-delà du seuil.
     */
    private function registerFailedAttempt(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $max = (int) Config::get('security.max_login_attempts', 5);
        $attempts = $user->failed_attempts + 1;

        if ($attempts >= $max) {
            $user->forceFill([
                'failed_attempts' => 0,
                'locked_until' => now()->addMinutes((int) Config::get('security.lockout_minutes', 15)),
            ])->save();

            return;
        }

        $user->forceFill(['failed_attempts' => $attempts])->save();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        // La clé porte sur la saisie normalisée : sans cela, un numéro écrit
        // de dix façons donnerait dix compteurs, et le verrou ne compterait
        // plus rien.
        $saisie = (string) $this->input('email');
        $cle = IdentifiantConnexion::normaliserTelephone($saisie) ?? $saisie;

        return Str::transliterate(Str::lower($cle).'|'.$this->ip());
    }
}
