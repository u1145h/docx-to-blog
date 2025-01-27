<?php

if (!defined('ABSPATH')) exit;

function docx_importer($file) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Check file validity
    if ($file['type'] !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        echo '<div class="error">Invalid file type. Please upload a DOCX file.</div>';
        return;
    }

    // Move uploaded file
    $upload = wp_handle_upload($file, ['test_form' => false]);
    if (!$upload || isset($upload['error'])) {
        echo '<div class="error">File upload failed: ' . $upload['error'] . '</div>';
        return;
    }

    // Load PHPWord
    if (!class_exists('PhpOffice\PhpWord\IOFactory')) {
        require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
    }

    $phpWord = \PhpOffice\PhpWord\IOFactory::load($upload['file']);
    $content = '';

    // Extract text from DOCX
    foreach ($phpWord->getSections() as $section) {
        $elements = $section->getElements();
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $content .= $element->getText() . "\n";
            }
        }
    }

    // Create a blog post
    $post_id = wp_insert_post([
        'post_title'   => 'Imported from DOCX',
        'post_content' => $content,
        'post_status'  => 'draft',
    ]);

    echo '<div class="updated">DOCX imported as post ID: ' . $post_id . '</div>';
}
