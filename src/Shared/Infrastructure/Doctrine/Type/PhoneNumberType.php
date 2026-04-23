<?php
namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Shared\Domain\ValueObject\PhoneNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class PhoneNumberType extends Type
{
    public const NAME = 'phone_number';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 20]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PhoneNumber
    {
        return $value === null ? null : PhoneNumber::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : (string)$value;
    }

    public function getName(): string { return self::NAME; }
}
