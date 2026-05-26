<?php
namespace App\Shared\Domain\ValueObject;

final class Email implements \Stringable
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: $value");
        }

        return new self($normalized);
    }

    public function __toString(): string { return $this->value; }
}
