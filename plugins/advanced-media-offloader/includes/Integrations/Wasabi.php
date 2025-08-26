<?php

namespace Advanced_Media_Offloader\Integrations;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use WPFitter\Aws\S3\S3Client;

class Wasabi extends S3_Provider
{
    public $providerName = "Wasabi";

    public function __construct()
    {
        // Do nothing.
    }

    public function getProviderName()
    {
        return $this->providerName;
    }

    public function getClient()
    {
        return new S3Client([
            'version' => 'latest',
            'endpoint' => $this->getEndpoint(),
            'region' => defined("ADVMO_WASABI_REGION") ? ADVMO_WASABI_REGION : 'us-east-1',
            'use_path_style_endpoint' => defined("ADVMO_WASABI_PATH_STYLE_ENDPOINT") ? ADVMO_WASABI_PATH_STYLE_ENDPOINT : true,
            'credentials' => [
                'key' => defined("ADVMO_WASABI_KEY") ? ADVMO_WASABI_KEY : '',
                'secret' => defined("ADVMO_WASABI_SECRET") ? ADVMO_WASABI_SECRET : '',
            ],
        ]);
    }

    public function getBucket()
    {
        return defined("ADVMO_WASABI_BUCKET") ? ADVMO_WASABI_BUCKET : null;
    }

    public function getDomain()
    {
        return defined('ADVMO_WASABI_DOMAIN') ? trailingslashit(ADVMO_WASABI_DOMAIN) : '';
    }

    private function getRegion(): string
    {
        return defined("ADVMO_WASABI_REGION") ? ADVMO_WASABI_REGION : 'us-east-1';
    }

    private function getEndpoint(): string
    {
        return sprintf(
            'https://s3.%swasabisys.com',
            ($region = $this->getRegion()) ? "{$region}." : ''
        );
    }

    public function credentialsField()
    {
        $requiredConstants = [
            'ADVMO_WASABI_KEY' => 'Your Wasabi Access Key',
            'ADVMO_WASABI_SECRET' => 'Your Wasabi Secret Key',
            'ADVMO_WASABI_BUCKET' => 'Your Wasabi Bucket Name',
            'ADVMO_WASABI_REGION' => 'Your Wasabi Region',
            'ADVMO_WASABI_DOMAIN' => 'Your Custom Domain',
        ];

        echo $this->getCredentialsFieldHTML($requiredConstants);
    }
}
