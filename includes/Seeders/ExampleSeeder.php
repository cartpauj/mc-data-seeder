<?php

namespace MCDS\Seeders;

use MCDS\AbstractSeeder;

/**
 * Example seeder demonstrating the framework
 */
class ExampleSeeder extends AbstractSeeder {

    /**
     * Initialize seeder
     */
    protected function init() {
        $this->key = 'example_posts';
        $this->name = __('Pages and Posts', 'membercore-data-seeder');
        $this->description = __('Creates example WordPress posts or pages with random content.', 'membercore-data-seeder');
        $this->default_count = 100;
        $this->default_batch_size = 200;

        // Add custom settings fields
        $this->settings_fields = [
            [
                'key' => 'post_type',
                'label' => __('Post Type', 'membercore-data-seeder'),
                'type' => 'select',
                'default' => 'post',
                'options' => [
                    'post' => __('Post', 'membercore-data-seeder'),
                    'page' => __('Page', 'membercore-data-seeder')
                ],
                'required' => true
            ],
            [
                'key' => 'post_status',
                'label' => __('Post Status', 'membercore-data-seeder'),
                'type' => 'select',
                'default' => 'publish',
                'options' => [
                    'publish' => __('Published', 'membercore-data-seeder'),
                    'draft' => __('Draft', 'membercore-data-seeder')
                ],
                'required' => true
            ]
        ];
    }

    /**
     * Run a batch of seeding
     *
     * @param int $offset Current offset
     * @param int $limit Batch size
     * @param array $settings Seeder settings
     * @return array Results with 'processed' count and optional 'error' message
     */
    public function seed_batch($offset, $limit, $settings) {
        global $wpdb;

        $post_status = $settings['post_status'] ?? 'publish';
        $post_type = $settings['post_type'] ?? 'post';
        $time = current_time('mysql');
        $time_gmt = current_time('mysql', 1);

        // Prepare bulk inserts
        $post_values = [];
        $meta_values = [];

        for ($i = 0; $i < $limit; $i++) {
            // Check if seeder has been cancelled
            if ($this->is_cancelled()) {
                // Return early with the number of posts processed so far
                return [
                    'processed' => $i,
                    'cancelled' => true
                ];
            }

            $index = $offset + $i + 1;

            $post_title = sprintf(__('Example Post %d', 'membercore-data-seeder'), $index);
            $post_name = sanitize_title($post_title);
            $post_content = $this->generate_content();

            $post_values[] = $wpdb->prepare(
                "(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                1, // post_author
                $time,
                $time_gmt,
                $post_content,
                $post_title,
                '',
                $post_status,
                'open',
                'open',
                '',
                $post_name,
                $post_type
            );
        }

        // Insert all posts at once
        if (!empty($post_values)) {
            $sql = "INSERT INTO {$wpdb->posts}
                    (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
                     post_status, comment_status, ping_status, post_password, post_name, post_type)
                    VALUES " . implode(', ', $post_values);

            $result = $wpdb->query($sql);

            if ($result === false) {
                return [
                    'processed' => 0,
                    'error' => $wpdb->last_error ?: __('Failed to insert posts', 'membercore-data-seeder')
                ];
            }

            // Get the IDs of inserted posts
            $first_post_id = $wpdb->insert_id;
            $post_ids = range($first_post_id, $first_post_id + $limit - 1);

            // Prepare postmeta bulk insert
            foreach ($post_ids as $post_id) {
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $post_id, '_mcds_seeded', '1');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $post_id, '_mcds_seeder_key', $this->key);
            }

            // Insert all postmeta at once
            if (!empty($meta_values)) {
                $sql = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(', ', $meta_values);
                $wpdb->query($sql);
            }

            return ['processed' => $limit];
        }

        return ['processed' => 0];
    }

    /**
     * Generate random content
     */
    private function generate_content() {
        $paragraphs = rand(2, 5);
        $content = '';

        for ($i = 0; $i < $paragraphs; $i++) {
            $sentences = rand(3, 7);
            $paragraph = '';

            for ($j = 0; $j < $sentences; $j++) {
                $words = rand(8, 15);
                $sentence = ucfirst($this->generate_words($words)) . '. ';
                $paragraph .= $sentence;
            }

            $content .= '<p>' . $paragraph . '</p>';
        }

        return $content;
    }

    /**
     * Generate random words
     */
    private function generate_words($count) {
        $words = [
            'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
            'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
            'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
            'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
            'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
            'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
            'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
            'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum'
        ];

        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $words[array_rand($words)];
        }

        return implode(' ', $result);
    }

    /**
     * Clean up before starting a new seed
     */
    public function before_seed($settings) {
        // Optional: Clean up previous seeded posts if needed
        // This is just an example - you can leave empty
    }

    /**
     * Clean up after completing seed
     */
    public function after_seed($settings) {
        // Optional: Do something after seeding completes
        // This is just an example - you can leave empty
    }

    /**
     * Get count of items to reset (for progress tracking)
     */
    public function get_reset_count() {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
             WHERE meta_key = %s AND meta_value = %s",
            '_mcds_seeder_key',
            $this->key
        ));

        return intval($count);
    }

    /**
     * Reset/clear data in batches
     *
     * @param int $offset Batch offset (post number to start from)
     * @param int $limit Batch size (number of posts to process)
     * @return array Results with 'processed' count
     */
    public function reset_batch($offset, $limit) {
        global $wpdb;

        // Get batch of post IDs (always use offset 0 since we're deleting as we go)
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = %s AND meta_value = %s
             ORDER BY post_id
             LIMIT %d",
            '_mcds_seeder_key',
            $this->key,
            $limit
        ));

        if (empty($post_ids)) {
            return ['processed' => 0];
        }

        $ids_placeholder = implode(',', array_fill(0, count($post_ids), '%d'));

        // Delete postmeta
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids_placeholder)",
            ...$post_ids
        ));

        // Delete posts
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->posts} WHERE ID IN ($ids_placeholder)",
            ...$post_ids
        ));

        return ['processed' => count($post_ids)];
    }

    /**
     * Reset/clear all data created by this seeder
     * Legacy method - now just calls reset_batch to process everything
     */
    public function reset() {
        $count = $this->get_reset_count();
        if ($count > 0) {
            $this->reset_batch(0, $count);
        }
    }
}
