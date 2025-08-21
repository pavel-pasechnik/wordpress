<?php

namespace Advanced_Media_Offloader\Integrations;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use WPFitter\Aws\S3\S3Client;

class MinIO extends S3_Provider
{
    public $providerName = "MinIO";

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
            'endpoint' => defined("ADVMO_MINIO_ENDPOINT") ? ADVMO_MINIO_ENDPOINT : '',
            'region' => defined("ADVMO_MINIO_REGION") ? ADVMO_MINIO_REGION : 'us-east-1',
            'credentials' => [
                'key' => defined("ADVMO_MINIO_KEY") ? ADVMO_MINIO_KEY : '',
                'secret' => defined("ADVMO_MINIO_SECRET") ? ADVMO_MINIO_SECRET : '',
            ],
            'use_path_style_endpoint' => defined("ADVMO_MINIO_PATH_STYLE_ENDPOINT") ? ADVMO_MINIO_PATH_STYLE_ENDPOINT : false,
            'retries' => 1
        ]);
    }

    public function getBucket()
    {
        return defined("ADVMO_MINIO_BUCKET") ? ADVMO_MINIO_BUCKET : null;
    }

    public function getDomain()
    {
        return defined('ADVMO_MINIO_DOMAIN') ? trailingslashit(ADVMO_MINIO_DOMAIN) . trailingslashit(ADVMO_MINIO_BUCKET) : '';
    }

    public function credentialsField()
    {
        $requiredConstants = [
            'ADVMO_MINIO_KEY' => 'Your MinIO Access Key',
            'ADVMO_MINIO_SECRET' => 'Your MinIO Secret Key',
            'ADVMO_MINIO_ENDPOINT' => 'Your MinIO S3 Endpoint URL',
            'ADVMO_MINIO_BUCKET' => 'Your MinIO Bucket Name',
            'ADVMO_MINIO_DOMAIN' => 'Your Custom Domain',
            'ADVMO_MINIO_PATH_STYLE_ENDPOINT' => true,
        ];

        echo $this->getCredentialsFieldHTML($requiredConstants);
    }
}
