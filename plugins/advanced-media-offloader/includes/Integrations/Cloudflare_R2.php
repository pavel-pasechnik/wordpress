<?php

namespace Advanced_Media_Offloader\Integrations;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use WPFitter\Aws\S3\S3Client;

class Cloudflare_R2 extends S3_Provider
{
	public $providerName = "Cloudflare R2";

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
			'endpoint' => defined("ADVMO_CLOUDFLARE_R2_ENDPOINT") ? ADVMO_CLOUDFLARE_R2_ENDPOINT : '',
			'region' => defined("ADVMO_CLOUDFLARE_R2_REGION") ? ADVMO_CLOUDFLARE_R2_REGION : 'us-east-1',
			'credentials' => [
				'key' => defined("ADVMO_CLOUDFLARE_R2_KEY") ? ADVMO_CLOUDFLARE_R2_KEY : '',
				'secret' => defined("ADVMO_CLOUDFLARE_R2_SECRET") ? ADVMO_CLOUDFLARE_R2_SECRET : '',
			]
		]);
	}

	public function getBucket()
	{
		return defined("ADVMO_CLOUDFLARE_R2_BUCKET") ? ADVMO_CLOUDFLARE_R2_BUCKET : null;
	}

	public function getDomain()
	{
		return defined('ADVMO_CLOUDFLARE_R2_DOMAIN') ? trailingslashit(ADVMO_CLOUDFLARE_R2_DOMAIN) : '';
	}

	public function credentialsField()
	{
		$requiredConstants = [
			'ADVMO_CLOUDFLARE_R2_KEY' => 'Your Cloudflare R2 Access Key',
			'ADVMO_CLOUDFLARE_R2_SECRET' => 'Your Cloudflare R2 Secret Key',
			'ADVMO_CLOUDFLARE_R2_ENDPOINT' => 'Your Cloudflare R2 Endpoint URL',
			'ADVMO_CLOUDFLARE_R2_BUCKET' => 'Your Cloudflare R2 Bucket Name',
			'ADVMO_CLOUDFLARE_R2_DOMAIN' => 'Your Custom Domain',
		];

		echo $this->getCredentialsFieldHTML($requiredConstants);
	}
}
