<?php

namespace Komma\Tbai\LROE\Expenses\Interfaces;

use Komma\Tbai\Interfaces\TbaiXml;
use Komma\Tbai\ValueObject\Date;

interface ExpensesInvoice extends TbaiXml
{
    public function selfEmployed(): bool;
    public function receptionDate(): Date;
    public function recipientVatId(): string;
    public function recipientName(): string;
}
