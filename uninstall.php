<?php
/**
 * Uninstall script for ContentCraft AI
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options
delete_option('contentcraft_ai_settings');

// Clean up any other data
delete_transient('contentcraft_ai_cache');

// Clean up user meta if any
$users = get_users();
foreach ($users as $user) {
    delete_user_meta($user->ID, 'contentcraft_ai_preferences');
}