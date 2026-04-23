<?php
namespace App\Shared\Domain\ValueObject;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class PhoneNumber implements \Stringable
{
    private function __construct(private readonly string $e164) {}

    public static function fromString(string $value): self
    {
        $util = PhoneNumberUtil::getInstance();
        try {
            $parsed = $util->parse($value, 'FR');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Invalid phone: $value", 0, $e);
        }
        if (!$util->isValidNumber($parsed)) {
            throw new \InvalidArgumentException("Invalid phone: $value");
        }
        return new self($util->format($parsed, PhoneNumberFormat::E164));
    }

    public function equals(self $other): bool { return $this->e164 === $other->e164; }
    public function __toString(): string { return $this->e164; }
}
