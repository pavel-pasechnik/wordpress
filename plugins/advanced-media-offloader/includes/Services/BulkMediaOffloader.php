<?php

namespace Advanced_Media_Offloader\Services;

use Advanced_Media_Offloader\Services\CloudAttachmentUploader;
use Advanced_Media_Offloader\Abstracts\S3_Provider;
use Advanced_Media_Offloader\Abstracts\WP_Background_Processing\WP_Background_Process;

class BulkMediaOffloader extends WP_Background_Process
{
    protected $prefix = 'advmo';
    protected $action = 'bulk_offload_media_process';

    private CloudAttachmentUploader $cloudAttachmentUploader;

    public function __construct(S3_Provider $cloudProvider)
    {
        parent::__construct();
        $this->cloudAttachmentUploader = new CloudAttachmentUploader($cloudProvider);
    }

    protected function task($item)
    {
        try {
            // Log the start of processing
            error_log("ADVMO: Processing attachment ID {$item}");

            // $item is the attachment ID
            $result = $this->cloudAttachmentUploader->uploadAttachment($item);

            // Log the result
            error_log("ADVMO: Processed attachment ID {$item}, result: " . ($result ? 'success' : 'failed'));

            // Update the processed count
            $this->update_processed_count($result);

            return false;
        } catch (\Exception $e) {
            // Log the error
            error_log("ADVMO Bulk Offload Error (ID: {$item}): " . $e->getMessage());

            // Mark as processed with error
            $this->update_processed_count(false);

            // Add error to attachment meta
            update_post_meta($item, 'advmo_error_log', $e->getMessage());

            return false; // Move on to the next item rather than retrying
        }
    }

    protected function complete()
    {
        parent::complete();
        advmo_update_bulk_offload_data(['status' => 'completed']);
    }

    public function update_processed_count($result_status)
    {
        $bulk_offload_data = advmo_get_bulk_offload_data();
        $processed_count = $bulk_offload_data['processed'];
        $processed_count++;
        $errors = $bulk_offload_data['errors'] ?? 0;

        if ($result_status !== true) {
            $errors++;
        }

        advmo_update_bulk_offload_data([
            'processed' => $processed_count,
            'total' => $bulk_offload_data['total'],
            'status' => $bulk_offload_data['status'],
            'errors' => $errors,
        ]);
    }

    public function get_identifier()
    {
        return $this->identifier;
    }
}
