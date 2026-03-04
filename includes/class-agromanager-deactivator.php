<?php
/**
 * AgroManager Pro – Plugin Deactivator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AgroManager_Deactivator {

    /**
     * Run on plugin deactivation.
     * Note: Does NOT delete database tables to preserve data.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
