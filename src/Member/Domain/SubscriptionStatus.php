<?php

declare(strict_types=1);

namespace App\Member\Domain;

enum SubscriptionStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payé',
        };
    }

    public static function fromLabel(string $label): self
    {
        return match($label) {
            'Payé' => self::PAID,
            'En attente' => self::PENDING,
            default => throw new \InvalidArgumentException("Statut de paiement invalide : $label"),
        };
    }
}
