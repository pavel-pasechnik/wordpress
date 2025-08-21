<?php

namespace Advanced_Media_Offloader\Integrations;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use WPFitter\Aws\S3\S3Client;

class DigitalOceanSpaces extends S3_Provider
{
	public $providerName = "DigitalOcean Spaces";

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
			'endpoint' => defined("ADVMO_DOS_ENDPOINT") ? ADVMO_DOS_ENDPOINT : '',
			'region' => defined("ADVMO_DOS_REGION") ? ADVMO_DOS_REGION : 'us-east-1',
			'credentials' => [
				'key' => defined("ADVMO_DOS_KEY") ? ADVMO_DOS_KEY : '',
				'secret' => defined("ADVMO_DOS_SECRET") ? ADVMO_DOS_SECRET : '',
			]
		]);
	}

	public function getBucket()
	{
		return defined("ADVMO_DOS_BUCKET") ? ADVMO_DOS_BUCKET : null;
	}

	public function getDomain()
	{
		return defined('ADVMO_DOS_DOMAIN') ? trailingslashit(ADVMO_DOS_DOMAIN) : '';
	}

	public function credentialsField()
	{
		$requiredConstants = [
			'ADVMO_DOS_KEY' => 'Your DigitalOcean Spaces Access Key',
			'ADVMO_DOS_SECRET' => 'Your DigitalOcean Spaces Secret Key',
			'ADVMO_DOS_ENDPOINT' => 'Your DigitalOcean Spaces Endpoint URL',
			'ADVMO_DOS_BUCKET' => 'Your DigitalOcean Spaces Bucket Name',
			'ADVMO_DOS_DOMAIN' => 'Your Custom Domain',
		];

		echo $this->getCredentialsFieldHTML($requiredConstants);
	}
}
