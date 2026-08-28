<?php

namespace Test\Komma\RegressionTests;

use Komma\Tbai\PrivateKey;
use Komma\Tbai\TicketBai;
use Test\Komma\TestCase;

class RectificationTicketBaiTest extends TestCase
{
    /**
     * https://github.com/komma-softhouse/tbai-php-lib/issues/35
     */
    public function test_gh35_TicketBai_create_rectification_from_xml_loads_correct_invoice_number(): void
    {
        $certFile = $_ENV['TBAI_GIPUZKOA_P12_PATH'];
        $certPassword = $_ENV['TBAI_GIPUZKOA_PRIVATE_KEY'];
        $privateKey = PrivateKey::p12($certFile);

        $ticketbai = $this->ticketBaiMother->createGipuzkoaTicketBai();

        $ticketbaiRectification = $this->ticketBaiMother->createGipuzkoaTicketBaiRectificationBySubstitution($ticketbai);
        $signedRectificationFile = $this->getSignedDestinationFile();
        $ticketbaiRectification->sign($privateKey, $certPassword, $signedRectificationFile);
        $ticketbaiFromXml = TicketBai::createFromXml(file_get_contents($signedRectificationFile), $ticketbai->territory());

        $rectificationArray = $ticketbaiRectification->toArray();
        $ticketbaiFromXmlArray = $ticketbaiFromXml->toArray();
        $this->assertEquals($rectificationArray['invoice']['header']['rectifiedInvoices'][0]['invoiceNumber'], $ticketbaiFromXmlArray['invoice']['header']['rectifiedInvoices'][0]['invoiceNumber']);
    }

    private function getSignedDestinationFile(): string
    {
        $filename = tempnam(__DIR__ . '/../Tbai/__files/signedXmls',  date('YmdHis') . '-signed-');
        rename($filename, $filename . '.xml');
        return $filename . '.xml';
    }
}
