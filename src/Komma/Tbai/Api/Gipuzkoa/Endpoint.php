<?php

namespace Komma\Tbai\Api\Gipuzkoa;

use Komma\Tbai\Api\ApiRequestInterface;
use Komma\Tbai\Api\AbstractTerritory;
use Komma\Tbai\TicketBai;
use Komma\Tbai\TicketBaiCancel;
use Komma\Tbai\Zuzendu;
use Komma\Tbai\ZuzenduCancel;

class Endpoint extends AbstractTerritory
{
    const SUBMIT_ENDPOINT_DEV = 'https://tbai-z.prep.gipuzkoa.eus';
    const SUBMIT_ENDPOINT = 'https://tbai-z.egoitza.gipuzkoa.eus';

    public function headers(ApiRequestInterface $apiRequest, string $dataFile): array
    {
        return [
            'Content-Type: application/xml;charset=UTF-8',
            'Content-Length: ' . filesize($dataFile),
            'Expect: '
        ];
    }

    public function createSubmitInvoiceRequest(TicketBai $ticketBai): ApiRequestInterface
    {
        return new SubmitInvoiceRequest($ticketBai, $this->getSubmitEndpoint());
    }

    public function createCancelInvoiceRequest(TicketBaiCancel $ticketBaiCancel): ApiRequestInterface
    {
        return new CancelInvoiceRequest($ticketBaiCancel, $this->getSubmitEndpoint());
    }

    public function createSubmitZuzenduRequest(Zuzendu $zuzendu): ApiRequestInterface
    {
        return new SubmitZuzenduRequest($zuzendu, $this->getSubmitEndpoint());
    }

    public function createCancelZuzenduRequest(ZuzenduCancel $zuzenduCancel): ApiRequestInterface
    {
        return new CancelZuzenduRequest($zuzenduCancel, $this->getSubmitEndpoint());
    }

    protected function response(string $status, array $headers, string $content): Response
    {
        return new Response($status, $headers, $content);
    }
}
