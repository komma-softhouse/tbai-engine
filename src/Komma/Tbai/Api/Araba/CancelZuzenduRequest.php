<?php

namespace Komma\Tbai\Api\Araba;

use Komma\Tbai\Api\ApiRequestInterface;
use Komma\Tbai\ZuzenduCancel;

class CancelZuzenduRequest implements ApiRequestInterface
{
    const URL = '/anulaciones/baja';
    protected string $endpoint;
    protected ZuzenduCancel $zuzenduCancel;

    public function __construct(ZuzenduCancel $zuzenduCancel, string $endpoint)
    {
        $this->endpoint = $endpoint;
        $this->zuzenduCancel = $zuzenduCancel;
    }

    public function url(): string
    {
        return $this->endpoint . static::URL;
    }

    public function data(): string
    {
        return $this->zuzenduCancel;
    }

    public function jsonDataHeader(): string
    {
        return json_encode([]);
    }
}
