<?php

namespace ReceiptValidator\GooglePlay;

class ServiceAccountValidator extends AbstractValidator
{
    protected function initClient($options = [])
    {
        $credentials = new \Google_Auth_AssertionCredentials(
            $options['client_email'],
            [\Google_Service_AndroidPublisher::ANDROIDPUBLISHER],
            $options['p12_key_path']
        );
        $this->_client = new \Google_Client();
        $this->_client->setAssertionCredentials($credentials);
        if ($this->_client->getAuth()->isAccessTokenExpired()) {
            $this->_client->getAuth()->refreshTokenWithAssertion();
        }
    }
}
