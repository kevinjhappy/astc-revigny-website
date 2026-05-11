<?php
namespace App\Member\Domain;

enum MembershipType: string
{
    case TERRAIN = 'TERRAIN';
    case TERRAIN_TOURNOIS = 'TERRAIN_TOURNOIS';
    case TERRAIN_TOURNOIS_COURS = 'TERRAIN_TOURNOIS_COURS';

    public function label(): string
    {
        return match($this) {
            self::TERRAIN => 'Terrains',
            self::TERRAIN_TOURNOIS => 'Terrains + Tournois',
            self::TERRAIN_TOURNOIS_COURS => 'Terrains + Tournois + Cours',
        };
    }

    public function hasTournamentAccess(): bool
    {
        return $this !== self::TERRAIN;
    }

    public static function fromLabel(string $label): self
    {
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Type de cotisation invalide : $label");
    }
}
