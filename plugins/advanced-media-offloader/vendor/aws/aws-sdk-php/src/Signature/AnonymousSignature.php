<?php

namespace WPFitter\Aws\Signature;

use WPFitter\Aws\Credentials\CredentialsInterface;
use WPFitter\Psr\Http\Message\RequestInterface;
/**
 * Provides anonymous client access (does not sign requests).
 * @internal
 */
class AnonymousSignature implements SignatureInterface
{
    /**
     * /** {@inheritdoc}
     */
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        return $request;
    }
    /**
     * /** {@inheritdoc}
     */
    public function presign(RequestInterface $request, CredentialsInterface $credentials, $expires, array $options = [])
    {
        return $request;
    }
}
