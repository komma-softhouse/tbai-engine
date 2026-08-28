<?php

namespace Komma\Tbai\Api\Araba;

use Komma\Tbai\Api\ApiRequestInterface;
use Komma\Tbai\Zuzendu;

class SubmitZuzenduRequest implements ApiRequestInterface
{
    const URL = '/facturas/subsanarmodificar';
    protected string $endpoint;
    protected Zuzendu $zuzendu;

    public function __construct(Zuzendu $zuzendu, string $endpoint)
    {
        $this->endpoint = $endpoint;
        $this->zuzendu = $zuzendu;
    }

    public function url(): string
    {
        return $this->endpoint . static::URL;
    }

    public function data(): string
    {
        return $this->zuzendu;
    }

    public function jsonDataHeader(): string
    {
        return json_encode([]);
    }
}
