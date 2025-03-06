<?php

namespace SuperBlank;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Elementor_Sections
{

    public function __construct()
    {
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
        add_action('wp_ajax_get_elementor_template', array($this, 'get_template_data'));
        add_action('elementor/editor/footer', array($this, 'additional_style'));
        add_action('elementor/editor/footer', array($this, 'render_modal_template'));
    }

    public function enqueue_editor_scripts()
    {

        wp_enqueue_script('elementor-sections', SUPER_BLANK_PLUGIN_URL . 'assets/js/sb-library.js', ['jquery'], SUPER_BLANK_PLUGIN_VERSION, true);

        wp_enqueue_style('elementor-sections', SUPER_BLANK_PLUGIN_URL . 'assets/css/sb-library.css', [], SUPER_BLANK_PLUGIN_VERSION, 'all');

        // Get templates and localize the script
        $templates = $this->get_templates();
        wp_localize_script(
            'elementor-sections',
            'elementorSectionsData',
            array(
                'templates' => $templates,
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('elementor_sections_nonce'),
                'loaderUrl' => SUPER_BLANK_PLUGIN_URL . 'assets/images/loader.json?v=2'
            )
        );
    }

    private function get_templates()
    {

        $base_dir = plugin_dir_path(dirname(__FILE__)) . 'templates/';
        $data = [
            'pages' => $this->scan_template_directory($base_dir . 'Pages'),
            'sections' => $this->scan_template_directory($base_dir . 'Sections'),
        ];

        return $data;
    }

    private function scan_template_directory($directory)
    {
        $templates = [];

        if (!is_dir($directory)) {
            return $templates;
        }

        // Get the type (Pages or Sections) from the directory path
        $type = basename($directory);

        // Get categories (subdirectories)
        $categories = array_filter(scandir($directory), function ($item) use ($directory) {
            return is_dir($directory . '/' . $item) && !in_array($item, ['.', '..']);
        });

        foreach ($categories as $category) {
            $category_path = $directory . '/' . $category;
            $files = array_filter(scandir($category_path), function ($item) {
                return pathinfo($item, PATHINFO_EXTENSION) === 'php';
            });

            // Strip numeric prefix from category name (e.g., "1-Home" becomes "Home")
            $display_category = preg_replace('/^\d+\-/', '', $category);

            foreach ($files as $file) {
                $templates[] = [
                    'name' => pathinfo($file, PATHINFO_FILENAME),
                    'file' => "{$type}/{$category}/{$file}",
                    'category' => $display_category,
                    'category_order' => $category,
                    'thumbnail' => $this->get_template_thumbnail($category_path, $file),
                    'type' => $type // Add this line to include the type
                ];
            }
        }

        return $templates;
    }

    private function get_template_thumbnail($category_path, $template_file)
    {
        // Remove .php extension and add .jpg
        $thumbnail_name = str_replace('.php', '.jpg', $template_file);
        $thumbnail_path = $category_path . '/' . $thumbnail_name;

        // Check if thumbnail exists, otherwise return default.svg
        if (file_exists($thumbnail_path)) {
            return plugins_url('templates/' . basename(dirname($category_path)) . '/' . basename($category_path) . '/' . $thumbnail_name, dirname(__FILE__)) . '?v=3';
        }

        return plugins_url('templates/default.svg', dirname(__FILE__));
    }

    public function get_template_data()
    {
        check_ajax_referer('elementor_sections_nonce', 'nonce');

        $template_file = sanitize_text_field($_POST['template']);
        // Remove any potential directory traversal
        $template_file = str_replace('..', '', $template_file);

        // Construct the correct path to the template file
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $template_file;

        if (file_exists($template_path)) {

            $template_data = include $template_path;
            wp_send_json_success($template_data);
        } else {

            wp_send_json_error('Template file not found');
        }
    }

