<?php

namespace Advanced_Media_Offloader\Observers;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use Advanced_Media_Offloader\Interfaces\ObserverInterface;
use Advanced_Media_Offloader\Services\CloudAttachmentUploader;

class AttachmentUpdateObserver implements ObserverInterface
{
    /**
     * @var S3_Provider
     */
    private S3_Provider $cloudProvider;

    private CloudAttachmentUploader $cloudAttachmentUploader;

    public function __construct(S3_Provider $cloudProvider)
    {
        $this->cloudProvider = $cloudProvider;
        $this->cloudAttachmentUploader = new CloudAttachmentUploader($cloudProvider);
    }

    public function register(): void
    {
        add_filter('wp_update_attachment_metadata', [$this, 'run'], 99, 2);
    }

    public function run($metadata, $attachment_id)
    {
        // PHPCS ignore reason: Update the attachment's metadata by either restoring or editing it.
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $element) {
            switch ($element['function']) {
                case 'wp_save_image':
                    // Right after an image has been edited.
                    $this->cloudAttachmentUploader->uploadUpdatedAttachment($attachment_id, $metadata);
                    break;
                case 'wp_restore_image':
                    // When an image has been restored.
                    break;
            }
        }

        return $metadata;
    }
}
