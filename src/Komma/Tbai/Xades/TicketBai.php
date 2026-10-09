<?php

namespace Komma\Tbai\Xades;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Komma\Tbai\Exception\SignatureException;
use OpenSSLAsymmetricKey;

/**
 * Enveloped XAdES-EPES signature of a TicketBAI document, built with DOM and
 * OpenSSL only. The previous XML signing library parsed the certificate with
 * an ASN.1 big-integer class that needs ext-gmp, which the PHP bundled in
 * desktop apps (NativePHP) does not ship; nothing here needs it.
 *
 * Each territory publishes its own signature policy: the subclasses only set
 * the policy identifier and the digest of the policy document.
 */
abstract class TicketBai
{
    public const POLICY_IDENTIFIER = '';
    public const POLICY_DIGEST = '';

    public const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';
    public const NS_XADES = 'http://uri.etsi.org/01903/v1.3.2#';

    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const RSA_SHA256 = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    private const SHA256 = 'http://www.w3.org/2001/04/xmlenc#sha256';
    private const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private const SIGNED_PROPERTIES_TYPE = 'http://uri.etsi.org/01903#SignedProperties';

    /**
     * Signs the document in place and returns it.
     *
     * @param string $certificate the signing certificate, PEM
     * @param OpenSSLAsymmetricKey|string $privateKey its private key, PEM or an OpenSSL key
     * @param string $fileName the name the signed file is stored with, recorded in DataObjectFormat
     */
    public static function signDocument(DOMDocument $document, string $certificate, $privateKey, string $fileName): DOMDocument
    {
        $root = $document->documentElement;

        if (!$root instanceof DOMElement) {
            throw new SignatureException('There is no document to sign.');
        }

        $der = self::certificateDer($certificate);
        $id = 'xmldsig-' . bin2hex(random_bytes(16));
        $referenceId = $id . '-ref0';
        $signedPropertiesId = $id . '-signedprops';

        // The enveloped transform drops the signature, so the document digest is the one it has before signing.
        $documentDigest = self::digest($root->C14N(false, false));

        $signature = $document->createElementNS(self::NS_DS, 'ds:Signature');
        $signature->setAttribute('Id', $id);
        $root->appendChild($signature);

        $signedInfo = self::ds($document, $signature, 'SignedInfo');
        self::ds($document, $signedInfo, 'CanonicalizationMethod')->setAttribute('Algorithm', self::C14N);
        self::ds($document, $signedInfo, 'SignatureMethod')->setAttribute('Algorithm', self::RSA_SHA256);

        $propertiesReference = self::ds($document, $signedInfo, 'Reference');
        $propertiesReference->setAttribute('URI', '#' . $signedPropertiesId);
        $propertiesReference->setAttribute('Type', self::SIGNED_PROPERTIES_TYPE);
        self::ds($document, self::ds($document, $propertiesReference, 'Transforms'), 'Transform')->setAttribute('Algorithm', self::C14N);
        self::ds($document, $propertiesReference, 'DigestMethod')->setAttribute('Algorithm', self::SHA256);
        $propertiesDigest = self::ds($document, $propertiesReference, 'DigestValue');

        $documentReference = self::ds($document, $signedInfo, 'Reference');
        $documentReference->setAttribute('Id', $referenceId);
        $documentReference->setAttribute('URI', '');
        self::ds($document, self::ds($document, $documentReference, 'Transforms'), 'Transform')->setAttribute('Algorithm', self::ENVELOPED);
        self::ds($document, $documentReference, 'DigestMethod')->setAttribute('Algorithm', self::SHA256);
        self::ds($document, $documentReference, 'DigestValue', $documentDigest);

        $signatureValue = self::ds($document, $signature, 'SignatureValue');

        $keyInfo = self::ds($document, $signature, 'KeyInfo');
        self::ds($document, self::ds($document, $keyInfo, 'X509Data'), 'X509Certificate', base64_encode($der));

        $object = self::ds($document, $signature, 'Object');
        $qualifying = $document->createElementNS(self::NS_XADES, 'xades:QualifyingProperties');
        $qualifying->setAttribute('Target', '#' . $id);
        $object->appendChild($qualifying);

        $signedProperties = self::xades($document, $qualifying, 'SignedProperties');
        $signedProperties->setAttribute('Id', $signedPropertiesId);
        $signatureProperties = self::xades($document, $signedProperties, 'SignedSignatureProperties');
        self::xades($document, $signatureProperties, 'SigningTime', gmdate('Y-m-d\TH:i:s\Z'));

        $certDigest = self::xades($document, self::xades($document, self::xades($document, $signatureProperties, 'SigningCertificateV2'), 'Cert'), 'CertDigest');
        self::ds($document, $certDigest, 'DigestMethod')->setAttribute('Algorithm', self::SHA256);
        self::ds($document, $certDigest, 'DigestValue', base64_encode(hash('sha256', $der, true)));

        $policyId = self::xades($document, self::xades($document, $signatureProperties, 'SignaturePolicyIdentifier'), 'SignaturePolicyId');
        self::xades($document, self::xades($document, $policyId, 'SigPolicyId'), 'Identifier', static::POLICY_IDENTIFIER);
        $policyHash = self::xades($document, $policyId, 'SigPolicyHash');
        self::ds($document, $policyHash, 'DigestMethod')->setAttribute('Algorithm', self::SHA256);
        self::ds($document, $policyHash, 'DigestValue', static::POLICY_DIGEST);
        self::xades($document, self::xades($document, $policyId, 'SigPolicyQualifiers'), 'SigPolicyQualifier', static::POLICY_IDENTIFIER);

        $format = self::xades($document, self::xades($document, $signedProperties, 'SignedDataObjectProperties'), 'DataObjectFormat');
        $format->setAttribute('ObjectReference', '#' . $referenceId);
        self::xades($document, $format, 'Description', $fileName);
        self::xades($document, $format, 'MimeType', 'text/xml');

        // Both digests below are taken in place: inclusive C14N carries the namespaces in scope at that point of the document.
        $propertiesDigest->appendChild($document->createTextNode(self::digest($signedProperties->C14N(false, false))));

        if (!openssl_sign($signedInfo->C14N(false, false), $raw, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new SignatureException('The private key could not sign the document: ' . (string) openssl_error_string());
        }

        $signatureValue->appendChild($document->createTextNode(base64_encode($raw)));

        return $document;
    }

    /**
     * Checks an enveloped signature made by signDocument() or by any XAdES
     * signer using the same algorithms: both reference digests and the
     * signature value against the certificate in KeyInfo.
     */
    public static function verifyDocument(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ds', self::NS_DS);

        $signature = $xpath->query('/*/ds:Signature')->item(0);

        if (!$signature instanceof DOMElement) {
            throw new SignatureException('The document is not signed.');
        }

        $signedInfo = $xpath->query('ds:SignedInfo', $signature)->item(0);
        $value = $xpath->query('ds:SignatureValue', $signature)->item(0);
        $certificate = $xpath->query('ds:KeyInfo/ds:X509Data/ds:X509Certificate', $signature)->item(0);

        if (!$signedInfo instanceof DOMElement || $value === null || $certificate === null) {
            throw new SignatureException('The signature is incomplete.');
        }

        foreach ($xpath->query('ds:Reference', $signedInfo) as $reference) {
            /** @var DOMElement $reference */
            $digest = trim((string) $xpath->query('ds:DigestValue', $reference)->item(0)?->textContent);
            $uri = $reference->getAttribute('URI');

            if ($uri === '') {
                $copy = new DOMDocument();
                $copy->loadXML((string) $document->saveXML());
                $copyXpath = new DOMXPath($copy);
                $copyXpath->registerNamespace('ds', self::NS_DS);
                $copySignature = $copyXpath->query('/*/ds:Signature')->item(0);
                $copySignature?->parentNode?->removeChild($copySignature);
                $actual = self::digest($copy->documentElement->C14N(false, false));
            } else {
                $target = $xpath->query('//*[@Id="' . substr($uri, 1) . '"]')->item(0);

                if ($target === null) {
                    throw new SignatureException(sprintf('The signature references %s, which is not in the document.', $uri));
                }

                $actual = self::digest($target->C14N(false, false));
            }

            if (!hash_equals($digest, $actual)) {
                throw new SignatureException(sprintf('The digest of reference "%s" does not match: the document was changed after signing.', $uri));
            }
        }

        $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(preg_replace('/\s+/', '', $certificate->textContent), 64, "\n") . "-----END CERTIFICATE-----\n";
        $publicKey = openssl_pkey_get_public($pem);

        if ($publicKey === false) {
            throw new SignatureException('The certificate of the signature cannot be read.');
        }

        $raw = base64_decode(preg_replace('/\s+/', '', $value->textContent), true);

        if ($raw === false || openssl_verify($signedInfo->C14N(false, false), $raw, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new SignatureException('The signature value does not match the signed info.');
        }
    }

    private static function certificateDer(string $certificate): string
    {
        if (!preg_match('/-----BEGIN CERTIFICATE-----(.+?)-----END CERTIFICATE-----/s', $certificate, $match)) {
            throw new SignatureException('The signing certificate is not a PEM certificate.');
        }

        $der = base64_decode(preg_replace('/\s+/', '', $match[1]), true);

        if ($der === false || $der === '') {
            throw new SignatureException('The signing certificate cannot be decoded.');
        }

        return $der;
    }

    private static function digest(string $canonical): string
    {
        return base64_encode(hash('sha256', $canonical, true));
    }

    private static function ds(DOMDocument $document, DOMElement $parent, string $name, ?string $text = null): DOMElement
    {
        return self::element($document, $parent, self::NS_DS, 'ds:' . $name, $text);
    }

    private static function xades(DOMDocument $document, DOMElement $parent, string $name, ?string $text = null): DOMElement
    {
        return self::element($document, $parent, self::NS_XADES, 'xades:' . $name, $text);
    }

    private static function element(DOMDocument $document, DOMElement $parent, string $namespace, string $name, ?string $text): DOMElement
    {
        $element = $document->createElementNS($namespace, $name);

        if ($text !== null) {
            $element->appendChild($document->createTextNode($text));
        }

        $parent->appendChild($element);

        return $element;
    }
}
