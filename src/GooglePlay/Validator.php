<?php
namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\RunTimeException as RunTimeException;

class Validator extends AbstractValidator
{
    protected function initClient($options = [])
    {
        $this->_client = new \Google_Client();
        $this->_client->setClientId($options['client_id']);
        $this->_client->setClientSecret($options['client_secret']);

        $cached_access_token_path = sys_get_temp_dir() . '/' . 'googleplay_access_token_' . md5($options['client_id']) . '.txt';

        touch($cached_access_token_path);
        chmod($cached_access_token_path, 0770);

        try {
            $this->_client->setAccessToken(file_get_contents($cached_access_token_path));
        } catch (\Exception $e) {
          // skip exceptions when the access token is not valid
        }

        try {
            if ($this->_client->isAccessTokenExpired()) {
                $this->_client->refreshToken($options['refresh_token']);
                file_put_contents($cached_access_token_path, $this->_client->getAccessToken());
            }
        } catch (\Exception $e) {
            throw new RuntimeException('Failed refreshing access token - ' . $e->getMessage());
        }
    }
}
