<?php

namespace Advanced_Media_Offloader\Admin;

use \Exception;

class MediaOverview
{
    private static $instance = null;

    private function __construct()
    {
        $this->register();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function register()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'initialize']);
        add_action('wp_ajax_advmo_download_errors_csv', array($this, 'handle_download_errors_csv'));
    }

    public function initialize()
    {
        $this->add_media_overview_fields();
        $this->add_media_overview_sections();
    }

    public function add_menu()
    {
        add_submenu_page(
            'advmo',
            __('Media Overview', 'advanced-media-offloader'),
            __('Media Overview', 'advanced-media-offloader'),
            'manage_options',
            'advmo_media_overview',
            [$this, 'media_overview_page_view']
        );
    }

    public function media_overview_page_view()
    {
        advmo_get_view('admin/media_overview');
    }

    private function add_media_overview_sections()
    {
        add_settings_section(
            'media_overview',
            __('Media Library Overview', 'advanced-media-offloader'),
            function () {
                echo '<p>' . esc_attr__('Get a comprehensive overview of all media files in your WordPress library. Easily identify any files that haven’t been offloaded to the Cloud and offload them in bulk.', 'advanced-media-offloader') . '</p></div>';
            },
            'advmo_media_overview',
            [
                'before_section' => '<div class="advmo-section advmo-media-overview"><div class="advmo-section-header">',
                'after_section' => '</div>',
            ]
        );

        add_settings_section(
            'media_bulk_offload',
            __('Bulk Offload', 'advanced-media-offloader'),
            function () {
                echo '<p>' . esc_html__('Bulk offload your unoffloaded media files to cloud storage. This process frees up your server storage and boosts your website\'s performance in one go!', 'advanced-media-offloader') . '</p></div>';
            },
            'advmo_media_overview',
            [
                'before_section' => '<div class="advmo-section advmo-media-overview"><div class="advmo-section-header">',
                'after_section' => '</div>',
            ]
        );
    }

    private function add_media_overview_fields()
    {
        add_settings_field(
            'total_media_files',
            __('Total Media Files', 'advanced-media-offloader'),
            [$this, 'total_media_files_field'],
            'advmo_media_overview',
            'media_overview',
            [
                'class' => 'advmo-field advmo-non-offloaded-media',
            ]
        );

        add_settings_field(
            'offloaded_media',
            __('Offloaded Media', 'advanced-media-offloader'),
            [$this, 'offloaded_media_field'],
            'advmo_media_overview',
            'media_overview',
            [
                'class' => 'advmo-field advmo-non-offloaded-media',
            ]
        );

        add_settings_field(
            'non_offloaded_media',
            __('Non-Offloaded Media', 'advanced-media-offloader'),
            [$this, 'non_offloaded_media_field'],
            'advmo_media_overview',
            'media_overview',
            [
                'class' => 'advmo-field advmo-non-offloaded-media',
            ]
        );

        add_settings_field(
            'offload_errors',
            __('Offload Errors', 'advanced-media-offloader'),
            [$this, 'offload_errors_field'],
            'advmo_media_overview',
            'media_overview',
            [
                'class' => 'advmo-field advmo-non-offloaded-media',
            ]
        );

        add_settings_field(
            'bulk_offload_media',
            __('Bulk Offload Existing Media', 'advanced-media-offloader'),
            [$this, 'bulk_offload_media_field'],
            'advmo_media_overview',
            'media_bulk_offload',
            [
                'class' => 'advmo-field advmo-bulk-offload-media',
            ]
        );
    }

    public function total_media_files_field()
    {
        $total_attachments = wp_count_attachments();
        $total_count = array_sum((array) $total_attachments);

        echo '<p class="advmo-stat">';
        echo '<span class="advmo-stat-number">' . esc_html(number_format_i18n($total_count)) . ' </span>';
        echo '<span class="advmo-stat-label">' . esc_html__('Media Attachments', 'advanced-media-offloader') . '</span>';
        echo '</p>';
        echo '<p class="description">' . esc_html__('Total number of media files stored on your server.', 'advanced-media-offloader') . '</p>';
    }

    public function offloaded_media_field()
    {
        $offloaded_count = advmo_get_offloaded_media_items_count();

        echo '<p class="advmo-stat">';
        echo '<span class="advmo-stat-number">' . esc_html(number_format_i18n($offloaded_count)) . ' </span>';
        echo '<span class="advmo-stat-label">' . esc_html__('Media Attachments', 'advanced-media-offloader') . '</span>';
        echo '</p>';
        echo '<p class="description">' . esc_html__('Number of media files successfully moved to cloud storage.', 'advanced-media-offloader') . '</p>';
    }

    public function non_offloaded_media_field()
    {
        $non_offloaded_count = advmo_get_unoffloaded_media_items_count();

        echo '<p class="advmo-stat">';
        echo '<span class="advmo-stat-number">' . esc_html(number_format_i18n($non_offloaded_count)) . ' </span>';
        if ($non_offloaded_count === 0) {
            echo '<span class="advmo-stat-label">' . esc_html__('Media Attachments', 'advanced-media-offloader') . '</span>';
        } elseif ($non_offloaded_count === 1) {
            echo '<span class="advmo-stat-label">' . esc_html__('Media Attachment', 'advanced-media-offloader') . '</span>';
        } else {
            echo '<span class="advmo-stat-label">' . esc_html__('Media Attachments', 'advanced-media-offloader') . '</span>';
        }
        echo '</p>';

        if ($non_offloaded_count === 0) {
            echo '<p class="description">' . esc_html__('Great job! All your media files are offloaded to cloud storage.', 'advanced-media-offloader') . '</p>';
        } elseif ($non_offloaded_count === 1) {
            echo '<p class="description">' . esc_html__('There is 1 media file still stored on your local server.', 'advanced-media-offloader') . '</p>';
            echo '<p class="description">' . esc_html__('This file can be offloaded to free up local storage space.', 'advanced-media-offloader') . '</p>';
        } else {
            echo '<p class="description">' . esc_html__('These files can be offloaded to free up local storage space.', 'advanced-media-offloader') . '</p>';
        }
    }

    public function offload_errors_field()
    {
        $attachments_with_errors = $this->get_attachments_with_errors();
        $count = count($attachments_with_errors);

        $nonce = wp_create_nonce('advmo_download_errors_csv');
        $download_url = admin_url('admin-ajax.php?action=advmo_download_errors_csv&nonce=' . $nonce);

        $output = "<p>" . __('Number of attachments with errors:', 'advanced-media-offloader') . " <strong>{$count}</strong></p>";

        if ($count > 0) {
            $output .= "<p><a href='{$download_url}' class='button button-secondary'>" . __('Download Errors CSV', 'advanced-media-offloader') . "</a></p>";
        }

        echo $output;
    }

    private function get_attachments_with_errors()
    {
        global $wpdb;
        $meta_key = 'advmo_error_log';

        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
            $meta_key
        );

        return $wpdb->get_col($query);
    }

    public function handle_download_errors_csv()
    {
        try {
            if (!current_user_can('manage_options')) {
                throw new Exception('You do not have sufficient permissions to access this page.');
            }

            if (!check_admin_referer('advmo_download_errors_csv', 'nonce')) {
                throw new Exception('Invalid nonce. Please try again.');
            }

            $attachments_with_errors = $this->get_attachments_with_errors();

            if (empty($attachments_with_errors)) {
                throw new Exception('No attachments with errors found.');
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="advmo_errors.csv"');

            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new Exception('Unable to create output stream.');
            }

            fputcsv($output, array('Attachment ID', 'Attachment Title', 'Errors'));

            foreach ($attachments_with_errors as $attachment_id) {
                $attachment = get_post($attachment_id);
                if (!$attachment) {
                    continue; // Skip if attachment doesn't exist
                }
                $errors = get_post_meta($attachment_id, 'advmo_error_log', true);
                $errors_string = is_array($errors) ? implode("; \n", $errors) : $errors;

                fputcsv($output, array(
                    $attachment_id,
                    $attachment->post_title,
                    $errors_string
                ));
            }

            fclose($output);
            exit;
        } catch (Exception $e) {
            status_header(500);
            wp_die('Error generating CSV: ' . esc_html($e->getMessage()), 'Error', array('response' => 500));
        }
    }


    public function bulk_offload_media_field()
    {
        // WP CLI Notice
        echo '<div style="background: #f0f6fc; border: 1px solid #c3d4e7; border-radius: 4px; padding: 12px; margin-bottom: 20px;">';
        echo '<p style="margin: 0 0 8px 0;"><strong style="color: #0073aa;">💡 ' . __('Use WP CLI for Large Operations', 'advanced-media-offloader') . '</strong></p>';
        echo '<p style="margin: 0 0 8px 0; font-size: 13px;">' . __('For sites with hundreds or thousands of media files, our WP CLI command offers superior performance and control:', 'advanced-media-offloader') . '</p>';
        echo '<p style="margin: 0 0 8px 0;"><code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-family: monospace;">wp advmo offload</code></p>';
        echo '<p style="margin: 0; font-size: 13px;">';
        echo ' <a href="https://wpfitter.com/blog/advmo-bulk-offload-with-wp-cli" target="_blank" style="color: #0073aa; text-decoration: none;">' . __('Learn more →', 'advanced-media-offloader') . '</a>';
        echo '</p>';
        echo '</div>';

        echo '<p class="description"><strong>' . __('Note:', 'advanced-media-offloader') . '</strong> ';
        echo __('This web-based bulk offload supports up to 50 media attachments. For larger operations with hundreds or thousands of files, we recommend using WP CLI commands which provide better performance and reliability for large-scale operations.', 'advanced-media-offloader') . '</p><br />';

        $bulk_offload_data = advmo_get_bulk_offload_data();
        $count = advmo_get_unoffloaded_media_items_count();
        $is_offloading = $bulk_offload_data['status'] === 'processing';
        $progress = ($is_offloading && $bulk_offload_data['total'] > 0) ? ($bulk_offload_data['processed'] / $bulk_offload_data['total']) * 100 : 0;

        if ($count > 0 || $is_offloading) {
            if (!$is_offloading) {
                echo '<p>' . sprintf(
                    _n(
                        'You have %d file still stored on your server.',
                        'You have %d files still stored on your server.',
                        $count,
                        'advanced-media-offloader'
                    ),
                    $count
                ) . '</p>';
                echo '<p class="description">' . __('Offload them to cloud storage now to free up space and enhance your website\'s performance.', 'advanced-media-offloader') . '</p>';
                echo '<button type="button" id="bulk-offload-button" class="button">' . __('Offload Now', 'advanced-media-offloader') . '</button>';
            }

            $display_style = $is_offloading ? 'block' : 'none';
            $progress_width = $is_offloading ? $progress : 0;
            $progress_text = $is_offloading ? round($progress) . '%' : '0%';
            if ($is_offloading && $bulk_offload_data['total'] == 0) {
                $progress_text = __('Preparing...', 'advanced-media-offloader');
            }

            $progress_status = $is_offloading ? 'processing' : 'idle';
            $processed = $bulk_offload_data['processed'] ?? 0;
            $total = $bulk_offload_data['total'] ?? 0;

            echo '<div id="progress-container" style="display: ' . esc_attr($display_style) . '; margin-top: 20px;" data-status="' . esc_attr($progress_status) . '">';
            echo '<p id="progress-title" style="font-size: 16px; font-weight: bold;">' .
                sprintf(
                    __('Offloading media files to cloud storage (%1$s of %2$s)', 'advanced-media-offloader'),
                    '<span id="processed-count">' . esc_html($processed) . '</span>',
                    '<span id="total-count">' . esc_html($total) . '</span>'
                ) .
                '</p>';
            echo '    <div class="progress-bar-container" style="width: 100%; background-color: #e0e0e0; padding: 3px; border-radius: 3px;">';
            printf('        <div id="offload-progress" style="width: %.1f%%; height: 20px; background-color: #0073aa; border-radius: 2px; transition: width 0.5s;"></div>', esc_html($progress_width));
            echo '    </div>';
            printf('    <p id="progress-text" style="margin-top: 10px; font-weight: bold;">%s</p>', esc_html($progress_text));
            if (get_option("advmo_bulk_offload_cancelled") === false) {
                echo '<button type="button" id="bulk-offload-cancel-button" class="button">' . __('Cancel', 'advanced-media-offloader') . '</button>';
            } else {
                echo "<p>Canceling the bulk offload process…</p>";
            }
            echo '</div>';
        } else {
            echo '<p>' . __('All media files are currently stored in the cloud.', 'advanced-media-offloader') . '</p>';
        }
    }
}
