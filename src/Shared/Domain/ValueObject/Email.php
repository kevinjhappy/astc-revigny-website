<?php
namespace App\Shared\Domain\ValueObject;

final class Email implements \Stringable
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $v = strtolower(trim($value));
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: $value");
        }
        return new self($v);
    }

    public function __toString(): string { return $this->value; }
}
