<?php

namespace Advanced_Media_Offloader\CLI;

use Advanced_Media_Offloader\Services\CloudAttachmentUploader;
use Advanced_Media_Offloader\Factories\CloudProviderFactory;
use Advanced_Media_Offloader\Traits\OffloaderTrait;

/**
 * WP CLI command for media offloading operations.
 */
class OffloadCommand
{
    use OffloaderTrait;

    /**
     * Cloud attachment uploader instance.
     *
     * @var CloudAttachmentUploader
     */
    private $uploader;

    /**
     * Constructor.
     */
    public function __construct()
    {
        try {
            $cloud_provider_key = advmo_get_cloud_provider_key();
            if (empty($cloud_provider_key)) {
                \WP_CLI::error('No cloud provider configured. Please configure your cloud storage settings first.');
            }

            $cloud_provider = CloudProviderFactory::create($cloud_provider_key);
            $this->uploader = new CloudAttachmentUploader($cloud_provider);
        } catch (\Exception $e) {
            \WP_CLI::error('Failed to initialize cloud provider: ' . $e->getMessage());
        }
    }

    /**
     * Offload media attachments to cloud storage.
     *
     * This command allows you to offload WordPress media attachments to your configured
     * cloud storage provider (S3, Cloudflare R2, DigitalOcean Spaces, MinIO, or Wasabi).
     * 
     * ## OPTIONS
     *
     * [<attachment_ids>]
     * : Attachment ID(s) to offload. Can be a single ID or comma-separated list.
     * ---
     * examples:
     *   - 123 (single attachment)
     *   - 123,456,789 (multiple attachments)
     * ---
     *
     * [--limit=<number>]
     * : Maximum number of attachments to process. Only applies when no specific IDs are provided.
     * Use this to process media in smaller batches to avoid timeouts or memory issues.
     * ---
     * default: 0 (no limit)
     * examples:
     *   - 50 (process up to 50 files)
     *   - 100 (process up to 100 files)
     * ---
     *
     * [--skip-failed]
     * : Skip attachments that have previously failed offloading. Useful for processing
     * only clean attachments without retrying known problematic files.
     *
     * ## EXAMPLES
     *
     *     # Offload all unoffloaded media attachments
     *     wp advmo offload
     *
     *     # Offload a specific attachment by ID
     *     wp advmo offload 123
     *
     *     # Offload multiple specific attachments
     *     wp advmo offload 123,456,789
     *
     *     # Offload up to 100 most recent attachments
     *     wp advmo offload --limit=100
     *
     *     # Offload all attachments, skipping any with previous errors
     *     wp advmo offload --skip-failed
     *
     *     # Combine flags: offload up to 50 attachments, skip failed ones
     *     wp advmo offload --limit=50 --skip-failed
     *
     *     # Retry specific failed attachments
     *     wp advmo offload 123,456 
     *
     * ## WHAT THIS COMMAND DOES
     *
     * 1. **Validates** cloud storage configuration
     * 2. **Identifies** eligible attachments based on your criteria
     * 3. **Uploads** original files and all image sizes to cloud storage
     * 4. **Updates** attachment metadata to mark as offloaded
     * 5. **Applies** retention policy (keeps/removes local files as configured)
     * 6. **Reports** detailed success/failure statistics
     *
     * ## BEFORE RUNNING
     *
     * - Ensure your cloud storage provider is properly configured
     * - Test with a small batch first: `wp advmo offload --limit=5`
     * - Consider running during low-traffic periods for large batches
     * - Check available disk space if using "retain local files" policy
     *
     * ## OUTPUT INFORMATION
     *
     * The command provides real-time progress indicators and detailed results:
     * 
     * - **Progress**: "Processing 1/100 attachments..."
     * - **Success**: "Successfully offloaded 95 attachment(s)"
     * - **Warnings**: "Attachment ID 999 not found, skipping"
     * - **Errors**: "Failed to offload attachment ID 123: [error details]"
     * - **Summary**: "95 successful, 3 failed, 2 skipped out of 100 total"
     *
     * ## TROUBLESHOOTING
     *
     * If attachments fail to offload:
     * 1. Check error logs in WordPress admin > Media Offloader > Media Overview
     * 2. Verify cloud storage credentials and permissions
     * 3. Ensure files exist on disk and are readable
     * 4. Try offloading individual files to isolate issues
     * 5. Check server memory/timeout limits for large batches
     *
     * @param array $args Positional arguments.
     * @param array $assoc_args Associative arguments (flags).
     */
    public function __invoke($args, $assoc_args)
    {
        $limit = isset($assoc_args['limit']) ? (int) $assoc_args['limit'] : 0;
        $skip_failed = isset($assoc_args['skip-failed']);

        // Parse attachment IDs from arguments
        $attachment_ids = $this->parseAttachmentIds($args);

        if (!empty($attachment_ids)) {
            $this->offloadSpecificAttachments($attachment_ids, $skip_failed);
        } else {
            $this->offloadAllAttachments($limit, $skip_failed);
        }
    }

