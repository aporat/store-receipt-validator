<?php
namespace ReceiptValidator\WindowsStore;

use DOMDocument;
use Guzzle\Http\Client as GuzzleClient;
use ReceiptValidator\RunTimeException;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityDSig;

class Validator
{
    public function __construct()
    {
    }

    public function validate($receipt)
    {
        // Load the receipt that needs to verified as an XML document.
        $dom = new \DOMDocument;
        if (@$dom->loadXML($receipt) === false) {
            throw new RunTimeException('Invalid XML');
        }

        // The certificateId attribute is present in the document root, retrieve it.
        $certificateId = $dom->documentElement->getAttribute('CertificateId');
        if (empty($certificateId)) {
            throw new RunTimeException('Missing CertificateId in receipt');
        }

        // Retrieve the certificate from the official site.
        $certificate = $this->retrieveCertificate($certificateId);

        return $this->validateXml($dom, $certificate);
    }

    /**
     * Load the certificate with the given ID.
     *
     * @param  string   $certificateId
     * @return resource
     */
    protected function retrieveCertificate($certificateId)
    {
        $maxCertificateSize = 10000;

        // We are attempting to retrieve the following url. The getAppReceiptAsync website at
        // http://msdn.microsoft.com/en-us/library/windows/apps/windows.applicationmodel.store.currentapp.getappreceiptasync.aspx
        // lists the following format for the certificate url.
        $certificateUrl = 'https://go.microsoft.com/fwlink/?LinkId=246509&cid=' . $certificateId;

        // Make an HTTP GET request for the certificate.
        $client = new GuzzleClient($certificateUrl);
        $response = $client->get()->send();

        // Retrieve the certificate out of the response.
        $body = $response->getBody(true);

        return openssl_x509_read($body);
    }

    /**
     * Validate the receipt contained in the given XML element using the
     * certificate provided.
     *
     * @param  DOMDocument $dom
     * @param  resource         $certificate
     * @return bool
     */
    protected function validateXml(DOMDocument $dom, $certificate)
    {
        $secDsig = new XMLSecurityDSig;

        // Locate the signature in the receipt XML.
        $dsig = $secDsig->locateSignature($dom);
        if ($dsig === null) {
            throw new RunTimeException('Cannot locate receipt signature');
        }

        $secDsig->canonicalizeSignedInfo();
        $secDsig->idKeys = array('wsu:Id');
        $secDsig->idNS = array(
            'wsu' => 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd',
        );

        if (!$secDsig->validateReference()) {
            throw new RunTimeException('Reference validation failed');
        }

        $key = $secDsig->locateKey();
        if ($key === null) {
            throw new RunTimeException('Could not locate key in receipt');
        }

        $keyInfo = XMLSecEnc::staticLocateKeyInfo($key, $dsig);
        if (!$keyInfo->key) {
            $key->loadKey($certificate);
        }

        return $secDsig->verify($key) == 1;
    }
}
