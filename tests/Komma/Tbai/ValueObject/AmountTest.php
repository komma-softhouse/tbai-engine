<?php

namespace Test\Komma\Tbai\ValueObject;

use Komma\Tbai\Exception\InvalidAmountException;
use Komma\Tbai\ValueObject\Amount;
use Test\Komma\TestCase;

class AmountTest extends TestCase
{
    public function test_throws_exception_if_amount_is_not_valid(): void
    {
        $this->expectException(InvalidAmountException::class);
        new Amount("12,04", 2);
    }

    public function test_throws_exception_if_amount_exceeds_maximum(): void
    {
        $this->expectException(InvalidAmountException::class);
        new Amount("123456.02", 2);
    }
}
