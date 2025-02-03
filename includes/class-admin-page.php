<?php
class DOCX_To_Blog_Admin_Page {
    private $exporter;
    private $importer;

    public function __construct() {
        $this->exporter = new DOCX_To_Blog_Export();
        $this->importer = new DOCX_To_Blog_Import();
    }

    public function add_menu() {
        add_menu_page(
            'DOCX to Blog Converter',
            'DOCX Converter',
            'manage_options',
            'docx-converter',
            [$this, 'render_admin_page'],
            'dashicons-media-document'
        );
    }

    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'export';
        ?>
        <div class="wrap">
            <h1>DOCX to Blog Converter</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=docx-converter&tab=export" class="nav-tab <?php echo $active_tab === 'export' ? 'nav-tab-active' : ''; ?>">Export Posts</a>
                <a href="?page=docx-converter&tab=import" class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">Import Posts</a>
            </h2>
            <div class="tab-content">
                <?php
                if ($active_tab === 'export') {
                    $this->render_export_tab();
                } else {
                    $this->render_import_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_export_tab() {
        // Handle export action
        if (isset($_GET['action']) && $_GET['action'] === 'export' && isset($_GET['post_id'])) {
            $this->exporter->export_post(intval($_GET['post_id']));
        }

        // Get all published posts
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);
        ?>
        <div class="export-section">
            <h3>Export Blog Posts</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo esc_html($post->post_title); ?></td>
                        <td><?php echo get_the_date('', $post); ?></td>
                        <td>
                            <a href="?page=docx-converter&tab=export&action=export&post_id=<?php echo $post->ID; ?>"
                               class="button button-primary">Export to DOCX</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_import_tab() {
        $messages = get_settings_errors('docx_import');

        // Display messages if any
        if (!empty($messages)) {
            foreach ($messages as $message) {
                ?>
                <div class="<?php echo $message['type'] === 'success' ? 'updated' : 'error'; ?> notice is-dismissible">
                    <p><?php echo wp_kses_post($message['message']); ?></p>
                </div>
                <?php
            }
        }

        // Handle file upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['docx_file'])) {
            $this->importer->import_post($_FILES['docx_file']);
        }
        ?>
        <div class="import-section">
            <h3>Import DOCX File</h3>
            <form method="post" enctype="multipart/form-data" class="import-form">
                <?php wp_nonce_field('docx_import_action', 'docx_import_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="docx_file">Choose DOCX File</label>
                        </th>
                        <td>
                            <input type="file"
                                   name="docx_file"
                                   id="docx_file"
                                   accept=".docx"
                                   required>
                            <p class="description">
                                Select a Word document (.docx) to import as a blog post.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Import as Blog Post'); ?>
            </form>
        </div>
        <?php
    }
}
