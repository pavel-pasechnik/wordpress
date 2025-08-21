<?php

namespace Advanced_Media_Offloader\Traits;

trait OffloaderTrait
{
    /**
     * Get the path prefix for offloaded files.
     *
     * @return string The sanitized path prefix or an empty string if not active.
     */
    private function get_path_prefix(): string
    {
        $settings = get_option('advmo_settings', []);
        $prefix_active = $settings['path_prefix_active'] ?? false;
        $path_prefix = $settings['path_prefix'] ?? '';

        if (!$prefix_active || empty($path_prefix)) {
            return '';
        }

        return trailingslashit(advmo_sanitize_path($path_prefix));
    }

    private function get_object_version($attachment_id)
    {
        // Check if we already have a version for this attachment
        $existing_version = get_post_meta($attachment_id, 'advmo_object_version', true);
        if ($existing_version) {
            return trailingslashit($existing_version);
        }

        $advmo_settings = get_option('advmo_settings');
        $object_versioning = isset($advmo_settings['object_versioning']) ? $advmo_settings['object_versioning'] : '0';

        // If versioning is not enabled, return an empty string
        if (!$object_versioning) {
            return '';
        }

        // Generate a new version
        if (!advmo_is_media_organized_by_year_month()) {
            $new_version = date("YmdHis");
        } else {
            $new_version = date("dHis");
        }

        // Save the new version in post meta
        update_post_meta($attachment_id, 'advmo_object_version', $new_version);

        return trailingslashit($new_version);
    }

    public function get_attachment_subdir($attachment_id)
    {
        // Check if already offlaoded, return advmo_path 
        if ($this->is_offloaded($attachment_id)) {
            return get_post_meta($attachment_id, 'advmo_path', true);
        }

        $object_version = $this->get_object_version($attachment_id);
        $path_prefix = $this->get_path_prefix();

        $metadata = wp_get_attachment_metadata($attachment_id);
        $file_path = get_attached_file($attachment_id);

        // For images, use the metadata 'file' if available
        if (isset($metadata['file'])) {
            $dirname = advmo_is_media_organized_by_year_month() ? trailingslashit(dirname($metadata['file'])) : "";
            return  $path_prefix . $dirname . $object_version;
        }

        // For non-images, extract the year/month structure from the file path
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];

        // Remove the base upload directory from the file path
        $relative_path = str_replace($base_dir . '/', '', $file_path);

        // Extract the year/month
        $path_parts = explode('/', trim($relative_path, '/'), 3);

        $response = '';
        if (count($path_parts) >= 2 && is_numeric($path_parts[0]) && is_numeric($path_parts[1])) {
            $response = trailingslashit($path_parts[0] . '/' . $path_parts[1]);
        }

        // Fallback: return empty string if we can't determine the structure
        return $path_prefix . $response . $object_version;
    }

    private function uniqueMetaDataSizes($sizes)
    {
        $uniqueSizes = [];
        $dimensionMap = [];

        foreach ($sizes as $name => $sizeInfo) {
            $dimension = $sizeInfo['width'] . 'x' . $sizeInfo['height'];

            if (!isset($dimensionMap[$dimension])) {
                $dimensionMap[$dimension] = $name;
                $uniqueSizes[$name] = $sizeInfo;
            } else {
                // If this size has a larger filesize, replace the existing one
                $existingName = $dimensionMap[$dimension];
                if ($sizeInfo['filesize'] > $uniqueSizes[$existingName]['filesize']) {
                    unset($uniqueSizes[$existingName]);
                    $dimensionMap[$dimension] = $name;
                    $uniqueSizes[$name] = $sizeInfo;
                }
            }
        }

        return $uniqueSizes;
    }

    private function is_offloaded($post_id)
    {
        return (bool)get_post_meta($post_id, 'advmo_offloaded', true);
    }

    private function shouldDeleteLocal()
    {
        $settings = get_option('advmo_settings');
        $retention_policy = isset($settings['retention_policy']) ? $settings['retention_policy'] : '0';

        // Ensure the value is a string and convert it to an integer
        return intval((string)$retention_policy);
    }

    private function shouldDeleteCloudFiles($post)
    {
        $advmo_settings = get_option('advmo_settings');
        $mirror_delete = false;
        if (isset($advmo_settings['mirror_delete'])) {
            $mirror_delete = $advmo_settings['mirror_delete'] == '1';
        }

        return $mirror_delete && $post->post_type === 'attachment' && $this->is_offloaded($post->ID);
    }
}
