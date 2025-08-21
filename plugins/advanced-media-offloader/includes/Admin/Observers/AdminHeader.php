<?php

namespace Advanced_Media_Offloader\Admin\Observers;

use Advanced_Media_Offloader\Interfaces\ObserverInterface;

class AdminHeader implements ObserverInterface
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
        add_action('in_admin_header', [$this, 'run']);
    }

    public function run()
    {

        $this->disable_admin_notices();
        advmo_get_view('admin/header');
    }

    /**
     * Disable admin notices from other plugins
     */
    protected function disable_admin_notices()
    {
        if (advmo_is_settings_page()) {
            remove_all_actions('user_admin_notices');
            remove_all_actions('admin_notices');
        }
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {}
}
