<?php

declare(strict_types=1);

namespace App\Domain\Room\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

final readonly class Money implements JsonSerializable
{
    private function __construct(
        private int $amount,
        private string $currency,
    ) {}

    public static function create(int $amount, string $currency = 'KRW'): self
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        return new self($amount, strtoupper($currency));
    }

    public static function zero(string $currency = 'KRW'): self
    {
        return new self(0, strtoupper($currency));
    }

    public static function fromArray(array $data): self
    {
        return self::create(
            $data['amount'] ?? 0,
            $data['currency'] ?? 'KRW',
        );
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function format(): string
    {
        return number_format($this->amount).' '.$this->currency;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }
}
