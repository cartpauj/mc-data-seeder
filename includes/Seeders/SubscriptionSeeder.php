<?php

namespace MCDS\Seeders;

use MCDS\AbstractSeeder;

/**
 * MemberCore Subscriptions and Transactions Seeder
 *
 * Creates subscriptions and transactions for seeded users only.
 * Respects upgrade path group constraints (one membership per upgrade path group).
 */
class SubscriptionSeeder extends AbstractSeeder {

    /**
     * Get the membership seeder key for the current plugin
     */
    private function get_membership_seeder_key() {
        $prefix = \MCDS\PluginConfig::get_prefix();
        return $prefix . '_memberships';
    }

    /**
     * Initialize seeder
     */
    protected function init() {
        $prefix = \MCDS\PluginConfig::get_prefix();
        $plugin_name = \MCDS\PluginConfig::get_active_plugin_name();
        $this->key = $prefix . '_subscriptions';
        $this->name = sprintf(__('%s Subscriptions & Transactions', 'membercore-data-seeder'), $plugin_name);
        $this->description = __('Creates subscriptions and transactions for seeded users. One-time/lifetime memberships only create transactions. Recurring memberships create subscriptions with realistic transaction history. Respects upgrade path group constraints.', 'membercore-data-seeder');
        $this->default_count = 100; // Will be overridden with actual user count
        $this->default_batch_size = 100; // Process 100 users at a time

        // Add custom settings fields
        $this->settings_fields = [
            [
                'key' => 'min_subscriptions_per_user',
                'label' => __('Minimum Subscriptions Per User', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => 1,
                'min' => 1,
                'max' => 10,
                'required' => true
            ],
            [
                'key' => 'max_subscriptions_per_user',
                'label' => __('Maximum Subscriptions Per User', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => 3,
                'min' => 1,
                'max' => 10,
                'required' => true
            ],
            [
                'key' => 'status_distribution',
                'label' => __('Status Distribution', 'membercore-data-seeder'),
                'type' => 'select',
                'default' => 'mostly_active',
                'options' => [
                    'all_active' => __('All Active', 'membercore-data-seeder'),
                    'mostly_active' => __('Mostly Active (80% active, 10% cancelled, 10% suspended)', 'membercore-data-seeder'),
                    'varied' => __('Varied (60% active, 20% cancelled, 20% suspended)', 'membercore-data-seeder'),
                ],
                'required' => true
            ],
            [
                'key' => 'date_range_days',
                'label' => __('Date Range (days back)', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => 365,
                'min' => 1,
                'max' => 1825,
                'required' => true
            ],
            [
                'key' => 'batch_size',
                'label' => __('Users per batch', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => 100,
                'min' => 1,
                'max' => 100,
                'required' => true,
                'description' => __('Number of users to process in each batch. Lower values show progress more frequently.', 'membercore-data-seeder')
            ]
        ];
    }

    /**
     * Override to show custom fields (including batch_size, but not count)
     */
    public function get_settings_fields() {
        return $this->settings_fields;
    }

    /**
     * Override to inject calculated count based on seeded users
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

        // Validate min/max relationship
        $min = intval($settings['min_subscriptions_per_user'] ?? 1);
        $max = intval($settings['max_subscriptions_per_user'] ?? 3);

        if ($max < $min) {
            return new \WP_Error(
                'invalid_min_max',
                __('Maximum subscriptions per user must be greater than or equal to minimum.', 'membercore-data-seeder')
            );
        }

        // Check if seeded users exist
        global $wpdb;
        $seeded_users_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
            '_mcds_seeder_key',
            'wp_users'
        ));

        if ($seeded_users_count == 0) {
            return new \WP_Error(
                'no_seeded_users',
                __('No seeded users found. Please run the WordPress Users seeder first.', 'membercore-data-seeder')
            );
        }

        // Check if seeded memberships exist
        $seeded_memberships_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
             WHERE meta_key = %s AND meta_value = %s
             AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s)",
            '_mcds_seeder_key',
            'membercore_memberships',
            'membercoreproduct',
            'publish'
        ));

        if ($seeded_memberships_count == 0) {
            return new \WP_Error(
                'no_seeded_memberships',
                __('No seeded memberships found. Please run the Memberships seeder first.', 'membercore-data-seeder')
            );
        }

        // Set count to the number of seeded users (progress will be based on users processed)
        $settings['count'] = intval($seeded_users_count);

        // Use the batch_size from settings, or default to 100
        if (!isset($settings['batch_size']) || $settings['batch_size'] < 1) {
            $settings['batch_size'] = 100;
        }

        // Clamp batch_size to valid range instead of showing error
        if ($settings['batch_size'] < 1) {
            $settings['batch_size'] = 1;
        }
        if ($settings['batch_size'] > 100) {
            $settings['batch_size'] = 100;
        }

        // Return modified settings array so AjaxHandler can use updated count
        return [
            'settings' => $settings
        ];
    }

    /**
     * Run a batch of seeding
     * @param int $offset - User offset (which user to start from)
     * @param int $limit - Number of users to process in this batch
     * @param array $settings - Seeder settings
     */
    public function seed_batch($offset, $limit, $settings) {
        global $wpdb;

        $min_subs = intval($settings['min_subscriptions_per_user'] ?? 1);
        $max_subs = intval($settings['max_subscriptions_per_user'] ?? 3);
        $status_distribution = $settings['status_distribution'] ?? 'mostly_active';
        $date_range_days = intval($settings['date_range_days'] ?? 365);

        // Get MemberCore table names
        $table_prefix = \MCDS\PluginConfig::get_table_prefix();
        $subscriptions_table = $wpdb->prefix . $table_prefix . '_subscriptions';
        $transactions_table = $wpdb->prefix . $table_prefix . '_transactions';
        $transaction_meta_table = $wpdb->prefix . $table_prefix . '_transaction_meta';

        // Step 1: Get batch of seeded users (using LIMIT and OFFSET)
        $seeded_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = %s AND meta_value = %s
             ORDER BY user_id
             LIMIT %d OFFSET %d",
            '_mcds_seeder_key',
            'wp_users',
            $limit,
            $offset
        ));

        if (empty($seeded_users)) {
            return [
                'processed' => 0,
                'error' => __('No seeded users found in this batch', 'membercore-data-seeder')
            ];
        }

        // Step 2: Get all seeded memberships with group info
        $memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID as product_id,
                    COALESCE(pm_group.meta_value, '0') as group_id,
                    COALESCE(pm_is_upgrade.meta_value, '0') as is_upgrade_path,
                    pm_price.meta_value as price,
                    pm_period.meta_value as period,
                    pm_period_type.meta_value as period_type
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_seeded ON p.ID = pm_seeded.post_id
                AND pm_seeded.meta_key = '_mcds_seeder_key'
                AND pm_seeded.meta_value = %s
             LEFT JOIN {$wpdb->postmeta} pm_group ON p.ID = pm_group.post_id
                AND pm_group.meta_key = %s
             LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id
                AND pm_price.meta_key = %s
             LEFT JOIN {$wpdb->postmeta} pm_period ON p.ID = pm_period.post_id
                AND pm_period.meta_key = %s
             LEFT JOIN {$wpdb->postmeta} pm_period_type ON p.ID = pm_period_type.post_id
                AND pm_period_type.meta_key = %s
             LEFT JOIN {$wpdb->postmeta} pm_is_upgrade ON pm_group.meta_value = pm_is_upgrade.post_id
                AND pm_is_upgrade.meta_key = %s
             WHERE p.post_type = %s AND p.post_status = %s",
            $this->get_membership_seeder_key(),
            \MCDS\PluginConfig::get_meta_key('group_id'),
            \MCDS\PluginConfig::get_meta_key('product_price'),
            \MCDS\PluginConfig::get_meta_key('product_period'),
            \MCDS\PluginConfig::get_meta_key('product_period_type'),
            \MCDS\PluginConfig::get_meta_key('group_is_upgrade_path'),
            \MCDS\PluginConfig::get_post_type('product'),
            'publish'
        ));

        if (empty($memberships)) {
            return [
                'processed' => 0,
                'error' => __('No seeded memberships found', 'membercore-data-seeder')
            ];
        }

        // Step 3: Organize memberships by upgrade path groups and type (recurring vs one-time)
        $upgrade_path_groups = [];
        $standalone_memberships = [];

        foreach ($memberships as $membership) {
            $group_id = intval($membership->group_id);
            $is_upgrade_path = intval($membership->is_upgrade_path);

            // Determine if this is a recurring membership
            $membership->is_recurring = ($membership->period_type !== 'lifetime');

            if ($group_id > 0 && $is_upgrade_path) {
                if (!isset($upgrade_path_groups[$group_id])) {
                    $upgrade_path_groups[$group_id] = [];
                }
                $upgrade_path_groups[$group_id][] = $membership;
            } else {
                $standalone_memberships[] = $membership;
            }
        }

        // Step 4: Prepare bulk inserts
        $subscription_values = [];
        $transaction_values = [];
        $transaction_meta_values = [];
        $subscriptions_to_create = [];
        $one_time_transactions = []; // For lifetime/one-time memberships

        $current_time = current_time('mysql');
        $current_timestamp = current_time('timestamp');

        $users_processed = 0;
        foreach ($seeded_users as $user_id) {
            // Check if seeder has been cancelled
            if ($this->is_cancelled()) {
                // Return early with the number of users processed so far
                return [
                    'processed' => $users_processed,
                    'cancelled' => true
                ];
            }

            // Randomly determine how many subscriptions/memberships this user gets
            $num_memberships = rand($min_subs, $max_subs);

            // Track which upgrade path groups are already assigned
            $assigned_upgrade_groups = [];

            // Create memberships for this user
            for ($i = 0; $i < $num_memberships; $i++) {
                // Build available membership pool
                $available_memberships = [];

                // Add standalone memberships (always available)
                $available_memberships = array_merge($available_memberships, $standalone_memberships);

                // Add memberships from unassigned upgrade path groups
                foreach ($upgrade_path_groups as $group_id => $group_memberships) {
                    if (!in_array($group_id, $assigned_upgrade_groups)) {
                        // Pick one random membership from this group
                        $available_memberships[] = $group_memberships[array_rand($group_memberships)];
                    }
                }

                // If no memberships available, skip
                if (empty($available_memberships)) {
                    break;
                }

                // Randomly select a membership
                $membership = $available_memberships[array_rand($available_memberships)];

                // Mark upgrade path group as used if applicable
                $group_id = intval($membership->group_id);
                $is_upgrade_path = intval($membership->is_upgrade_path);
                if ($group_id > 0 && $is_upgrade_path) {
                    $assigned_upgrade_groups[] = $group_id;
                }

                // Random created_at date within range
                $days_back = rand(0, $date_range_days);
                $created_timestamp = $current_timestamp - ($days_back * DAY_IN_SECONDS);
                $created_at = date('Y-m-d H:i:s', $created_timestamp);

                // Check if this is a recurring or one-time membership
                if ($membership->is_recurring) {
                    // RECURRING MEMBERSHIP - Create subscription
                    // Determine status based on distribution
                    $status = $this->get_random_status($status_distribution);

                    // Generate unique subscription ID
                    $subscr_id = 'mc-sub-' . uniqid() . '-' . $user_id . '-' . $membership->product_id;

                    // Store subscription data for later
                    $subscriptions_to_create[] = [
                        'user_id' => $user_id,
                        'product_id' => $membership->product_id,
                        'price' => $membership->price,
                        'period' => $membership->period,
                        'period_type' => $membership->period_type,
                        'status' => $status,
                        'created_at' => $created_at,
                        'created_timestamp' => $created_timestamp,
                        'subscr_id' => $subscr_id
                    ];
                } else {
                    // ONE-TIME/LIFETIME MEMBERSHIP - Create transaction only (no subscription)
                    $trans_num = 'mc-txn-' . uniqid() . '-' . $user_id . '-' . $membership->product_id;

                    // One-time purchases are always complete
                    $txn_status = 'complete';

                    // Lifetime memberships never expire
                    $expires_at = '0000-00-00 00:00:00';

                    $one_time_transactions[] = [
                        'user_id' => $user_id,
                        'product_id' => $membership->product_id,
                        'price' => $membership->price,
                        'created_at' => $created_at,
                        'trans_num' => $trans_num,
                        'status' => $txn_status,
                        'expires_at' => $expires_at
                    ];
                }
            }

            // Increment users processed counter
            $users_processed++;
        }

        // Step 5: Bulk insert subscriptions
        if (!empty($subscriptions_to_create)) {
            foreach ($subscriptions_to_create as $sub_data) {
                $subscription_values[] = $wpdb->prepare(
                    "(%d, %d, %s, %s, %s, %d, %s, %s, %s)",
                    $sub_data['user_id'],
                    $sub_data['product_id'],
                    $sub_data['subscr_id'],
                    $sub_data['price'],
                    $sub_data['price'], // total
                    $sub_data['period'],
                    $sub_data['period_type'],
                    $sub_data['status'],
                    $sub_data['created_at']
                );
            }

            $sql = "INSERT INTO {$subscriptions_table}
                    (user_id, product_id, subscr_id, price, total, period, period_type, status, created_at)
                    VALUES " . implode(', ', $subscription_values);

            $result = $wpdb->query($sql);

            if ($result === false) {
                return [
                    'processed' => 0,
                    'error' => $wpdb->last_error ?: __('Failed to insert subscriptions', 'membercore-data-seeder')
                ];
            }

            // Get first subscription ID
            $first_subscription_id = $wpdb->insert_id;

            // Step 6: Create transactions for each subscription (realistic count based on time elapsed)
            foreach ($subscriptions_to_create as $idx => $sub_data) {
                $subscription_id = $first_subscription_id + $idx;
                $base_created_timestamp = $sub_data['created_timestamp'];

                // Calculate how many periods have elapsed since subscription creation
                $period_seconds = $this->get_period_in_seconds($sub_data['period'], $sub_data['period_type']);
                $time_elapsed = $current_timestamp - $base_created_timestamp;
                $periods_elapsed = max(1, floor($time_elapsed / $period_seconds));

                // For cancelled subscriptions, determine when they were cancelled
                $cancelled_after_period = 0;
                if ($sub_data['status'] === 'cancelled') {
                    // Cancelled somewhere between period 1 and periods_elapsed
                    $cancelled_after_period = rand(1, max(1, $periods_elapsed - 1));
                }

                // Create transactions for each period that has elapsed
                for ($t = 0; $t <= $periods_elapsed; $t++) {
                    // Calculate transaction timestamp respecting day-of-month for months/years
                    $txn_timestamp = $this->calculate_period_timestamp(
                        $base_created_timestamp,
                        $t,
                        $sub_data['period'],
                        $sub_data['period_type']
                    );

                    // Don't create transactions in the future
                    if ($txn_timestamp > $current_timestamp) {
                        break;
                    }

                    $txn_created_at = date('Y-m-d H:i:s', $txn_timestamp);

                    // Calculate expires_at based on period (use next period timestamp)
                    $expires_timestamp = $this->calculate_period_timestamp(
                        $base_created_timestamp,
                        $t + 1,
                        $sub_data['period'],
                        $sub_data['period_type']
                    );
                    $expires_at = date('Y-m-d H:i:s', $expires_timestamp);

                    // Transaction status
                    if ($sub_data['status'] === 'cancelled' && $t >= $cancelled_after_period) {
                        // Failed transaction when cancelled
                        $txn_status = 'failed';
                    } elseif ($sub_data['status'] === 'suspended' && $t === $periods_elapsed) {
                        // Last transaction pending if suspended
                        $txn_status = 'pending';
                    } else {
                        $txn_status = 'complete';
                    }

                    $trans_num = 'mc-txn-' . uniqid() . '-' . $subscription_id . '-' . $t;

                    $transaction_values[] = $wpdb->prepare(
                        "(%s, %s, %d, %d, %d, %s, %s, %s, %s)",
                        $sub_data['price'],
                        $sub_data['price'], // total
                        $sub_data['user_id'],
                        $sub_data['product_id'],
                        $subscription_id,
                        $trans_num,
                        $txn_status,
                        $txn_created_at,
                        $expires_at
                    );
                }
            }

            // Step 7: Bulk insert transactions for subscriptions
            if (!empty($transaction_values)) {
                $sql = "INSERT INTO {$transactions_table}
                        (amount, total, user_id, product_id, subscription_id, trans_num, status, created_at, expires_at)
                        VALUES " . implode(', ', $transaction_values);

                $result = $wpdb->query($sql);

                if ($result === false) {
                    return [
                        'processed' => 0,
                        'error' => $wpdb->last_error ?: __('Failed to insert subscription transactions', 'membercore-data-seeder')
                    ];
                }

                // Get first transaction ID
                $first_transaction_id = $wpdb->insert_id;
                $transaction_count = count($transaction_values);

                // Step 8: Add metadata for subscription transactions
                for ($i = 0; $i < $transaction_count; $i++) {
                    $transaction_id = $first_transaction_id + $i;
                    $transaction_meta_values[] = $wpdb->prepare("(%d, %s, %s)", $transaction_id, '_mcds_seeder_key', $this->key);
                }

                if (!empty($transaction_meta_values)) {
                    $sql = "INSERT INTO {$transaction_meta_table} (transaction_id, meta_key, meta_value)
                            VALUES " . implode(', ', $transaction_meta_values);
                    $wpdb->query($sql);
                }
            }
        }

        // Step 9: Bulk insert one-time transactions (for lifetime memberships)
        if (!empty($one_time_transactions)) {
            $one_time_values = [];

            foreach ($one_time_transactions as $txn_data) {
                $one_time_values[] = $wpdb->prepare(
                    "(%s, %s, %d, %d, %s, %s, %s, %s)",
                    $txn_data['price'],
                    $txn_data['price'], // total
                    $txn_data['user_id'],
                    $txn_data['product_id'],
                    $txn_data['trans_num'],
                    $txn_data['status'],
                    $txn_data['created_at'],
                    $txn_data['expires_at']
                );
            }

            $sql = "INSERT INTO {$transactions_table}
                    (amount, total, user_id, product_id, trans_num, status, created_at, expires_at)
                    VALUES " . implode(', ', $one_time_values);

            $result = $wpdb->query($sql);

            if ($result === false) {
                return [
                    'processed' => 0,
                    'error' => $wpdb->last_error ?: __('Failed to insert one-time transactions', 'membercore-data-seeder')
                ];
            }

            // Get first one-time transaction ID
            $first_onetime_id = $wpdb->insert_id;
            $onetime_count = count($one_time_values);

            // Add metadata for one-time transactions
            $onetime_meta_values = [];
            for ($i = 0; $i < $onetime_count; $i++) {
                $transaction_id = $first_onetime_id + $i;
                $onetime_meta_values[] = $wpdb->prepare("(%d, %s, %s)", $transaction_id, '_mcds_seeder_key', $this->key);
            }

            if (!empty($onetime_meta_values)) {
                $sql = "INSERT INTO {$transaction_meta_table} (transaction_id, meta_key, meta_value)
                        VALUES " . implode(', ', $onetime_meta_values);
                $wpdb->query($sql);
            }
        }

        // Step 10: Update members table using MemberCore's built-in function
        // Call MemberCore's update_member_data_static for each user in the batch
        $config = \MCDS\PluginConfig::get_active_config();
        if ($config && class_exists($config['class'])) {
            $class_name = $config['class'];
            if (method_exists($class_name, 'update_member_data_static')) {
                foreach ($seeded_users as $user_id) {
                    $class_name::update_member_data_static($user_id);
                }
            }
        }

        // Return the number of users processed in this batch
        return ['processed' => count($seeded_users)];
    }

