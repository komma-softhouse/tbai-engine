<?php

namespace Komma\Tbai\Api\Araba;

use Komma\Tbai\TicketBai;
use Komma\Tbai\Api\ApiRequestInterface;

class SubmitInvoiceRequest implements ApiRequestInterface
{
    const URL = '/facturas';
    protected string $endpoint;
    protected TicketBai $ticketbai;

    public function __construct(TicketBai $ticketbai, string $endpoint)
    {
        $this->endpoint = $endpoint;
        $this->ticketbai = $ticketbai;
    }

    public function url(): string
    {
        return $this->endpoint . static::URL;
    }

    public function data(): string
    {
        return $this->ticketbai->signed();
    }

    public function jsonDataHeader(): string
    {
        return json_encode([]);
    }
}
