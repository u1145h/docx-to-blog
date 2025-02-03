<?php
class DOCX_To_Blog_Import {
    public function import_post($file) {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed with error code: ' . $file['error']);
            }

            require_once DOCX_TO_BLOG_PLUGIN_DIR . 'vendor/autoload.php';

            // Load the DOCX file
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($file['tmp_name']);

            $content = '';
            $title = '';
            $first_element = true;

            // Process each section
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $elementText = '';
                        foreach ($element->getElements() as $textElement) {
                            if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                $elementText .= $textElement->getText();
                            }
                        }

                        // First text element is considered as title
                        if ($first_element && !empty(trim($elementText))) {
                            $title = $elementText;
                            $first_element = false;
                        } else {
                            if (!empty(trim($elementText))) {
                                $content .= "<p>" . $elementText . "</p>\n";
                            }
                        }
                    }
                }
            }

            // If no title was found, use a default
            if (empty($title)) {
                $title = 'Imported Post - ' . date('Y-m-d H:i:s');
            }

            // Create post
            $post_data = array(
                'post_title'    => wp_strip_all_tags($title),
                'post_content'  => wp_kses_post($content),
                'post_status'   => 'draft',
                'post_author'   => get_current_user_id(),
                'post_type'     => 'post'
            );

            // Insert the post into the database
            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }

            // Add success message
            add_settings_error(
                'docx_import',
                'import_success',
                sprintf(
                    'Post imported successfully! <a href="%s">Edit Post</a>',
                    get_edit_post_link($post_id)
                ),
                'success'
            );

            return $post_id;

        } catch (Exception $e) {
            error_log('DOCX Import Error: ' . $e->getMessage());
            add_settings_error(
                'docx_import',
                'import_error',
                'Error importing file: ' . $e->getMessage(),
                'error'
            );
            return false;
        }
    }
}
