<?php

namespace Advanced_Media_Offloader\Integrations;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use WPFitter\Aws\S3\S3Client;

class AmazonS3 extends S3_Provider
{
    public $providerName = "Amazon S3";

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
            'region' => defined("ADVMO_AWS_REGION") ? ADVMO_AWS_REGION : 'us-east-1',
            'credentials' => [
                'key' => defined("ADVMO_AWS_KEY") ? ADVMO_AWS_KEY : '',
                'secret' => defined("ADVMO_AWS_SECRET") ? ADVMO_AWS_SECRET : '',
            ]
        ]);
    }

    public function getBucket()
    {
        return defined("ADVMO_AWS_BUCKET") ? ADVMO_AWS_BUCKET : null;
    }

    public function getDomain()
    {
        return defined('ADVMO_AWS_DOMAIN') ? trailingslashit(ADVMO_AWS_DOMAIN) : '';
    }

    public function credentialsField()
    {
        $requiredConstants = [
            'ADVMO_AWS_KEY' => "Your AWS Access Key",
            'ADVMO_AWS_SECRET' => "Your AWS Secret Key",
            'ADVMO_AWS_BUCKET' => "Your S3 Bucket Name",
            'ADVMO_AWS_REGION' => "Your S3 Bucket Region",
            'ADVMO_AWS_DOMAIN' => "Your Custom Domain",
        ];

        echo $this->getCredentialsFieldHTML($requiredConstants);
    }
}
