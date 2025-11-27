<?php

namespace MCDS\Seeders;

use MCDS\AbstractSeeder;

/**
 * MemberCore Memberships and Groups Seeder
 */
class MembershipSeeder extends AbstractSeeder {

    /**
     * Standalone membership names (role/status-based, distinct from tier names)
     */
    private $standalone_names = [
        'VIP Access',
        'All Access Pass',
        'Founder\'s Club',
        'Inner Circle',
        'Insider Access',
        'Executive Membership',
        'Premier Pass',
        'Master Access',
        'Ambassador Program',
        'Champion Membership',
        'Patron Access',
        'Supporter Club',
        'Advocate Membership',
        'Partner Access',
        'Contributor Pass',
        'Specialist Membership',
        'Expert Access',
        'Authority Membership',
        'Leader\'s Circle',
        'Innovator Pass',
        'Pioneer Membership',
        'Trailblazer Access',
        'Visionary Club',
        'Maverick Membership',
        'Architect Pass'
    ];

    /**
     * Group tier naming sets (randomly selected per group)
     */
    private $tier_names = [
        ['Basic', 'Plus', 'Pro', 'Elite'],
        ['Bronze', 'Silver', 'Gold', 'Platinum'],
        ['Starter', 'Growth', 'Scale', 'Enterprise'],
        ['Essential', 'Professional', 'Business', 'Premium'],
        ['Intro', 'Standard', 'Advanced'],
        ['Launch', 'Growth', 'Scale'],
        ['Core', 'Enhanced', 'Ultimate']
    ];

