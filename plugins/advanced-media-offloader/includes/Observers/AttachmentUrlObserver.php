<?php

namespace Advanced_Media_Offloader\Observers;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use Advanced_Media_Offloader\Interfaces\ObserverInterface;
use Advanced_Media_Offloader\Traits\OffloaderTrait;

class AttachmentUrlObserver implements ObserverInterface
{
    use OffloaderTrait;

    /**
     * @var S3_Provider
     */
    private S3_Provider $cloudProvider;

    /**
     * Constructor.
     *
     * @param S3_Provider $cloudProvider
     */
    public function __construct(S3_Provider $cloudProvider)
    {
        $this->cloudProvider = $cloudProvider;
    }

    /**
     * Register the observer with WordPress hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_filter('wp_get_attachment_url', [$this, 'run'], 10, 2);
    }

    /**
     * Modify the attachment URL if the media is offloaded.
     *
     * @param string $url      The original URL.
     * @param int    $post_id  The attachment ID.
     * @return string          The modified URL.
     */
    public function run(string $url, int $post_id): string
    {
        if ($this->is_offloaded($post_id)) {
            $subDir = $this->get_attachment_subdir($post_id);
            $url = rtrim($this->cloudProvider->getDomain(), '/') . '/' . ltrim($subDir, '/') . basename($url);
        }
        return $url;
    }
}
