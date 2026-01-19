<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Room;

use App\Domain\Room\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_create_money(): void
    {
        $money = Money::create(5000, 'KRW');

        $this->assertEquals(5000, $money->amount());
        $this->assertEquals('KRW', $money->currency());
    }

    public function test_cannot_create_negative_money(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::create(-1000);
    }

    public function test_can_create_zero_money(): void
    {
        $money = Money::zero();

        $this->assertEquals(0, $money->amount());
    }

    public function test_can_add_money(): void
    {
        $money1 = Money::create(5000);
        $money2 = Money::create(3000);

        $result = $money1->add($money2);

        $this->assertEquals(8000, $result->amount());
    }

    public function test_cannot_add_different_currencies(): void
    {
        $krw = Money::create(5000, 'KRW');
        $usd = Money::create(50, 'USD');

        $this->expectException(InvalidArgumentException::class);

        $krw->add($usd);
    }

    public function test_can_multiply_money(): void
    {
        $money = Money::create(5000);

        $result = $money->multiply(4);

        $this->assertEquals(20000, $result->amount());
    }

    public function test_can_compare_money(): void
    {
        $money1 = Money::create(5000);
        $money2 = Money::create(5000);
        $money3 = Money::create(3000);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
        $this->assertTrue($money1->isGreaterThan($money3));
    }

    public function test_can_format_money(): void
    {
        $money = Money::create(5000, 'KRW');

        $this->assertEquals('5,000 KRW', $money->format());
    }
}
