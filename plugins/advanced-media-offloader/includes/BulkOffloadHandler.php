<?php

namespace Advanced_Media_Offloader;

use Advanced_Media_Offloader\Services\BulkMediaOffloader;
use Advanced_Media_Offloader\Factories\CloudProviderFactory;

class BulkOffloadHandler
{
    protected $process_all;

    # singleton
    private static $instance = null;

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('wp_ajax_advmo_check_bulk_offload_progress', array($this, 'get_progress'));
        add_action('wp_ajax_advmo_start_bulk_offload', array($this, 'bulk_offload'));
        add_action('wp_ajax_advmo_cancel_bulk_offload', array($this, 'cancel_bulk_offload'));
    }

    /**
     * Init
     */
    public function init()
    {
        try {
            $cloud_provider_key = advmo_get_cloud_provider_key();
            $cloud_provider = CloudProviderFactory::create($cloud_provider_key);
            $this->process_all    = new BulkMediaOffloader($cloud_provider);
            add_action($this->process_all->get_identifier() . '_cancelled', array($this, 'process_is_cancelled'));

            // Check for stalled processes every 15 minutes
            add_filter('cron_schedules', [$this, 'add_cron_interval']);
            add_action('advmo_check_stalled_processes', [$this, 'check_stalled_processes']);

            if (!wp_next_scheduled('advmo_check_stalled_processes')) {
                wp_schedule_event(time(), 'advmo_fifteen_min', 'advmo_check_stalled_processes');
            }
        } catch (\Exception $e) {
            error_log('ADVMO - Error: ' . $e->getMessage());
        }
    }


    public function add_cron_interval($schedules)
    {
        $schedules['advmo_fifteen_min'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 minutes', 'advanced-media-offloader')
        ];
        return $schedules;
    }

    public function check_stalled_processes()
    {
        // Check if a process is locked but not updating
        $process_lock = get_site_transient($this->process_all->get_identifier() . '_process_lock');
        $last_update = get_option('advmo_bulk_offload_last_update', 0);

        // If process locked but hasn't updated in 10 minutes
        if ($process_lock && (time() - $last_update) > 600) {
            // Force unlock and reset
            delete_site_transient($this->process_all->get_identifier() . '_process_lock');
            delete_option('advmo_bulk_offload_cancelled');

            // Update status to ready for restart
            advmo_update_bulk_offload_data([
                'status' => 'ready',
            ]);

            error_log('ADVMO: Detected and unlocked stalled bulk offload process');
        }
    }


    public function bulk_offload()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'advanced-media-offloader')
            ], 403);
        }

        if (!wp_verify_nonce(sanitize_key($_POST['bulk_offload_nonce'] ?? ''), 'advmo_bulk_offload')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'advanced-media-offloader')
            ], 403);
        }

        try {
            $this->handle_all();
            $bulk_offload_data = advmo_get_bulk_offload_data();

            wp_send_json_success([
                'total'     => $bulk_offload_data['total'],
            ]);
        } catch (\Exception $e) {
            error_log('ADVMO Bulk Offload Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function get_progress()
    {
        // Verify nonce
        if (!check_ajax_referer('advmo_bulk_offload', 'bulk_offload_nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'advanced-media-offloader')], 403);
            return;
        }

        // Verify user capabilities
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Permission denied', 'advanced-media-offloader')], 403);
            return;
        }

        $bulk_offload_data = advmo_get_bulk_offload_data();
        $is_bulk_offload_cancelled = get_option("advmo_bulk_offload_cancelled");
        wp_send_json_success([
            'processed' => $bulk_offload_data['processed'],
            'total'     => $bulk_offload_data['total'],
            'status'    => $is_bulk_offload_cancelled ? "cancelled" : $bulk_offload_data['status'],
            'errors'    => $bulk_offload_data['errors'] ?? 0,
            'oversized_skipped' => $bulk_offload_data['oversized_skipped'] ?? 0,
        ]);
    }

    protected function handle_all()
    {
        if (!wp_verify_nonce($_POST['bulk_offload_nonce'], 'advmo_bulk_offload')) {
            wp_send_json_error([
                'message' => __('Invalid nonce', 'advanced-media-offloader')
            ]);
        }

        $names = $this->get_unoffloaded_attachments();

        foreach ($names as $name) {
            $this->process_all->push_to_queue($name);
        }

        $this->process_all->save()->dispatch();
    }

    protected function get_unoffloaded_attachments($batch_size = 50)
    {
        global $wpdb;

        // Max batch size in MB (150MB total per batch)
        $max_batch_size_mb = 150;
        $current_batch_size = 0;
        $filtered_attachments = [];
        $oversized_files = 0;

        // First, get attachments without errors
        $query = $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'advmo_offloaded'
            LEFT JOIN {$wpdb->postmeta} em ON p.ID = em.post_id AND em.meta_key = 'advmo_error_log'
            WHERE p.post_type = 'attachment' 
            AND (pm.meta_value IS NULL OR pm.meta_value = '') 
            AND em.meta_id IS NULL
            ORDER BY p.post_date ASC
            LIMIT %d",
            $batch_size * 2
        );

        $normal_attachments = $wpdb->get_col($query);

        // Process normal attachments first, with size limitations
        foreach ($normal_attachments as $attachment_id) {
            // Skip if we've hit batch size limit
            if (count($filtered_attachments) >= $batch_size) {
                break;
            }

            $file_path = get_attached_file($attachment_id);
            if (file_exists($file_path)) {
                $file_size = filesize($file_path) / (1024 * 1024); // Size in MB

                // Skip massive files entirely (> 10MB)
                if ($file_size > 10) {
                    $error_msg = sprintf(
                        __('File exceeds maximum size (%s MB) for bulk processing', 'advanced-media-offloader'),
                        '10'
                    );
                    update_post_meta($attachment_id, 'advmo_error_log', $error_msg);
                    $oversized_files++;
                    continue;
                }

                // Add to batch if we're under the total size limit
                if (($current_batch_size + $file_size) <= $max_batch_size_mb) {
                    $filtered_attachments[] = $attachment_id;
                    $current_batch_size += $file_size;
                } else {
                    // We've hit the batch size limit
                    break;
                }
            } else {
                // Include files that don't exist on disk (metadata only)
                $filtered_attachments[] = $attachment_id;
            }
        }

        // If we have remaining capacity, try to process some error files
        $remaining_slots = $batch_size - count($filtered_attachments);

        // If there are remaining slots, fill them with attachments that have errors
        if ($remaining_slots > 0) {
            $error_query = $wpdb->prepare(
                "SELECT p.ID 
                FROM {$wpdb->posts} p 
                JOIN {$wpdb->postmeta} pm_error ON (p.ID = pm_error.post_id AND pm_error.meta_key = 'advmo_error_log')
                LEFT JOIN {$wpdb->postmeta} pm_offload ON (p.ID = pm_offload.post_id AND pm_offload.meta_key = 'advmo_offloaded')
                WHERE p.post_type = 'attachment'
                AND (pm_offload.meta_value IS NULL OR pm_offload.meta_value = '')
                AND pm_error.meta_value IS NOT NULL
                AND pm_error.meta_value != ''
                ORDER BY p.post_date ASC
                LIMIT %d",
                $remaining_slots * 2
            );

            $error_attachments = $wpdb->get_col($error_query);

            // Process error attachments with size limitations
            foreach ($error_attachments as $attachment_id) {
                if (count($filtered_attachments) >= $batch_size) {
                    break;
                }

                $file_path = get_attached_file($attachment_id);
                if (file_exists($file_path)) {
                    $file_size = filesize($file_path) / (1024 * 1024); // Size in MB

                    // Skip massive files
                    if ($file_size > 100) {
                        continue;
                    }

                    // Add if under size limit
                    if (($current_batch_size + $file_size) <= $max_batch_size_mb) {
                        $filtered_attachments[] = $attachment_id;
                        $current_batch_size += $file_size;
                    } else {
                        break;
                    }
                } else {
                    // Include non-existent files
                    $filtered_attachments[] = $attachment_id;
                }
            }
        }

        // Update bulk offload data
        $attachment_count = count($filtered_attachments);
        if ($attachment_count > 0) {
            advmo_update_bulk_offload_data(array(
                'total' => $attachment_count,
                'status' => 'processing',
                'processed' => 0,
                'errors' => 0,
                'oversized_skipped' => $oversized_files
            ));
        } else {
            advmo_clear_bulk_offload_data();
        }

        return $filtered_attachments;
    }

    public function cancel_bulk_offload()
    {

        if (!wp_verify_nonce($_POST['bulk_offload_nonce'], 'advmo_bulk_offload')) {
            wp_send_json_error([
                'message' => __('Invalid nonce', 'advanced-media-offloader')
            ]);
        }
        $this->process_all->cancel();

        # lock the bulk offload cancel
        update_option("advmo_bulk_offload_cancelled", true);

        wp_send_json_success([
            "message" => __('Bulk offload cancelled successfully.', 'advanced-media-offloader')
        ]);
    }

    public function process_is_cancelled()
    {
        advmo_update_bulk_offload_data([
            'status' => 'cancelled'
        ]);
        delete_option("advmo_bulk_offload_cancelled");
    }

    public function bulk_offload_cron_healthcheck()
    {
        $this->process_all->handle_cron_healthcheck();
        wp_send_json_success();
    }
}
