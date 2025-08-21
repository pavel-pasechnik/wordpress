<?php

namespace Advanced_Media_Offloader\Observers;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use Advanced_Media_Offloader\Interfaces\ObserverInterface;
use Advanced_Media_Offloader\Traits\OffloaderTrait;

class ImageSrcsetMetaObserver implements ObserverInterface
{
    use OffloaderTrait;

    /**
     * @var S3_Provider
     */
    private S3_Provider $cloudProvider;

    /**
     * The base URL for uploads.
     *
     * @var string
     */
    private string $upload_base_url;

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
        add_filter('wp_calculate_image_srcset_meta', [$this, 'run'], 1000, 4);
    }

    /**
     * Calculates the image source set metadata by appending the object version to the file names of the image sizes if enabled.
     *
     * @param array $image_meta The metadata of the image.
     * @param array $size_array The array of sizes for the image.
     * @param string $image_src The source URL of the image.
     * @param int $attachment_id The ID of the attachment.
     * @return array The modified image metadata with updated sizes.
     */
    public function run($image_meta, $size_array, $image_src, $attachment_id)
    {

        if (!$this->is_offloaded($attachment_id)) {
            return $image_meta;
        }

        $object_version = $this->get_object_version($attachment_id);

        // Check if ['sizes] is set and is an array. Bug reported by a user
        $image_sizes = isset($image_meta['sizes']) && is_array($image_meta['sizes']) ? $image_meta['sizes'] : [];

        $image_sizes = array_map(function ($size) use ($object_version) {
            $size['file'] = $object_version . $size['file'];
            return $size;
        }, $image_sizes);

        $image_meta['sizes'] = $image_sizes;
        return $image_meta;
    }
}
