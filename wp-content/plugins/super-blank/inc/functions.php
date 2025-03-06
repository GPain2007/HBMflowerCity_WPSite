<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('superBlankPatternsExtractor')) {
    /**
     * Patterns Extractor.
     * 
     * @return void      Register patterns.
     */
    function superBlankPatternsExtractor()
    {

        // Check if block editor is available
        if (! function_exists('register_block_pattern')) {
            return;
        }

        // Define the patterns directory in your plugin
        $pattern_directory = SUPER_BLANK_PLUGIN_PATH . 'patterns/';

        // Get all PHP files in the patterns directory
        $pattern_files = glob($pattern_directory . '*.php');

        foreach ($pattern_files as $pattern_file) {
            $pattern_data = get_file_data(
                $pattern_file,
                array(
                    'title'       => 'Title',
                    'slug'        => 'Slug',
                    'description' => 'Description',
                    'categories'  => 'Categories',
                    'keywords'    => 'Keywords',
                    'viewport'    => 'Viewport width',
                )
            );

            // Skip if required data is missing
            if (empty($pattern_data['title']) || empty($pattern_data['slug'])) {
                continue;
            }

            // Get pattern content
            ob_start();
            include $pattern_file;
            $pattern_content = ob_get_clean();

            // Prepare pattern data
            $pattern = array(
                'title'       => $pattern_data['title'],
                'description' => $pattern_data['description'],
                'content'     => $pattern_content,
                'categories'  => array_map('trim', explode(',', $pattern_data['categories'])),
                'keywords'    => array_map('trim', explode(',', $pattern_data['keywords'])),
            );

            // Register the pattern
            register_block_pattern(
                $pattern_data['slug'],
                $pattern
            );
        }
    }
}
add_action('init', 'superBlankPatternsExtractor');

if (!function_exists('superBlankCustomColorPalette')) {

    /**
     * Add color palette.
     * 
     * @return void      Add color palette.
     */
    function superBlankCustomColorPalette($theme_json)
    {

        // Check if Astra theme is installed
        $theme = wp_get_theme();
        if ($theme->get('Name') === 'Astra' || $theme->get('Template') === 'astra') return $theme_json;

        if ($theme_json instanceof WP_Theme_JSON_Data) {
            $existing_data = $theme_json->get_data();

            $custom_colors = [
                [
                    'name'  => 'Bold',
                    'slug'  => 'bold',
                    'color' => '#303A4D'
                ],
                [
                    'name'  => 'Grey',
                    'slug'  => 'grey',
                    'color' => '#959E95'
                ],
                [
                    'name'  => 'White',
                    'slug'  => 'white',
                    'color' => '#ffffff'
                ],
                [
                    'name'  => 'Light',
                    'slug'  => 'light',
                    'color' => '#E1E4EB'
                ],
                [
                    'name'  => 'Extra Light',
                    'slug'  => 'extra-light',
                    'color' => '#f1f2f8'
                ],
                [
                    'name'  => 'Vibrant',
                    'slug'  => 'vibrant',
                    'color' => '#FFE4B0'
                ]
            ];

            // Add custom colors to the existing palette
            if (isset($existing_data['settings']['color']['palette']['theme'])) {

                $existing_data['settings']['color']['palette']['theme'] = array_merge(
                    $existing_data['settings']['color']['palette']['theme'],
                    $custom_colors
                );
            } else {

                $existing_data['settings']['color']['palette']['theme'] = $custom_colors;
            }

            $theme_json->update_with($existing_data);
        }

        return $theme_json;
    }
}
add_filter('wp_theme_json_data_theme', 'superBlankCustomColorPalette');

if (!function_exists('superBlankCleanTerms')) {
    /**
     * Cleanup terms with related data
     *
     * @return void
     */
    function superBlankCleanTerms()
    {

        global $wpdb;

        $taxonomies = get_taxonomies();

        foreach ($taxonomies as $taxonomy) {

            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));

            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }

        $wpdb->query('START TRANSACTION');

        try {

            // Remove all term relationships
            $wpdb->query("TRUNCATE TABLE " . $wpdb->term_relationships);

            // Remove all term taxonomies
            $wpdb->query("TRUNCATE TABLE " . $wpdb->term_taxonomy);

            // Remove all terms
            $wpdb->query("TRUNCATE TABLE " . $wpdb->terms);

            // Reset auto-increment values
            $wpdb->query("ALTER TABLE {$wpdb->terms} AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->term_taxonomy} AUTO_INCREMENT = 1");

            // Commit the transaction
            $wpdb->query('COMMIT');

            // Optionally, you can recreate the default "Uncategorized" category
            wp_insert_term('Uncategorized', 'category', array('slug' => 'uncategorized'));
        } catch (Exception $e) {

            // If there's an error, roll back the transaction
            $wpdb->query('ROLLBACK');
        }

        // Clear the cache
        wp_cache_flush();
    }
}

