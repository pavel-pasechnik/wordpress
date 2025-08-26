<?php

namespace Advanced_Media_Offloader\Interfaces;

interface ObserverInterface
{
    /**
     * Register the observer with WordPress hooks.
     *
     * @return void
     */
    public function register(): void;
}
