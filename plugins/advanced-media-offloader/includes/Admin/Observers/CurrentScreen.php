<?php

namespace Advanced_Media_Offloader\Admin\Observers;

use Advanced_Media_Offloader\Admin\Observers\AdminHeader;
use Advanced_Media_Offloader\Admin\Observers\AdminFooterTexts;

use Advanced_Media_Offloader\Interfaces\ObserverInterface;

class CurrentScreen implements ObserverInterface
{
    public function __construct()
    {
        $this->register();
    }

    public function register(): void
    {
        add_action('current_screen', array($this, 'run'));
    }

    public function run($screen)
    {
        if (advmo_is_settings_page()) :
            AdminHeader::getInstance();
            AdminFooterTexts::getInstance();
        endif;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {}
}
