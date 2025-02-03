<?php
/*
Plugin Name: DOCX to Blog Converter
Plugin URI: https://www.github.com/u1145h/docx-to-blog
Description: A WordPress plugin to import/export blog posts to/from Word documents.
Version: 1.7
Author: Ullash
Author URI: https://www.ullashroy.site/
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DOCX_TO_BLOG_VERSION', '1.1');
define('DOCX_TO_BLOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOCX_TO_BLOG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if vendor directory exists
if (!file_exists(DOCX_TO_BLOG_PLUGIN_DIR . 'vendor/autoload.php')) {
    add_action('admin_notices', function() {
        ?>
        <div class="error">
            <p>DOCX to Blog Converter requires Composer dependencies to be installed. Please run <code>composer install</code> in the plugin directory.</p>
        </div>
        <?php
    });
    return;
}

// Enable error reporting for debugging
if (WP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Autoload classes
require_once DOCX_TO_BLOG_PLUGIN_DIR . 'vendor/autoload.php';
require_once DOCX_TO_BLOG_PLUGIN_DIR . 'includes/class-docx-export.php';
require_once DOCX_TO_BLOG_PLUGIN_DIR . 'includes/class-docx-import.php';
require_once DOCX_TO_BLOG_PLUGIN_DIR . 'includes/class-admin-page.php';

// Initialize plugin
function docx_to_blog_init() {
    try {
        $admin_page = new DOCX_To_Blog_Admin_Page();
        add_action('admin_menu', [$admin_page, 'add_menu']);
        add_action('admin_enqueue_scripts', 'docx_to_blog_enqueue_admin_assets');
    } catch (Exception $e) {
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="error">
                <p>DOCX to Blog Converter Error: <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}
add_action('plugins_loaded', 'docx_to_blog_init');

// Enqueue admin assets
function docx_to_blog_enqueue_admin_assets($hook) {
    if ('toplevel_page_docx-converter' !== $hook) {
        return;
    }

    wp_enqueue_style('docx-to-blog-admin',
        DOCX_TO_BLOG_PLUGIN_URL . 'assets/css/admin-style.css',
        [],
        DOCX_TO_BLOG_VERSION
    );

    wp_enqueue_script('docx-to-blog-admin',
        DOCX_TO_BLOG_PLUGIN_URL . 'assets/js/admin-script.js',
        ['jquery'],
        DOCX_TO_BLOG_VERSION,
        true
    );
}

// Add error logging
function docx_to_blog_log_error($message) {
    if (WP_DEBUG_LOG) {
        error_log('DOCX to Blog Converter Error: ' . $message);
    }
}

// Add this after plugin header
function docx_to_blog_check_requirements() {
    $required_extensions = ['zip', 'xml', 'fileinfo'];
    $missing_extensions = [];

    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $missing_extensions[] = $ext;
        }
    }

    if (!empty($missing_extensions)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'Required PHP extensions are missing: ' . implode(', ', $missing_extensions) . '.
            Please contact your hosting provider to enable these extensions.',
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }
}
register_activation_hook(__FILE__, 'docx_to_blog_check_requirements');
