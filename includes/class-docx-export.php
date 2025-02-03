<?php

class DOCX_To_Blog_Export {
    private $phpWord;

    public function export_post($post_id) {
        try {
            $post = get_post($post_id);
            if (!$post) {
                wp_die('Invalid post ID.');
            }

            require_once DOCX_TO_BLOG_PLUGIN_DIR . 'vendor/autoload.php';

            $this->phpWord = new \PhpOffice\PhpWord\PhpWord();

            // Set document properties
            $properties = $this->phpWord->getDocInfo();
            $properties->setCreator(get_bloginfo('name'));
            $properties->setTitle($post->post_title);

            // Add a section
            $section = $this->phpWord->addSection([
                'marginLeft' => 1133,
                'marginRight' => 1133,
                'marginTop' => 1133,
                'marginBottom' => 1133
            ]);

            // Add title
            $section->addText(
                $post->post_title,
                ['bold' => true, 'size' => 40, 'name' => 'Arial'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 240]
            );

            $section->addTextBreak(2);

            // Get clean content
            $content = apply_filters('the_content', $post->post_content);
            $content = wp_strip_all_tags($content, true); // Strip HTML tags

            // Split into paragraphs and add to document
            $paragraphs = explode("\n", $content);
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (!empty($paragraph)) {
                    $section->addText(
                        $paragraph,
                        ['size' => 21, 'name' => 'Arial'],
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT, 'spaceAfter' => 240]
                    );
                }
            }

            // Save file
            $file_name = sanitize_file_name($post->post_title) . '.docx';
            $file_path = wp_upload_dir()['path'] . '/' . $file_name;

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this->phpWord, 'Word2007');
            $objWriter->save($file_path);

            // Send file to browser
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));

            ob_clean();
            flush();
            readfile($file_path);
            unlink($file_path);
            exit;

        } catch (Exception $e) {
            error_log('DOCX Export Error: ' . $e->getMessage());
            wp_die('Error exporting document: ' . $e->getMessage());
        }
    }
}
