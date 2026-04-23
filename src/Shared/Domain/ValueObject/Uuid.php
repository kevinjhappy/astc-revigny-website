<?php
namespace App\Shared\Domain\ValueObject;

use Ramsey\Uuid\Uuid as RamseyUuid;

final class Uuid implements \Stringable
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self(RamseyUuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        if (!RamseyUuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid UUID: $value");
        }
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
