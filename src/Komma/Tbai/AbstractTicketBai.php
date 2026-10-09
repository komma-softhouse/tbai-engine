<?php

namespace Komma\Tbai;

use DOMNode;
use Komma\Tbai\Interfaces\Stringable;
use DOMDocument;
use JsonSerializable;
use SimpleXMLElement;
use Komma\Tbai\Interfaces\TbaiXml;
use Komma\Tbai\Xades\Araba as XadesAraba;
use Komma\Tbai\Xades\Bizkaia as XadesBizkaia;
use Komma\Tbai\Xades\Gipuzkoa as XadesGipuzkoa;
use Komma\Tbai\Exception\InvalidTerritoryException;
use Komma\Tbai\Exception\SignatureException;
use Komma\Tbai\Xades\TicketBai as Xades;
use Komma\Tbai\Interfaces\TbaiSignable;
use Komma\Tbai\ValueObject\Date;
use Komma\Tbai\ValueObject\VatId;
use PBurggraf\CRC\CRC8\CRC8;

abstract class AbstractTicketBai implements TbaiXml, TbaiSignable, Stringable, JsonSerializable
{
    const TERRITORY_ARABA = '01';
    const TERRITORY_BIZKAIA = '02';
    const TERRITORY_GIPUZKOA = '03';

    protected string $territory;
    private ?string $signedXmlPath = null;

    public function __construct(string $territory)
    {
        if (!in_array($territory, self::validTerritories())) {
            throw new InvalidTerritoryException();
        }
        $this->territory = $territory;
    }

    abstract public function xml(DOMDocument $document): DOMNode;
    abstract public function toArray(): array;
    abstract public function issuerVatId(): VatId;
    abstract public function expeditionDate(): Date;

    protected static function validTerritories(): array
    {
        return [
            self::TERRITORY_ARABA,
            self::TERRITORY_BIZKAIA,
            self::TERRITORY_GIPUZKOA,
        ];
    }

    public function territory(): string
    {
        return $this->territory;
    }

    public function sign(PrivateKey $privateKey, string $password, string $signedFileStoragePath): void
    {
        if (!$this->isSigned()) {
            if ($privateKey->type() === PrivateKey::TYPE_P12) {
                openssl_pkcs12_read(
                    file_get_contents($privateKey->keyPath()),
                    $certData,
                    $password
                );
            } else {
                $certData['cert'] = file_get_contents($privateKey->certPath());
                $certData['pkey'] = openssl_get_privatekey(
                    file_get_contents($privateKey->keyPath()),
                    $password
                );
            }

            if (!isset($certData['cert'], $certData['pkey']) || $certData['pkey'] === false) {
                throw new SignatureException('The certificate or its password is not valid.');
            }

            /** @var class-string<Xades> $xadesClass */
            $xadesClass = $this->getXadesClassForTerritory();

            if (!file_exists(dirname($signedFileStoragePath))) {
                mkdir(dirname($signedFileStoragePath), 0777, true);
            }

            $signed = $xadesClass::signDocument(
                $this->dom(),
                $certData['cert'],
                $certData['pkey'],
                basename($signedFileStoragePath)
            );

            file_put_contents($signedFileStoragePath, $signed->saveXML());
            $this->signedXmlPath = $signedFileStoragePath;
        }
    }

    public function verifySignature(string $xml, ?string $signedFileStoragePath = null): bool
    {
        if (!$signedFileStoragePath) {
            $signedFileStoragePath = tempnam(sys_get_temp_dir(), 'signed-xml');
        }

        file_put_contents($signedFileStoragePath, $xml);

        $document = new DOMDocument();

        try {
            if (!$document->loadXML($xml)) {
                throw new SignatureException('The signed document is not valid XML.');
            }

            Xades::verifyDocument($document);
            $this->signedXmlPath = $signedFileStoragePath;
        } catch (SignatureException $exception) {
            unlink($signedFileStoragePath);
            return false;
        }

        return true;
    }

    public function moveSignedXmlTo(string $newPath): void
    {
        rename($this->signedXmlPath, $newPath);
        $this->signedXmlPath = $newPath;
    }

    private function getXadesClassForTerritory(): string
    {
        switch ($this->territory) {
            case self::TERRITORY_ARABA:
                return XadesAraba::class;
            case self::TERRITORY_GIPUZKOA:
                return XadesGipuzkoa::class;
            case self::TERRITORY_BIZKAIA:
                return XadesBizkaia::class;
            default:
        }
        throw new InvalidTerritoryException();
    }

    public function base64Signed(): string
    {
        return base64_encode(file_get_contents($this->signedXmlPath()));
    }

    public function signatureValue(): string
    {
        $simpleXml = new SimpleXMLElement(file_get_contents($this->signedXmlPath));
        $namespaces = $simpleXml->getNamespaces(true);
        $ds = $simpleXml->children($namespaces['ds']);
        return (string)$ds->Signature->SignatureValue;
    }

    public function chainSignatureValue(): string
    {
        return substr($this->signatureValue(), 0, 100);
    }

    public function shortSignatureValue(): string
    {
        return substr($this->signatureValue(), 0, 13);
    }

    public function signedXmlPath(): string
    {
        return $this->signedXmlPath;
    }

    public function signed(): string
    {
        return file_get_contents($this->signedXmlPath);
    }

    public function isSigned(): bool
    {
        return (bool)$this->signedXmlPath;
    }

    public function dom(): DomDocument
    {
        $xml = new DOMDocument('1.0', 'utf-8');
        $domNode = $this->xml($xml);
        $xml->appendChild($domNode);
        return $xml;
    }

    public function __toString(): string
    {
        return $this->dom()->saveXml();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }


    public function setSignedXmlPath(string $path): void
    {
        $this->signedXmlPath = $path;
    }

    public function ticketbaiIdentifier(): string
    {
        $code = sprintf(
            'TBAI-%s-%s-%s-',
            $this->issuerVatId(),
            $this->expeditionDate()->short(),
            $this->shortSignatureValue()
        );

        return $code . $this->crc8($code);
    }

    private function crc8(string $data): string
    {
        $crc8 = new CRC8();
        return str_pad(
            (string)$crc8->calculate($data),
            3,
            '0',
            STR_PAD_LEFT
        );
    }
}
