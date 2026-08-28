<?php

namespace Test\Komma\Tbai\Invoice;

use Komma\Tbai\Invoice\Breakdown\VatDetail;
use Komma\Tbai\ValueObject\Amount;
use Test\Komma\TestCase;

class InvoiceVatDetailTest extends TestCase
{
    public function test_VatDetail_is_properly_transformed_into_array(): void
    {
        $vatDetail = new VatDetail(new Amount('12.12'), new Amount('34.56'), new Amount('78.90'));
        $aVatDetail = $vatDetail->toArray();
        $this->assertEquals('12.12', $aVatDetail['taxBase']);
        $this->assertEquals('34.56', $aVatDetail['taxRate']);
        $this->assertEquals('78.90', $aVatDetail['taxQuota']);
    }
}