if (!function_exists('superBlankCleanAttachmentsWithFiles')) {
    /**
     * Delete attachments with files
     *
     * @return void
     */
    function superBlankCleanAttachmentsWithFiles()
    {

        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'numberposts' => -1
        ]);

        foreach ($attachments as $attachment) {

            wp_delete_attachment($attachment->ID, true);
        }
    }
}

if (!function_exists('superBlankCleanPosts')) {
    /**
     * Cleanup posts table and related data
     *
     * @return void
     */
    function superBlankCleanPosts()
    {

        // Delete all other
        $posts = get_posts([
            'post_type' => [
                'wp_navigation',
                'nav_menu_item',
                'portfolio-works',
                'wp_global_styles',
                'wp_template_part',
                'wpforms',
                'post',
                'page',
                'product',
                'shop_order_placehold',
                'customize_changeset'
            ],
            'post_status' => [
                'publish',
                'pending',
                'draft',
                'auto-draft',
                'future',
                'private',
                'trash',
            ],
            'numberposts' => -1
        ]);

        foreach ($posts as $post) {

            wp_delete_post($post->ID, true);
        }
    }
}

if (!function_exists('superBlankCleanSpecificPosts')) {
    /**
     * Remove special posts. Eg. elementor_library
     * that are connected with Elementor hooks.
     *
     * @return void
     */
    function superBlankCleanSpecificPosts()
    {

        // Delete specific posts
        $posts = get_posts([
            'post_type' => [
                'elementor_library',
                'custom_css',
                'wp_global_styles',
            ],
            'post_status' => [
                'publish',
                'pending',
                'draft',
                'auto-draft',
                'future',
                'private',
                'trash',
            ],
            'numberposts' => -1
        ]);

        foreach ($posts as $post) {

            wp_delete_post($post->ID, true);
        }
    }
}

if (!function_exists('superBlankCleanComments')) {
    /**
     * Cleanup comments table and related data
     *
     * @return void
     */
    function superBlankCleanComments()
    {

        $comments = get_comments(array('status' => 'any', 'number' => -1));

        foreach ($comments as $comment) {

            wp_delete_comment($comment->comment_ID, true);
        }
    }
}

if (!function_exists('superBlankSetPermalinkStructure')) {
    /**
     * Set permalink structure
     * 
     * @param string $structure   permalink structure.
     *
     * @return void
     */
    function superBlankSetPermalinkStructure($structure)
    {

        if (!$structure) {

            $structure = '/%postname%/';
        }

        // Set permalinks
        update_option('permalink_structure', sanitize_text_field($structure));

        flush_rewrite_rules();
    }
}

if (!function_exists('superBlankDeleteExactOptions')) {
    /**
     * Delete exact options like site_logo etc.
     * 
     * @param array $options   list of options
     *
     * @return boolean
     */
    function superBlankDeleteExactOptions($options = [])
    {

        if (empty($options)) return;

        global $wpdb;

        $placeholders = implode(',', array_fill(0, count($options), '%s'));

        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name IN ($placeholders)",
            $options
        );

        return $wpdb->query($sql);
    }
}

if (!function_exists('superBlankDeleteOptionsByPattern')) {
    /**
     * Delete options using patterns like: woocommerce_% or %_transient_%.
     * 
     * @param array $patterns   list of options
     *
     * @return void
     */
    function superBlankDeleteOptionsByPattern($patterns = [])
    {

        if (!is_array($patterns)) return;

        global $wpdb;

        foreach ($patterns as $pattern) {

            $sql = $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            );

            $wpdb->query($sql);
        }
    }
}

if (!function_exists('superBlankSanitizeNestedArray')) {
    /**
     * $array nested array
     *
     * @return array
     */
    function superBlankSanitizeNestedArray($array, $oldUrl = false, $newUrl = false)
    {

        $array = wp_unslash($array);

        if (is_string($array)) {

            if ($oldUrl && $newUrl) {

                $array = str_replace($oldUrl, $newUrl, $array);
            }

            return $array;
        }

        foreach ($array as $key => $value) {

            $value = wp_unslash($value);

            if (is_array($value)) {

                $array[$key] = superBlankSanitizeNestedArray($value, $oldUrl, $newUrl);
            }

            if (is_string($value)) {

                if ($oldUrl && $newUrl) {

                    $value = str_replace($oldUrl, $newUrl, $value);
                }

                $array[$key] = $value;
            }
        }

        return $array;
    }
}