    /**
     * Parse attachment IDs from command arguments.
     *
     * @param array $args Command arguments.
     * @return array Array of attachment IDs or empty array.
     */
    private function parseAttachmentIds($args)
    {
        if (empty($args[0])) {
            return [];
        }

        $ids_string = $args[0];

        // Handle comma-separated list
        if (strpos($ids_string, ',') !== false) {
            $ids = explode(',', $ids_string);
            return array_map('intval', array_filter(array_map('trim', $ids)));
        }

        // Handle single ID
        $id = intval($ids_string);
        return $id > 0 ? [$id] : [];
    }

    /**
     * Offload specific attachments by ID.
     *
     * @param array $attachment_ids Array of attachment IDs.
     * @param bool $skip_failed Whether to skip failed attachments.
     */
    private function offloadSpecificAttachments($attachment_ids, $skip_failed)
    {
        $total = count($attachment_ids);

        \WP_CLI::log("Processing {$total} specific attachment(s)...");

        $successful = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($attachment_ids as $index => $attachment_id) {
            \WP_CLI::log("Processing attachment ID {$attachment_id}...");

            // Validate attachment exists
            if (!$this->attachmentExists($attachment_id)) {
                \WP_CLI::warning("Attachment ID {$attachment_id} not found, skipping.");
                $skipped++;
                continue;
            }

            // Skip if already offloaded
            if ($this->is_offloaded($attachment_id)) {
                \WP_CLI::log("Attachment ID {$attachment_id} already offloaded, skipping.");
                $skipped++;
                continue;
            }

            // Skip failed if flag is set
            if ($skip_failed && $this->hasErrors($attachment_id)) {
                \WP_CLI::log("Attachment ID {$attachment_id} has previous errors, skipping.");
                $skipped++;
                continue;
            }

            // Attempt offload
            if ($this->processAttachment($attachment_id)) {
                \WP_CLI::success("Successfully offloaded attachment ID {$attachment_id}");
                $successful++;
            } else {
                $failed++;
                $errors = get_post_meta($attachment_id, 'advmo_error_log', true);
                $error_message = is_array($errors) ? implode('; ', $errors) : $errors;
                \WP_CLI::error("Failed to offload attachment ID {$attachment_id}: {$error_message}", false);
            }
        }

        $this->displayResults($successful, $failed, $skipped, $total);
    }