    /**
     * Initialize seeder
     */
    protected function init() {
        $prefix = \MCDS\PluginConfig::get_prefix();
        $plugin_name = \MCDS\PluginConfig::get_active_plugin_name();
        $this->key = $prefix . '_memberships';
        $this->name = sprintf(__('%s Memberships & Groups', 'membercore-data-seeder'), $plugin_name);
        $this->description = sprintf(__('Creates %s memberships and membership groups with upgrade paths.', 'membercore-data-seeder'), $plugin_name);
        $this->default_count = 1; // We'll calculate this dynamically
        $this->default_batch_size = 1; // Process all in one batch

        // Add custom settings fields
        $this->settings_fields = [
            [
                'key' => 'memberships_count',
                'label' => __('Number of Standalone Memberships', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => 10,
                'min' => 0,
                'max' => 25,
                'required' => false
            ],
            [
                'key' => 'groups_count',
                'label' => __('Number of Groups', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => 2,
                'min' => 0,
                'max' => 5,
                'required' => false
            ],
            [
                'key' => 'price_model',
                'label' => __('Pricing Model', 'membercore-data-seeder'),
                'type' => 'select',
                'default' => 'varied',
                'options' => [
                    'varied' => __('Varied Pricing (random prices and billing periods)', 'membercore-data-seeder'),
                    'monthly' => __('All Monthly', 'membercore-data-seeder'),
                    'yearly' => __('All Yearly', 'membercore-data-seeder'),
                    'lifetime' => __('All Lifetime', 'membercore-data-seeder')
                ],
                'required' => true
            ]
        ];
    }

    /**
     * Get list of seeders that depend on memberships
     * Subscriptions depend on memberships, so resetting memberships should also reset subscriptions
     */
    public function get_dependents() {
        $prefix = \MCDS\PluginConfig::get_prefix();
        return [$prefix . '_subscriptions'];
    }

    /**
     * Override to hide count and batch_size fields
     */
    public function get_settings_fields() {
        return $this->settings_fields;
    }

    /**
     * Override to inject calculated count and batch_size
     */
    public function validate_settings($settings) {
        // Check if the active plugin is installed
        if (!\MCDS\PluginConfig::is_active_plugin_installed()) {
            $plugin_name = \MCDS\PluginConfig::get_active_plugin_name();
            return new \WP_Error(
                'plugin_not_active',
                sprintf(__('%s plugin is not installed or activated. Please install and activate %s before running this seeder.', 'membercore-data-seeder'), $plugin_name, $plugin_name)
            );
        }

        // Calculate total based on settings
        $memberships_count = intval($settings['memberships_count'] ?? 10);
        $groups_count = intval($settings['groups_count'] ?? 2);

        // Require at least one item
        if ($memberships_count === 0 && $groups_count === 0) {
            return new \WP_Error(
                'no_items',
                __('You must create at least one membership or group.', 'membercore-data-seeder')
            );
        }

        // Inject count and batch_size for the framework
        // count = 1 because we process everything in a single batch
        $settings['count'] = 1;
        $settings['batch_size'] = 1;

        // Run parent validation
        $result = parent::validate_settings($settings);

        // If parent validation passed and we modified settings, return them
        if ($result === true) {
            return ['settings' => $settings];
        }

        return $result;
    }

    /**
     * Calculate total count based on settings (used internally)
     */
    private function calculate_total($settings) {
        $memberships_count = intval($settings['memberships_count'] ?? 10);
        $groups_count = intval($settings['groups_count'] ?? 2);

        // Each group creates 1 group post + 3-4 membership posts
        // We'll estimate 3.5 memberships per group on average
        $group_items = $groups_count * 4.5; // 1 group + 3.5 memberships

        return $memberships_count + $group_items;
    }

    /**
     * Run a batch of seeding
     */
    public function seed_batch($offset, $limit, $settings) {
        global $wpdb;

        $memberships_count = intval($settings['memberships_count'] ?? 10);
        $groups_count = intval($settings['groups_count'] ?? 2);
        $price_model = $settings['price_model'] ?? 'varied';
        $time = current_time('mysql');
        $time_gmt = current_time('mysql', 1);

        // Shuffle standalone names to ensure variety
        $available_names = $this->standalone_names;
        shuffle($available_names);

        $post_values = [];
        $meta_values = [];
        $group_ids_map = []; // Will store group post IDs after insertion
        $group_memberships = []; // Will store group membership data
        $standalone_memberships = []; // Will store standalone membership data

        // Step 1: Create all group posts
        for ($i = 0; $i < $groups_count; $i++) {
            // Check if seeder has been cancelled
            if ($this->is_cancelled()) {
                return [
                    'processed' => 0,
                    'cancelled' => true
                ];
            }

            $group_title = sprintf(__('Group %d', 'membercore-data-seeder'), $i + 1);
            $group_name = sanitize_title($group_title);

            $post_values[] = $wpdb->prepare(
                "(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                1, // post_author
                $time,
                $time_gmt,
                '', // post_content
                $group_title,
                '', // post_excerpt
                'publish',
                'closed', // comment_status
                'closed', // ping_status
                '', // post_password
                $group_name,
                \MCDS\PluginConfig::get_post_type('group') // post_type
            );
        }

        // Insert groups and get their IDs
        if (!empty($post_values) && $groups_count > 0) {
            $sql = "INSERT INTO {$wpdb->posts}
                    (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
                     post_status, comment_status, ping_status, post_password, post_name, post_type)
                    VALUES " . implode(', ', $post_values);

            $result = $wpdb->query($sql);

            if ($result === false) {
                return [
                    'processed' => 0,
                    'error' => $wpdb->last_error ?: __('Failed to insert groups', 'membercore-data-seeder')
                ];
            }

            // Get inserted group IDs
            $first_group_id = $wpdb->insert_id;
            for ($i = 0; $i < $groups_count; $i++) {
                $group_ids_map[$i] = $first_group_id + $i;

                // Add group metadata
                $group_id = $group_ids_map[$i];
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $group_id, '_mcds_seeded', '1');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $group_id, '_mcds_seeder_key', $this->key);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $group_id, \MCDS\PluginConfig::get_meta_key('group_is_upgrade_path'), '1');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $group_id, \MCDS\PluginConfig::get_meta_key('group_upgrade_path_reset_period'), '1');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $group_id, \MCDS\PluginConfig::get_meta_key('group_pricing_page_disabled'), '0');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $group_id, \MCDS\PluginConfig::get_meta_key('group_disable_change_plan_popup'), '0');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $group_id, \MCDS\PluginConfig::get_meta_key('group_theme'), 'minimal_gray_horizontal.css');
            }
        }

        // Step 2: Create memberships for groups
        $post_values = []; // Reset for memberships

        for ($i = 0; $i < $groups_count; $i++) {
            // Check if seeder has been cancelled
            if ($this->is_cancelled()) {
                return [
                    'processed' => 0,
                    'cancelled' => true
                ];
            }

            $group_id = $group_ids_map[$i];
            $tier_set = $this->tier_names[array_rand($this->tier_names)];
            $tier_count = count($tier_set);

            for ($tier_index = 0; $tier_index < $tier_count; $tier_index++) {
                $membership_title = sprintf('Group %d - %s', $i + 1, $tier_set[$tier_index]);
                $membership_name = sanitize_title($membership_title);

                // Get pricing for this tier
                $pricing = $this->get_group_tier_pricing($price_model, $tier_index, $tier_count);

                $post_values[] = $wpdb->prepare(
                    "(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                    1, // post_author
                    $time,
                    $time_gmt,
                    '', // post_content
                    $membership_title,
                    '', // post_excerpt
                    'publish',
                    'closed',
                    'closed',
                    '',
                    $membership_name,
                    \MCDS\PluginConfig::get_post_type('product')
                );

                // Store pricing info to add as meta after insert
                $group_memberships[] = [
                    'group_id' => $group_id,
                    'order' => $tier_index,
                    'price' => $pricing['price'],
                    'period' => $pricing['period'],
                    'period_type' => $pricing['period_type']
                ];
            }
        }

        // Step 3: Create standalone memberships
        for ($i = 0; $i < $memberships_count; $i++) {
            // Check if seeder has been cancelled
            if ($this->is_cancelled()) {
                return [
                    'processed' => 0,
                    'cancelled' => true
                ];
            }

            $membership_title = $available_names[$i] ?? sprintf('Membership %d', $i + 1);
            $membership_name = sanitize_title($membership_title);

            // Get pricing
            $pricing = $this->get_standalone_pricing($price_model);

            $post_values[] = $wpdb->prepare(
                "(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                1, // post_author
                $time,
                $time_gmt,
                '',
                $membership_title,
                '',
                'publish',
                'closed',
                'closed',
                '',
                $membership_name,
                \MCDS\PluginConfig::get_post_type('product')
            );

            $standalone_memberships[] = [
                'price' => $pricing['price'],
                'period' => $pricing['period'],
                'period_type' => $pricing['period_type']
            ];
        }

        // Insert all memberships
        if (!empty($post_values)) {
            $sql = "INSERT INTO {$wpdb->posts}
                    (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
                     post_status, comment_status, ping_status, post_password, post_name, post_type)
                    VALUES " . implode(', ', $post_values);

            $result = $wpdb->query($sql);

            if ($result === false) {
                return [
                    'processed' => 0,
                    'error' => $wpdb->last_error ?: __('Failed to insert memberships', 'membercore-data-seeder')
                ];
            }

            // Get first membership ID
            $first_membership_id = $wpdb->insert_id;
            $current_id = $first_membership_id;

            // Add metadata for group memberships
            foreach ($group_memberships as $membership) {
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, '_mcds_seeded', '1');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, '_mcds_seeder_key', $this->key);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_price'), $membership['price']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_period'), $membership['period']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_period_type'), $membership['period_type']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_signup_button_text'), 'Sign Up');
                $meta_values[] = $wpdb->prepare("(%d, %s, %d)", $current_id, \MCDS\PluginConfig::get_meta_key('group_id'), $membership['group_id']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %d)", $current_id, \MCDS\PluginConfig::get_meta_key('group_order'), $membership['order']);
                $current_id++;
            }

            // Add metadata for standalone memberships
            foreach ($standalone_memberships as $membership) {
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, '_mcds_seeded', '1');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, '_mcds_seeder_key', $this->key);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_price'), $membership['price']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_period'), $membership['period']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_period_type'), $membership['period_type']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $current_id, \MCDS\PluginConfig::get_meta_key('product_signup_button_text'), 'Sign Up');
                $meta_values[] = $wpdb->prepare("(%d, %s, %d)", $current_id, \MCDS\PluginConfig::get_meta_key('group_id'), 0);
                $meta_values[] = $wpdb->prepare("(%d, %s, %d)", $current_id, \MCDS\PluginConfig::get_meta_key('group_order'), 0);
                $current_id++;
            }
        }

        // Insert all postmeta
        if (!empty($meta_values)) {
            $sql = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(', ', $meta_values);
            $result = $wpdb->query($sql);

            if ($result === false) {
                return [
                    'processed' => 0,
                    'error' => $wpdb->last_error ?: __('Failed to insert post metadata', 'membercore-data-seeder')
                ];
            }
        }

        // Clear all transient caches so new items show up immediately
        $prefix = \MCDS\PluginConfig::get_prefix();
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $prefix . '_all_models_for_class%',
            '_transient_timeout_' . $prefix . '_all_models_for_class%'
        ));

        // Return processed = 1 to match our count setting of 1
        // (We process everything in a single batch)
        return ['processed' => 1];
    }

    /**
     * Get pricing for a group tier membership
     */
    private function get_group_tier_pricing($price_model, $tier_index, $tier_count) {
        // 4-tier pricing ranges
        $pricing_4tier = [
            'monthly' => [
                ['min' => 9, 'max' => 19],
                ['min' => 29, 'max' => 39],
                ['min' => 49, 'max' => 69],
                ['min' => 89, 'max' => 129]
            ],
            'yearly' => [
                ['min' => 99, 'max' => 149],
                ['min' => 249, 'max' => 349],
                ['min' => 449, 'max' => 599],
                ['min' => 799, 'max' => 999]
            ],
            'lifetime' => [
                ['min' => 199, 'max' => 299],
                ['min' => 399, 'max' => 599],
                ['min' => 799, 'max' => 999],
                ['min' => 1299, 'max' => 1599]
            ]
        ];

        // 3-tier pricing ranges
        $pricing_3tier = [
            'monthly' => [
                ['min' => 9, 'max' => 19],
                ['min' => 39, 'max' => 59],
                ['min' => 89, 'max' => 129]
            ],
            'yearly' => [
                ['min' => 99, 'max' => 199],
                ['min' => 349, 'max' => 499],
                ['min' => 799, 'max' => 999]
            ],
            'lifetime' => [
                ['min' => 199, 'max' => 399],
                ['min' => 599, 'max' => 899],
                ['min' => 1299, 'max' => 1599]
            ]
        ];

        $model = $price_model;
        if ($price_model === 'varied') {
            $model = ['monthly', 'yearly', 'lifetime'][rand(0, 2)];
        }

        $ranges = $tier_count === 4 ? $pricing_4tier[$model] : $pricing_3tier[$model];
        $range = $ranges[$tier_index];
        $price = rand($range['min'], $range['max']);

        $period_type = $model === 'monthly' ? 'months' : ($model === 'yearly' ? 'years' : 'lifetime');

        return [
            'price' => $price,
            'period' => 1,
            'period_type' => $period_type
        ];
    }

    /**
     * Get pricing for a standalone membership
     */
    private function get_standalone_pricing($price_model) {
        if ($price_model === 'varied') {
            $model = ['monthly', 'yearly', 'lifetime'][rand(0, 2)];
        } else {
            $model = $price_model;
        }

        switch ($model) {
            case 'monthly':
                $price = rand(9, 49);
                $period_type = 'months';
                break;
            case 'yearly':
                $price = rand(99, 499);
                $period_type = 'years';
                break;
            case 'lifetime':
                $price = rand(199, 999);
                $period_type = 'lifetime';
                break;
        }

        return [
            'price' => $price,
            'period' => 1,
            'period_type' => $period_type
        ];
    }

    /**
     * Clean up before starting a new seed
     */
    public function before_seed($settings) {
        // Optional: Clean up previous seeded data if needed
    }

    /**
     * Reset/clear all data created by this seeder
     */
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
     * Legacy reset method - now just calls reset_batch to process everything
     */
    public function reset() {
        $count = $this->get_reset_count();
        if ($count > 0) {
            $this->reset_batch(0, $count);
        }
    }
}
