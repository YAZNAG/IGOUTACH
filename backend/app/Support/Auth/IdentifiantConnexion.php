<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;

/**
 * Retrouve un compte à partir de ce qui a été saisi : une adresse e-mail ou
 * un numéro de téléphone.
 *
 * Un responsable de terrain connaît son numéro par cœur ; il tape mal une
 * adresse en « @igoutech.optizaworks.com » sur un clavier de téléphone. Les
 * deux voies mènent donc au même compte, avec le même mot de passe.
 *
 * Le numéro est comparé sur ses chiffres significatifs, pas sur sa forme :
 * « 06 12 34 56 78 », « 0612345678 » et « +212612345678 » désignent la même
 * ligne, et personne ne se souvient de la façon dont l'administrateur l'a
 * saisi le jour de la création du compte.
 */
final class IdentifiantConnexion
{
    /** Longueur du numéro national marocain, indicatif et zéro retirés. */
    private const CHIFFRES_SIGNIFIATIFS = 9;

    public static function estEmail(string $saisie): bool
    {
        return str_contains($saisie, '@');
    }

    /**
     * Réduit un numéro à ses chiffres significatifs, ou null s'il n'en a pas
     * assez pour désigner une ligne.
     */
    public static function normaliserTelephone(?string $saisie): ?string
    {
        if ($saisie === null) {
            return null;
        }

        $chiffres = preg_replace('/\D+/', '', $saisie) ?? '';

        // Un numéro écrit avec l'indicatif ou le zéro initial désigne la même
        // ligne : on ne garde que la fin, qui est la partie stable.
        if (strlen($chiffres) < self::CHIFFRES_SIGNIFIATIFS) {
            return null;
        }

        return substr($chiffres, -self::CHIFFRES_SIGNIFIATIFS);
    }

    /**
     * Le compte correspondant, ou null s'il n'y en a pas.
     *
     * Retourne null AUSSI quand plusieurs comptes actifs portent le même
     * numéro : deviner lequel ouvrirait la porte au mauvais. L'administrateur
     * doit alors corriger la saisie en double.
     *
     * La comparaison des numéros se fait en mémoire plutôt qu'en SQL : les
     * numéros sont stockés tels que saisis, avec espaces et indicatifs, et
     * aucune égalité SQL ne les rapprocherait. Le nombre de comptes se compte
     * en dizaines — la table entière tient en une requête.
     */
    public static function trouver(string $saisie): ?User
    {
        $saisie = trim($saisie);

        if ($saisie === '') {
            return null;
        }

        if (self::estEmail($saisie)) {
            return User::query()->where('email', $saisie)->first();
        }

        $cherche = self::normaliserTelephone($saisie);

        if ($cherche === null) {
            return null;
        }

        $correspondants = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get()
            ->filter(fn (User $u): bool => self::normaliserTelephone($u->phone) === $cherche);

        return $correspondants->count() === 1 ? $correspondants->first() : null;
    }
}
