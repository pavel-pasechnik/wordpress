<?php

namespace Advanced_Media_Offloader\Factories;

use Advanced_Media_Offloader\Integrations\AmazonS3;
use Advanced_Media_Offloader\Integrations\Cloudflare_R2;
use Advanced_Media_Offloader\Integrations\DigitalOceanSpaces;
use Advanced_Media_Offloader\Integrations\MinIO;
use Advanced_Media_Offloader\Integrations\Wasabi;
use Advanced_Media_Offloader\Abstracts\S3_Provider;
use Exception;

class CloudProviderFactory
{
    /**
     * List of available cloud providers.
     *
     * @var array
     */
    private static array $cloud_providers = [
        'aws_s3'        => [
            'name'  => 'Amazon S3',
            'class' => AmazonS3::class,
        ],
        'digitalocean'  => [
            'name'  => 'DigitalOcean Spaces',
            'class' => DigitalOceanSpaces::class,
        ],
        'minio'         => [
            'name'  => 'MinIO',
            'class' => MinIO::class,
        ],
        'cloudflare_r2' => [
            'name'  => 'Cloudflare R2',
            'class' => Cloudflare_R2::class,
        ],
        'wasabi'        => [
            'name'  => 'Wasabi',
            'class' => Wasabi::class,
        ],
    ];

    /**
     * Create an instance of the selected cloud provider.
     *
     * @param string $provider_key The key representing the cloud provider.
     * @return S3_Provider An instance of the cloud provider class.
     * @throws Exception If the provider key is unsupported.
     */
    public static function create(string $provider_key): S3_Provider
    {
        if (!isset(self::$cloud_providers[$provider_key])) {
            throw new Exception("Unsupported cloud provider: {$provider_key}");
        }

        $provider_class = self::$cloud_providers[$provider_key]['class'];

        if (!class_exists($provider_class)) {
            throw new Exception("Cloud provider class does not exist: {$provider_class}");
        }

        return new $provider_class();
    }

    /**
     * Get the list of available cloud providers.
     *
     * @return array The list of cloud providers with keys and names.
     */
    public static function getAvailableProviders(): array
    {
        return self::$cloud_providers;
    }
}