    /**
     * Offload all eligible attachments.
     *
     * @param int $limit Maximum number of attachments to process.
     * @param bool $skip_failed Whether to skip failed attachments.
     */
    private function offloadAllAttachments($limit, $skip_failed)
    {
        $attachment_ids = $this->getEligibleAttachments($limit, $skip_failed);
        $total = count($attachment_ids);

        if ($total === 0) {
            \WP_CLI::success('No eligible attachments found for offloading.');
            return;
        }

        \WP_CLI::log("Found {$total} eligible attachment(s) for offloading...");

        $successful = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($attachment_ids as $index => $attachment_id) {
            $current = $index + 1;
            \WP_CLI::log("Processing attachment ID {$attachment_id} ({$current}/{$total})...");

            // Double-check eligibility (in case of concurrent modifications)
            if ($this->is_offloaded($attachment_id)) {
                \WP_CLI::log("Attachment ID {$attachment_id} already offloaded, skipping.");
                $skipped++;
                continue;
            }

            if ($this->processAttachment($attachment_id)) {
                \WP_CLI::success("Successfully offloaded attachment ID {$attachment_id}");
                $successful++;
            } else {
                $failed++;
                $errors = get_post_meta($attachment_id, 'advmo_error_log', true);
                $error_message = is_array($errors) ? implode('; ', $errors) : $errors;
                \WP_CLI::error("Failed to offload attachment ID {$attachment_id}: {$error_message}", false);
            }
        }

        $this->displayResults($successful, $failed, $skipped, $total);
    }

    /**
     * Get eligible attachments for offloading.
     *
     * @param int $limit Maximum number to retrieve.
     * @param bool $skip_failed Whether to skip failed attachments.
     * @return array Array of attachment IDs.
     */
    private function getEligibleAttachments($limit, $skip_failed)
    {
        global $wpdb;

        $limit_clause = $limit > 0 ? "LIMIT {$limit}" : '';

        if ($skip_failed) {
            // Exclude attachments with errors
            $query = $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'advmo_offloaded'
                LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'advmo_error_log'
                WHERE p.post_type = 'attachment' 
                AND (pm1.meta_value IS NULL OR pm1.meta_value = '') 
                AND pm2.meta_id IS NULL
                ORDER BY p.post_date ASC
                {$limit_clause}"
            );
        } else {
            // Include all non-offloaded attachments
            $query = $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'advmo_offloaded'
                WHERE p.post_type = 'attachment' 
                AND (pm.meta_value IS NULL OR pm.meta_value = '')
                ORDER BY p.post_date ASC
                {$limit_clause}"
            );
        }

        return $wpdb->get_col($query);
    }

    /**
     * Process a single attachment for offloading.
     *
     * @param int $attachment_id Attachment ID.
     * @return bool True if successful, false otherwise.
     */
    private function processAttachment($attachment_id)
    {
        try {
            return $this->uploader->uploadAttachment($attachment_id);
        } catch (\Exception $e) {
            // Log the error to attachment meta
            update_post_meta($attachment_id, 'advmo_error_log', $e->getMessage());
            return false;
        }
    }

    /**
     * Check if an attachment exists.
     *
     * @param int $attachment_id Attachment ID.
     * @return bool True if exists, false otherwise.
     */
    private function attachmentExists($attachment_id)
    {
        $post = get_post($attachment_id);
        return $post && $post->post_type === 'attachment';
    }

    /**
     * Check if attachment has errors.
     *
     * @param int $attachment_id Attachment ID.
     * @return bool True if has errors, false otherwise.
     */
    private function hasErrors($attachment_id)
    {
        $errors = get_post_meta($attachment_id, 'advmo_error_log', true);
        return !empty($errors);
    }

    /**
     * Display operation results.
     *
     * @param int $successful Number of successful operations.
     * @param int $failed Number of failed operations.
     * @param int $skipped Number of skipped operations.
     * @param int $total Total number of operations attempted.
     */
    private function displayResults($successful, $failed, $skipped, $total)
    {
        if ($successful > 0) {
            \WP_CLI::success("Successfully offloaded {$successful} attachment(s).");
        }

        if ($failed > 0) {
            \WP_CLI::warning("{$failed} attachment(s) failed to offload.");
        }

        if ($skipped > 0) {
            \WP_CLI::log("{$skipped} attachment(s) were skipped.");
        }

        \WP_CLI::log("Summary: {$successful} successful, {$failed} failed, {$skipped} skipped out of {$total} total.");

        if ($failed > 0) {
            \WP_CLI::log('Check attachment error logs or use the Media Overview page for detailed error information.');
        }
    }
}
