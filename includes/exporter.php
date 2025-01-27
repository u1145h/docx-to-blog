<?php

if (!defined('ABSPATH')) exit;

function docx_exporter($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        echo '<div class="error">Invalid Post ID.</div>';
        return;
    }

    if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
        require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
    }

    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();
    $section->addTitle($post->post_title, 1);
    $section->addText(strip_tags($post->post_content));

    // Save DOCX
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/post-' . $post_id . '.docx';

    $phpWord->save($file_path, 'Word2007');

    echo '<div class="updated">Post exported: <a href="' . $upload_dir['url'] . '/post-' . $post_id . '.docx">Download</a></div>';
}
