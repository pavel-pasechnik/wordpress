<?php

namespace Advanced_Media_Offloader\Services;

use Advanced_Media_Offloader\Abstracts\S3_Provider;
use Advanced_Media_Offloader\Traits\OffloaderTrait;

class CloudAttachmentUploader
{
    use OffloaderTrait;

    private S3_Provider $cloudProvider;

    public function __construct(S3_Provider $cloudProvider)
    {
        $this->cloudProvider = $cloudProvider;
    }

    public function uploadAttachment(int $attachment_id): bool
    {
        if ($this->is_offloaded($attachment_id)) {
            return true;
        }

        if ($this->uploadToCloud($attachment_id)) {
            $this->updateAttachmentMetadata($attachment_id);
            return true;
        }

        return false;
    }

    public function uploadUpdatedAttachment(int $attachment_id, array $metadata): bool
    {
        if ($metadata) {
            $file = get_attached_file($attachment_id);
            $subdir = $this->get_attachment_subdir($attachment_id);
            $uploadResult = $this->cloudProvider->uploadFile($file, $subdir . wp_basename($file));

            if (!$uploadResult) {
                $this->logError($attachment_id, 'Failed to upload resized main file to cloud storage.');
                return false;
            }

            if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
                $metadata_sizes = $this->uniqueMetaDataSizes($metadata['sizes']);
                foreach ($metadata_sizes as $size => $data) {
                    $pattern = '/\-e[0-9]+(?=\-)/';
                    if (!preg_match($pattern, $data['file'])) {
                        error_log("{$data['file']} is not a valid size file name.");
                        continue;
                    }
                    $file = get_attached_file($attachment_id, true);
                    $file = str_replace(wp_basename($file), $data['file'], $file);
                    $uploadResult = $this->cloudProvider->uploadFile($file, $subdir . wp_basename($file));
                    if (!$uploadResult) {
                        $this->logError($attachment_id, "Failed to upload size '{$size}' to cloud storage.");
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function uploadToCloud(int $attachment_id): bool
    {
        /**
         * Fires before the attachment is uploaded to the cloud.
         *
         * This action allows developers to perform tasks or logging before
         * the attachment is uploaded to the cloud.
         *
         * @param int $attachment_id
         */
        do_action('advmo_before_upload_to_cloud', $attachment_id);

        # remove error logs related to the attachment before starting the new upload process
        delete_post_meta($attachment_id, 'advmo_error_log');

        if (!$this->attachment_exists_on_disk($attachment_id)) {
            return false;
        }

        $file = get_attached_file($attachment_id);
        $subdir = $this->get_attachment_subdir($attachment_id);
        $uploadResult = $this->cloudProvider->uploadFile($file, $subdir . wp_basename($file));

        if (!$uploadResult) {
            $this->logError($attachment_id, 'Failed to upload main file to cloud storage.');
            return false;
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            $metadata_sizes = $this->uniqueMetaDataSizes($metadata['sizes']);
            foreach ($metadata_sizes as $size => $data) {
                $file = get_attached_file($attachment_id, true);
                $file = str_replace(wp_basename($file), $data['file'], $file);
                $uploadResult = $this->cloudProvider->uploadFile($file, $subdir . wp_basename($file));
                if (!$uploadResult) {
                    $this->logError($attachment_id, "Failed to upload size '{$size}' to cloud storage.");
                    return false;
                }
            }
        }

        $deleteLocalRule = $this->shouldDeleteLocal();
        if ($deleteLocalRule !== 0) {
            $this->deleteLocalFile($attachment_id, $deleteLocalRule);
        }

        /**
         * Fires after the attachment has been uploaded to the cloud.
         *
         * This action allows developers to perform additional tasks or logging after
         * the attachment has been uploaded to the cloud.
         *
         * @param int $attachment_id    The ID of the attachment that was processed.
         */
        do_action('advmo_after_upload_to_cloud', $attachment_id);

        return true;
    }

    private function logError(int $attachment_id, string $specificError): void
    {
        $generalError = $specificError . ' Please review your Cloud provider credentials or connection settings. For more details, enable debug.log and check the logs.';

        $errorLog = get_post_meta($attachment_id, 'advmo_error_log', true);
        if (!is_array($errorLog)) {
            $errorLog = array();
        }

        $errorLog[] = $generalError;

        update_post_meta($attachment_id, 'advmo_error_log', $errorLog);
        update_post_meta($attachment_id, 'advmo_offloaded', false);
    }

    private function updateAttachmentMetadata(int $attachment_id): void
    {
        update_post_meta($attachment_id, 'advmo_path', $this->get_attachment_subdir($attachment_id));
        update_post_meta($attachment_id, 'advmo_offloaded', true);
        update_post_meta($attachment_id, 'advmo_offloaded_at', time());
        update_post_meta($attachment_id, 'advmo_provider', $this->cloudProvider->getProviderName());
        update_post_meta($attachment_id, 'advmo_bucket', $this->cloudProvider->getBucket());
    }

    private function deleteLocalFile(int $attachment_id, int $deleteLocalRule): bool
    {
        /**
         * Fires before the local file(s) associated with an attachment are deleted.
         *
         * This action allows developers to perform tasks or logging before
         * the local files are removed following a successful cloud upload.
         *
         * @param int $attachment_id    The ID of the attachment to be processed.
         * @param int $deleteLocalRule  The rule to be applied for local file deletion:
         *                              1 - Delete only sized images, keep original.
         *                              2 - Delete all local files including the original.
         */
        do_action('advmo_before_delete_local_file', $attachment_id, $deleteLocalRule);

        $original_file = get_attached_file($attachment_id, true);

        if (!file_exists($original_file)) {
            error_log("Advanced Media Offloader: Original file not found for deletion: $original_file");
            return false;
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $file_dir = trailingslashit(dirname($original_file));
            foreach ($metadata['sizes'] as $size => $sizeinfo) {
                $sized_file = $file_dir . $sizeinfo['file'];
                if (file_exists($sized_file)) {
                    wp_delete_file($sized_file);
                }
            }
        }

        if ($deleteLocalRule === 2) {
            wp_delete_file($original_file);
        }

        update_post_meta($attachment_id, 'advmo_retention_policy', $deleteLocalRule);

        /**
         * Fires after the local file(s) associated with an attachment have been deleted.
         *
         * This action allows developers to perform additional tasks or logging after
         * the local files have been removed following a successful cloud upload.
         *
         * @param int $attachment_id    The ID of the attachment that was processed.
         * @param int $deleteLocalRule  The rule applied for local file deletion:
         *                              1 - Delete only sized images, keep original.
         *                              2 - Delete all local files including the original.
         */
        do_action('advmo_after_delete_local_file', $attachment_id, $deleteLocalRule);

        return true;
    }

    protected function attachment_exists_on_disk($attachment_id)
    {
        $errors = array();

        // Get the full path to the attachment file
        $file_path = get_attached_file($attachment_id);

        // Check if the main file exists
        if (!file_exists($file_path)) {
            $errors[] = "Main file does not exist: {$file_path}";
        }

        // If it's an image, check all sizes
        if (wp_attachment_is_image($attachment_id)) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            if (!empty($metadata['sizes'])) {
                $upload_dir = wp_upload_dir();
                $base_dir = trailingslashit($upload_dir['basedir']);
                $file_dir = trailingslashit(dirname($file_path));

                foreach ($metadata['sizes'] as $size => $size_info) {
                    $size_file_path = $file_dir . $size_info['file'];
                    if (!file_exists($size_file_path)) {
                        $errors[] = "Size '{$size}' does not exist: {$size_file_path}";
                    }
                }
            }
        }

        // Save errors to post meta
        if (!empty($errors)) {
            $existing_errors = get_post_meta($attachment_id, 'advmo_error_log', true);
            if (!is_array($existing_errors)) {
                $existing_errors = array();
            }
            $updated_errors = array_merge($existing_errors, $errors);
            update_post_meta($attachment_id, 'advmo_error_log', $updated_errors);
        } else {
            // If there are no errors, remove any existing error log
            delete_post_meta($attachment_id, 'advmo_error_log');
        }

        // Return true if no errors, false otherwise
        return empty($errors);
    }
}
