<?php

namespace Advanced_Media_Offloader\Admin\Observers;

use Advanced_Media_Offloader\Interfaces\ObserverInterface;

class AdminFooterTexts implements ObserverInterface
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

    public function register(): void
    {
        add_action('admin_footer_text', [$this, 'run']);
        add_filter('update_footer', [$this, 'admin_footer_version_text']);
    }

    public function run($text)
    {
        return advmo_get_copyright_text();
    }

    public function admin_footer_version_text($text)
    {
        global $advmo;
        return "Version " . $advmo->version;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {}
}