    /**
     * Get random status based on distribution setting
     */
    private function get_random_status($distribution) {
        $rand = rand(1, 100);

        switch ($distribution) {
            case 'all_active':
                return 'active';

            case 'mostly_active':
                if ($rand <= 80) return 'active';
                if ($rand <= 90) return 'cancelled';
                return 'suspended';

            case 'varied':
                if ($rand <= 60) return 'active';
                if ($rand <= 80) return 'cancelled';
                return 'suspended';

            default:
                return 'active';
        }
    }

    /**
     * Convert period and period_type to seconds
     */
    private function get_period_in_seconds($period, $period_type) {
        $period = intval($period);

        switch ($period_type) {
            case 'days':
                return $period * DAY_IN_SECONDS;
            case 'weeks':
                return $period * WEEK_IN_SECONDS;
            case 'months':
                return $period * MONTH_IN_SECONDS;
            case 'years':
                return $period * YEAR_IN_SECONDS;
            case 'lifetime':
                return YEAR_IN_SECONDS * 100; // Treat lifetime as 100 years
            default:
                return MONTH_IN_SECONDS;
        }
    }

    /**
     * Calculate timestamp for a specific period iteration, respecting day-of-month for months/years
     *
     * @param int $base_timestamp The subscription creation timestamp
     * @param int $period_number Which period (0 = first transaction, 1 = second, etc.)
     * @param int $period The period interval (e.g., 1, 3, 6)
     * @param string $period_type The period type (days, weeks, months, years)
     * @return int The calculated timestamp
     */
    private function calculate_period_timestamp($base_timestamp, $period_number, $period, $period_type) {
        $period = intval($period);
        $period_number = intval($period_number);

        switch ($period_type) {
            case 'days':
                return $base_timestamp + ($period_number * $period * DAY_IN_SECONDS);

            case 'weeks':
                return $base_timestamp + ($period_number * $period * WEEK_IN_SECONDS);

            case 'months':
                // Use DateTime to properly handle month boundaries and maintain day-of-month
                $base_date = new \DateTime('@' . $base_timestamp);
                $base_date->setTimezone(new \DateTimeZone(wp_timezone_string()));
                $original_day = intval($base_date->format('d'));

                $date = new \DateTime('@' . $base_timestamp);
                $date->setTimezone(new \DateTimeZone(wp_timezone_string()));
                $date->modify('+' . ($period_number * $period) . ' months');

                // If the day changed due to month overflow (e.g., Jan 31 + 1 month = Mar 3),
                // set it to the last day of the target month
                $resulting_day = intval($date->format('d'));
                if ($resulting_day < $original_day) {
                    // Day overflowed, go back to last day of previous month
                    $date->modify('last day of previous month');
                }

                return $date->getTimestamp();

            case 'years':
                // Use DateTime to properly handle leap years and maintain day-of-month
                $date = new \DateTime('@' . $base_timestamp);
                $date->setTimezone(new \DateTimeZone(wp_timezone_string()));

                // Check if base date is Feb 29 (leap day)
                $base_date = new \DateTime('@' . $base_timestamp);
                $base_date->setTimezone(new \DateTimeZone(wp_timezone_string()));
                $is_leap_day = ($base_date->format('m-d') === '02-29');

                $date->modify('+' . ($period_number * $period) . ' years');

                // Special handling for Feb 29 signups: if result is Mar 1, change to Feb 28
                if ($is_leap_day && $date->format('m-d') === '03-01') {
                    $date->modify('-1 day');
                }

                return $date->getTimestamp();

            case 'lifetime':
                // Lifetime subscriptions don't recur, but for consistency:
                return $base_timestamp + ($period_number * YEAR_IN_SECONDS * 100);

            default:
                // Default to monthly behavior
                $date = new \DateTime('@' . $base_timestamp);
                $date->setTimezone(new \DateTimeZone(wp_timezone_string()));
                $date->modify('+' . ($period_number * $period) . ' months');
                return $date->getTimestamp();
        }
    }

