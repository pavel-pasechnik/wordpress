<?php

namespace Advanced_Media_Offloader\Observers;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use Advanced_Media_Offloader\Interfaces\ObserverInterface;
use Advanced_Media_Offloader\Traits\OffloaderTrait;

class AttachmentDeleteObserver  implements ObserverInterface
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
        add_action('delete_attachment', [$this, 'run'], 10, 2);
    }

    /**
     * Delete cloud files when an attachment is deleted.
     * @param int    $post_id The ID of the post.
     * @param \WP_Post $post The post object.
     * @return void
     * @see https://developer.wordpress.org/reference/hooks/delete_attachment/
     */
    public function run(int $post_id, \WP_Post $post): void
    {
        if ($this->shouldDeleteCloudFiles($post)) {
            $this->performCloudFileDeletion($post_id);
        }
    }

    /**
     * Perform the actual deletion of cloud files.
     *
     * @param int $post_id The ID of the post.
     * @return void
     */
    private function performCloudFileDeletion(int $post_id): void
    {
        try {
            $result = $this->cloudProvider->deleteAttachment($post_id);
            if (!$result) {
                throw new \Exception("Cloud file deletion failed");
            }
        } catch (\Exception $e) {
            $this->handleDeletionError($post_id, $e->getMessage());
        }
    }

    /**
     * Handle errors during cloud file deletion.
     *
     * @param int    $post_id The ID of the post.
     * @param string $error_message The error message.
     * @return void
     */
    private function handleDeletionError(int $post_id, string $error_message): void
    {
        $log_message = "Cloud file deletion failed for attachment ID: {$post_id}. " .
            "The file remains in the cloud storage and locally due to an error. " .
            "Please try again or contact support if the issue persists.";

        error_log($log_message);

        // Add a notice to the dashboard
        add_action('admin_notices', function () use ($error_message) {
            echo '<div class="error"><p>' . esc_html($error_message) . '</p></div>';
        });

        wp_die('Error deleting file from cloud provider: ' . esc_html($error_message));
    }
}
