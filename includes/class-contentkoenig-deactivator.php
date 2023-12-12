<?php

class Contentkoenig_Deactivator {
    public static function deactivate() {
        wp_clear_scheduled_hook(PLUGIN_SLUG_uhbyqy . '_cron_make_posts');
        wp_clear_scheduled_hook(PLUGIN_SLUG_uhbyqy . '_cron_licence_check');
    }
}
