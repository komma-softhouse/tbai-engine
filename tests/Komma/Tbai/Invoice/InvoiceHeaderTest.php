<?php

namespace Test\Komma\Tbai\Invoice;

use Komma\Tbai\Exception\InvalidDateException;
use Komma\Tbai\Exception\InvalidTimeException;
use Komma\Tbai\Invoice\Header;
use Komma\Tbai\ValueObject\Date;
use Komma\Tbai\ValueObject\Time;
use Test\Komma\TestCase;

class InvoiceHeaderTest extends TestCase
{
    public function test_invoice_header_can_be_created(): void
    {
        $invoiceHeader = Header::create('00001', new Date('02-09-2021'), new Time('21:21:21'), 'SERIE');
        $this->assertEquals('SERIE', $invoiceHeader->series());
        $this->assertEquals('00001', $invoiceHeader->invoiceNumber());
        $this->assertNotEquals('1', $invoiceHeader->invoiceNumber());
        $this->assertEquals('02-09-2021', $invoiceHeader->expeditionDate());
        $this->assertEquals('21:21:21', $invoiceHeader->expeditionTime());
    }

    public function test_wrong_date_format_throws_exception(): void
    {
        $this->expectException(InvalidDateException::class);
        Header::create('00001', new Date('2021-09-02'), new Time('21:21:21'), 'SERIE');
    }

    public function test_wrong_time_format_throws_exception(): void
    {
        $this->expectException(InvalidTimeException::class);
        Header::create('00001', new Date('02-09-2021'), new Time('25:21'), 'SERIE');
    }
}