    /**
     * Get count of items to reset (for progress tracking)
     */
    public function get_reset_count() {
        global $wpdb;

        $table_prefix = \MCDS\PluginConfig::get_table_prefix();
        $transaction_meta_table = $wpdb->prefix . $table_prefix . '_transaction_meta';

        // Count affected users (we'll reset one user at a time for progress)
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT t.user_id)
             FROM {$transactions_table} t
             INNER JOIN {$transaction_meta_table} tm ON t.id = tm.transaction_id
             WHERE tm.meta_key = %s AND tm.meta_value = %s",
            '_mcds_seeder_key',
            $this->key
        ));

        return intval($count);
    }

    /**
     * Reset/clear data in batches
     *
     * @param int $offset Batch offset (user number to start from)
     * @param int $limit Batch size (number of users to process)
     * @return array Results with 'processed' count
     */
    public function reset_batch($offset, $limit) {
        global $wpdb;

        $table_prefix = \MCDS\PluginConfig::get_table_prefix();
        $transaction_meta_table = $wpdb->prefix . $table_prefix . '_transaction_meta';
        $transactions_table = $wpdb->prefix . $table_prefix . '_transactions';
        $subscriptions_table = $wpdb->prefix . $table_prefix . '_subscriptions';

        // Get batch of affected users (always use offset 0 since we're deleting as we go)
        $affected_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT t.user_id
             FROM {$transactions_table} t
             INNER JOIN {$transaction_meta_table} tm ON t.id = tm.transaction_id
             WHERE tm.meta_key = %s AND tm.meta_value = %s
             ORDER BY t.user_id
             LIMIT %d",
            '_mcds_seeder_key',
            $this->key,
            $limit
        ));

        if (empty($affected_users)) {
            return ['processed' => 0];
        }

        // Get transaction IDs for these users
        $user_ids_placeholder = implode(',', array_fill(0, count($affected_users), '%d'));
        $transaction_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT t.id
             FROM {$transactions_table} t
             INNER JOIN {$transaction_meta_table} tm ON t.id = tm.transaction_id
             WHERE tm.meta_key = %s AND tm.meta_value = %s
             AND t.user_id IN ($user_ids_placeholder)",
            '_mcds_seeder_key',
            $this->key,
            ...$affected_users
        ));

        if (!empty($transaction_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($transaction_ids), '%d'));

            // Get subscription IDs from these transactions
            $subscription_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT subscription_id FROM {$transactions_table}
                 WHERE id IN ($ids_placeholder) AND subscription_id IS NOT NULL",
                ...$transaction_ids
            ));

            // Delete transaction meta
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$transaction_meta_table} WHERE transaction_id IN ($ids_placeholder)",
                ...$transaction_ids
            ));

            // Delete transactions
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$transactions_table} WHERE id IN ($ids_placeholder)",
                ...$transaction_ids
            ));

            // Delete subscriptions
            if (!empty($subscription_ids)) {
                $sub_ids_placeholder = implode(',', array_fill(0, count($subscription_ids), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$subscriptions_table} WHERE id IN ($sub_ids_placeholder)",
                    ...$subscription_ids
                ));
            }

            // Sync members table for affected users
            $config = \MCDS\PluginConfig::get_active_config();
            if ($config && class_exists($config['class'])) {
                $class_name = $config['class'];
                if (method_exists($class_name, 'update_member_data_static')) {
                    foreach ($affected_users as $user_id) {
                        $class_name::update_member_data_static($user_id);
                    }
                }
            }
        }

        return ['processed' => count($affected_users)];
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
