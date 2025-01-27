<?php
/*
Plugin Name: DOCX to Blog Converter
Plugin URI: https://www.github.com/u1145h/docx-to-blog
Description: A WordPress plugin to import/export blog posts to/from Word documents.
Version: 1.1
Author: Ullash
Author URI: https://www.ullashroy.site/
License: GPL2
*/

// Hook to initialize plugin and add menu
add_action('admin_menu', 'docx_to_blog_add_menu');

// Admin Menu Pages for export and import
function docx_to_blog_add_menu() {
    add_menu_page(
        'DOCX to Blog Converter',
        'DOCX Converter',
        'manage_options',
        'docx-converter',
        'docx_to_blog_admin_page',
        'dashicons-media-document'
    );
}

function docx_to_blog_admin_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'export';
    ?>
    <div class="wrap">
        <h1>DOCX to Blog Converter</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=docx-converter&tab=export" class="nav-tab <?php echo $active_tab === 'export' ? 'nav-tab-active' : ''; ?>">Export Posts</a>
            <a href="?page=docx-converter&tab=import" class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">Import Posts</a>
        </h2>
        <div>
            <?php
            if ($active_tab === 'export') {
                docx_to_blog_export_page();
            } elseif ($active_tab === 'import') {
                docx_to_blog_import_page();
            }
            ?>
        </div>
    </div>
    <?php
}

// Export page
function docx_to_blog_export_page() {
    ?>
    <h2>Export Blog Posts</h2>
    <p>Select a blog post to export it as a Word document:</p>
    <table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th class="manage-column column-title">Post Title</th>
                <th class="manage-column column-date">Date</th>
                <th class="manage-column column-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Get all published posts
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]);

            if ($posts) {
                foreach ($posts as $post) {
                    ?>
                    <tr>
                        <td><?php echo esc_html($post->post_title); ?></td>
                        <td><?php echo esc_html(get_the_date('', $post)); ?></td>
                        <td>
                            <!-- Export button for each post -->
                            <a href="<?php echo admin_url('admin.php?page=docx-converter&tab=export&post_id=' . $post->ID); ?>" class="button">Export</a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="3">No posts found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
    <?php

    if (isset($_GET['post_id'])) {
        $post_id = intval($_GET['post_id']);
        docx_to_blog_export_post($post_id);
    }
}

// Import page
function docx_to_blog_import_page() {
    ?>
    <h2>Import Blog Post</h2>
    <form method="post" enctype="multipart/form-data">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="docx_file">Upload Word Document (.docx):</label></th>
                <td><input type="file" name="docx_file" id="docx_file" accept=".docx" required></td>
            </tr>
        </table>
        <?php submit_button('Import Post'); ?>
    </form>
    <?php

    // Form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['docx_file'])) {
        docx_to_blog_import_post($_FILES['docx_file']);
    }
}

// Export - Function
function docx_to_blog_export_post($post_id) {
    $post = get_post($post_id);

    if (!$post) {
        wp_die('Invalid post ID.');
    }

    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    $section = $phpWord->addSection();
    $section->addText(htmlspecialchars($post->post_title), ['bold' => true, 'size' => 16]);
    $section->addTextBreak();
    $section->addText(htmlspecialchars(strip_tags($post->post_content)));

    $file_name = sanitize_title($post->post_title) . '.docx';
    $file_path = wp_upload_dir()['path'] . '/' . $file_name;

    $phpWord->save($file_path, 'Word2007');

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    readfile($file_path);
    unlink($file_path);
    exit;
}

// Import - Function
function docx_to_blog_import_post($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_die('Error uploading file.');
    }

    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    $phpWord = \PhpOffice\PhpWord\IOFactory::load($file['tmp_name']);
    $content = '';

    foreach ($phpWord->getSections() as $section) {
        $elements = $section->getElements();
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $content .= $element->getText() . "\n";
            }
        }
    }

    // Use the first line as the title and the rest as content
    $lines = explode("\n", trim($content));
    $title = array_shift($lines);
    $content = implode("\n", $lines);

    $post_id = wp_insert_post([
        'post_title' => sanitize_text_field($title),
        'post_content' => wp_kses_post($content),
        'post_status' => 'draft',
    ]);

    if ($post_id) {
        echo '<div class="notice notice-success is-dismissible"><p>Post imported successfully!</p></div>';
    } else {
        wp_die('Failed to import post.');
    }
}