    public function additional_style()
    {
?>
        <style>
            #elementor-panel-elements-notice-area,
            #elementor-panel-get-pro-elements-sticky {
                display: none !important;
            }
        </style>
    <?php
    }

    public function render_modal_template()
    {
    ?>
        <div class="sb-library-modal" style="display: none;">
            <div class="sb-library-modal-overlay"></div>
            <div class="sb-library-modal-container">
                <div class="sb-library-modal-header">
                    <div class="sb-library-modal-brand">
                        <div class="sb-logo">

                            <svg class="sb-white-mode" width="20" height="20" viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_599_1017)">
                                    <rect width="128" height="128" rx="64" fill="black" />
                                    <circle cx="90.6667" cy="58.6667" r="10.6667" fill="#E1E4EB" />
                                    <path d="M27.7034 52.7708L-10.667 107.142V138.667H128V96.5126C123.852 92.5668 114.696 83.7799 111.259 80.1997C107.822 76.6196 103.012 78.708 101.037 80.1997L83.4071 96.5126C70.1232 83.52 42.5478 56.582 38.5182 52.7708C34.4886 48.9597 29.6293 51.1829 27.7034 52.7708Z" fill="#E1E4EB" />
                                </g>
                                <rect x="2" y="2" width="124" height="124" rx="62" stroke="black" stroke-width="4" />
                                <defs>
                                    <clipPath id="clip0_599_1017">
                                        <rect width="128" height="128" rx="64" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>

                            <svg class="sb-dark-mode" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_599_540)">
                                    <rect width="20" height="20" rx="10" fill="black" />
                                    <circle cx="14.1667" cy="9.16667" r="1.66667" fill="#E1E4EB" />
                                    <path d="M4.32887 8.24531L-1.6665 16.7407V21.6665H20.0002V15.08C19.352 14.4634 17.9215 13.0905 17.3844 12.5311C16.8474 11.9717 16.0958 12.298 15.7872 12.5311L13.0326 15.08C10.957 13.0499 6.64831 8.8408 6.01868 8.24531C5.38905 7.64981 4.62979 7.99718 4.32887 8.24531Z" fill="#E1E4EB" />
                                </g>
                                <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#F1F2F4" />
                                <defs>
                                    <clipPath id="clip0_599_540">
                                        <rect width="20" height="20" rx="10" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>

                        </div>
                        <span class="sb-brand-text">Super Blank</span>
                    </div>
                    <div class="sb-library-modal-tabs">
                        <button class="sb-library-tab active" data-tab="pages">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16.9133 6.2125L12.5383 1.8375C12.4512 1.75033 12.3478 1.68117 12.234 1.63398C12.1202 1.5868 11.9982 1.56251 11.875 1.5625H4.375C3.9606 1.5625 3.56317 1.72712 3.27015 2.02015C2.97712 2.31317 2.8125 2.7106 2.8125 3.125V16.875C2.8125 17.2894 2.97712 17.6868 3.27015 17.9799C3.56317 18.2729 3.9606 18.4375 4.375 18.4375H15.625C16.0394 18.4375 16.4368 18.2729 16.7299 17.9799C17.0229 17.6868 17.1875 17.2894 17.1875 16.875V6.875C17.1875 6.62656 17.0889 6.38827 16.9133 6.2125ZM12.5 4.45312L14.2969 6.25H12.5V4.45312ZM4.6875 16.5625V3.4375H10.625V7.1875C10.625 7.43614 10.7238 7.6746 10.8996 7.85041C11.0754 8.02623 11.3139 8.125 11.5625 8.125H15.3125V16.5625H4.6875Z" fill="currentColor" />
                            </svg>
                            Pages
                        </button>
                        <button class="sb-library-tab" data-tab="sections">
                            <svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_403_282)">
                                    <path d="M1.28525 8.62648L10.0352 13.6265C10.1768 13.7073 10.337 13.7498 10.5001 13.7498C10.6631 13.7498 10.8233 13.7073 10.9649 13.6265L19.7149 8.62648C19.8585 8.5445 19.9778 8.42601 20.0608 8.28305C20.1437 8.14009 20.1874 7.97772 20.1874 7.81242C20.1874 7.64712 20.1437 7.48476 20.0608 7.34179C19.9778 7.19883 19.8585 7.08035 19.7149 6.99836L10.9649 1.99836C10.8233 1.91752 10.6631 1.875 10.5001 1.875C10.337 1.875 10.1768 1.91752 10.0352 1.99836L1.28525 6.99836C1.14171 7.08035 1.0224 7.19883 0.939417 7.34179C0.856435 7.48476 0.812728 7.64712 0.812728 7.81242C0.812728 7.97772 0.856435 8.14009 0.939417 8.28305C1.0224 8.42601 1.14171 8.5445 1.28525 8.62648ZM10.504 3.89211L17.3602 7.81242L10.5001 11.7327L3.63993 7.81242L10.504 3.89211ZM20.0681 11.0976C20.1913 11.3135 20.2238 11.5695 20.1583 11.8093C20.0928 12.0492 19.9347 12.2532 19.7188 12.3765L10.9688 17.3765C10.8273 17.4573 10.667 17.4998 10.504 17.4998C10.341 17.4998 10.1807 17.4573 10.0392 17.3765L1.28915 12.3765C1.18067 12.3163 1.08523 12.2351 1.00834 12.1377C0.931459 12.0404 0.874668 11.9287 0.841262 11.8092C0.807857 11.6897 0.798501 11.5648 0.813736 11.4417C0.828972 11.3185 0.868496 11.1997 0.930019 11.0919C0.991541 10.9842 1.07384 10.8897 1.17214 10.814C1.27044 10.7383 1.38278 10.6829 1.50267 10.6509C1.62255 10.619 1.74758 10.6111 1.87052 10.6279C1.99346 10.6446 2.11185 10.6855 2.21884 10.7484L10.5001 15.4827L18.7813 10.7484C18.8884 10.6867 19.0066 10.6468 19.1292 10.631C19.2517 10.6152 19.3761 10.6238 19.4954 10.6562C19.6146 10.6887 19.7262 10.7444 19.8238 10.8201C19.9214 10.8959 20.0031 10.9902 20.0641 11.0976H20.0681Z" fill="currentColor" />
                                </g>
                                <defs>
                                    <clipPath id="clip0_403_282">
                                        <rect width="20" height="20" fill="white" transform="translate(0.5)" />
                                    </clipPath>
                                </defs>
                            </svg>
                            Sections
                        </button>
                    </div>
                    <button class="sb-library-theme-toggle" title="Toggle theme">
                        <svg width="17" height="17" viewBox="0 0 90 90" class="sb-theme-light" xmlns="http://www.w3.org/2000/svg">
                            <path d="M87.823 60.7c-0.463-0.423-1.142-0.506-1.695-0.214c-15.834 8.398-35.266 2.812-44.232-12.718c-8.966-15.53-4.09-35.149 11.101-44.665c0.531-0.332 0.796-0.963 0.661-1.574c-0.134-0.612-0.638-1.074-1.259-1.153c-9.843-1.265-19.59 0.692-28.193 5.66C13.8 12.041 6.356 21.743 3.246 33.35S1.732 57.08 7.741 67.487c6.008 10.407 15.709 17.851 27.316 20.961C38.933 89.486 42.866 90 46.774 90c7.795 0 15.489-2.044 22.42-6.046c8.601-4.966 15.171-12.43 18.997-21.586C88.433 61.79 88.285 61.123 87.823 60.7z" fill="currentColor" />
                        </svg>
                        <svg width="17" height="17" viewBox="0 0 90 90" class="sb-theme-dark" xmlns="http://www.w3.org/2000/svg">
                            <path d="M87.823 60.7c-0.463-0.423-1.142-0.506-1.695-0.214c-15.834 8.398-35.266 2.812-44.232-12.718c-8.966-15.53-4.09-35.149 11.101-44.665c0.531-0.332 0.796-0.963 0.661-1.574c-0.134-0.612-0.638-1.074-1.259-1.153c-9.843-1.265-19.59 0.692-28.193 5.66C13.8 12.041 6.356 21.743 3.246 33.35S1.732 57.08 7.741 67.487c6.008 10.407 15.709 17.851 27.316 20.961C38.933 89.486 42.866 90 46.774 90c7.795 0 15.489-2.044 22.42-6.046c8.601-4.966 15.171-12.43 18.997-21.586C88.433 61.79 88.285 61.123 87.823 60.7z" fill="currentColor" />
                        </svg>
                    </button>
                    <button class="sb-library-modal-close">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15.5459 13.954C15.7572 14.1653 15.876 14.452 15.876 14.7509C15.876 15.0497 15.7572 15.3364 15.5459 15.5477C15.3346 15.7591 15.0479 15.8778 14.749 15.8778C14.4501 15.8778 14.1635 15.7591 13.9521 15.5477L7.99996 9.59367L2.0459 15.5459C1.83455 15.7572 1.54791 15.8759 1.24902 15.8759C0.950136 15.8759 0.663491 15.7572 0.452147 15.5459C0.240802 15.3345 0.12207 15.0479 0.12207 14.749C0.12207 14.4501 0.240803 14.1635 0.452147 13.9521L6.40621 7.99992L0.454022 2.04586C0.242677 1.83451 0.123945 1.54787 0.123945 1.24898C0.123945 0.950097 0.242677 0.663452 0.454022 0.452108C0.665366 0.240763 0.95201 0.122031 1.2509 0.122031C1.54978 0.122031 1.83643 0.240763 2.04777 0.452108L7.99996 6.40617L13.954 0.45117C14.1654 0.239826 14.452 0.121094 14.7509 0.121094C15.0498 0.121094 15.3364 0.239826 15.5478 0.45117C15.7591 0.662514 15.8778 0.949159 15.8778 1.24804C15.8778 1.54693 15.7591 1.83358 15.5478 2.04492L9.59371 7.99992L15.5459 13.954Z" fill="currentColor" />
                        </svg>
                    </button>
                </div>
                <div class="sb-library-content-wrapper">
                    <div class="sb-library-modal-categories">
                        <div class="sb-library-category-select-wrapper">
                            <select class="sb-library-category-select">
                                <option value="">All Categories</option>
                            </select>
                            <i class="eicon-caret-down"></i>
                        </div>
                    </div>
                    <div class="sb-library-modal-content">
                        <div class="sb-library-templates-grid"></div>
                    </div>
                </div>
            </div>
            <button class="sb-library-reload-styles" title="Reload Styles todo remove">
                <i class="eicon-sync"></i>
            </button>
        </div>
<?php
    }
}