if (!function_exists('superBlankDeleteLostRecordsTermmeta')) {
    /**
     * Delete Termmeta trash
     *
     */
    function superBlankDeleteLostRecordsTermmeta()
    {

        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {

            // Delete orphaned termmeta
            $deleted = $wpdb->query("
                DELETE meta FROM {$wpdb->termmeta} meta
                LEFT JOIN {$wpdb->terms} terms ON meta.term_id = terms.term_id
                WHERE terms.term_id IS NULL
            ");

            // Optional: Delete duplicate termmeta entries
            $deleted += $wpdb->query("
                DELETE t1 FROM {$wpdb->termmeta} t1
                INNER JOIN {$wpdb->termmeta} t2
                WHERE t1.meta_id < t2.meta_id
                AND t1.term_id = t2.term_id
                AND t1.meta_key = t2.meta_key
            ");

            // If everything went well, commit the transaction
            $wpdb->query('COMMIT');
        } catch (Exception $e) {

            // If there was an error, rollback the changes
            $wpdb->query('ROLLBACK');
        }
    }
}

if (!function_exists('superBlankDeleteLostRecordsPostmeta')) {
    /**
     * Delete Postmeta trash
     *
     */
    function superBlankDeleteLostRecordsPostmeta()
    {

        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {

            // Delete orphaned postmeta (where post_id doesn't exist in wp_posts table)
            $wpdb->query("
                DELETE pm
                FROM {$wpdb->postmeta} pm
                LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE p.ID IS NULL
            ");

            // Delete duplicate postmeta
            $wpdb->query("
                DELETE pm1 FROM
                {$wpdb->postmeta} pm1
                INNER JOIN
                (
                    SELECT post_id, meta_key, meta_value, MIN(meta_id) as first_meta_id
                    FROM {$wpdb->postmeta}
                    GROUP BY post_id, meta_key, meta_value
                    HAVING COUNT(*) > 1
                ) pm2
                ON pm1.post_id = pm2.post_id
                AND pm1.meta_key = pm2.meta_key
                AND pm1.meta_value = pm2.meta_value
                WHERE pm1.meta_id > pm2.first_meta_id
            ");

            // Commit the transaction
            $wpdb->query('COMMIT');
        } catch (Exception $e) {

            // If there's an error, roll back the transaction
            $wpdb->query('ROLLBACK');
        }
    }
}

if (!function_exists('superBlankFindAndReplaceWpFormsId')) {
    /**
     * Replace WP Forms ID
     *
     */
    function superBlankFindAndReplaceWpFormsId($content, $newId)
    {

        if (!is_array($content)) {
            return $content;
        }

        foreach ($content as $key => $value) {

            if (is_array($value)) {

                $content[$key] = superBlankFindAndReplaceWpFormsId($value, $newId);
            } elseif ($key === 'form_id') {

                $content[$key] = $newId;
            }
        }

        return $content;
    }
}

if (!function_exists('superBlankDeleteDirectory')) {
    /**
     * Delete plugin directory with files
     * 
     * @param string $dir   directory.
     *
     * @return boolean
     */
    function superBlankDeleteDirectory($dir)
    {
        // Ensure WordPress filesystem functions are available
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        // Initialize the WordPress Filesystem
        if (!function_exists('WP_Filesystem')) {

            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        WP_Filesystem();
        global $wp_filesystem;

        if (substr($dir, -1) === '.') {

            return false;
        }

        // Check if the directory exists
        if (!$wp_filesystem->exists($dir)) {

            return false;
        }

        // If it's a single file, delete it normally
        if (!$wp_filesystem->is_dir($dir)) {

            return $wp_filesystem->delete($dir);
        }

        // If it's a folder, recursively delete it and its contents
        return $wp_filesystem->delete($dir, true);
    }
}

if (!function_exists('superBlankParseElementorSections')) {
    /**
     * Parse Elementor sections and extract data
     * 
     * @param string $array   List of files (templates).
     *
     * @return array
     */
    function superBlankParseElementorSections($filesArray)
    {

        $sections = array();

        foreach ($filesArray as $key => $value) {

            if (file_exists($value)) {

                $section = include $value;

                if (!isset($section['content'])) continue;

                if (!isset($section['content'][0])) continue;

                $sections[$key] = $section['content'][0];
            }
        }

        return $sections;
    }
}
