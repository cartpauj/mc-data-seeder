# MemberCore Data Seeder

A comprehensive WordPress plugin framework for seeding test data with batch processing, progress tracking, and AJAX-powered UI.

## Features

- **Batch Processing**: Processes data in configurable batches (50-100 items at a time) to prevent timeouts
- **Progress Tracking**: Persists progress in the database so users can navigate away and resume later
- **AJAX-Powered UI**: Real-time progress bars and status updates without page refreshes
- **Extensible Framework**: Easy-to-use abstract class for creating custom seeders
- **Settings Management**: Each seeder can define custom settings fields
- **Error Handling**: Robust error handling with detailed error messages
- **Reset Functionality**: Individual or bulk reset of seeded data

## Installation

1. Copy the `membercore-data-seeder` plugin directory to your WordPress plugins folder
2. Activate the plugin through the WordPress admin
3. Navigate to **Data Seeder** in the admin menu

## Creating a Custom Seeder

### Step 1: Create Your Seeder Class

Create a new PHP file in `includes/seeders/` (e.g., `UserSeeder.php`):

```php
<?php

namespace MCDS\Seeders;

use MCDS\AbstractSeeder;

class UserSeeder extends AbstractSeeder {

    protected function init() {
        // Required: Set unique key for this seeder
        $this->key = 'wp_users';

        // Required: Set display name
        $this->name = __('WordPress Users', 'membercore-data-seeder');

        // Optional: Set description
        $this->description = __('Creates WordPress users with random data.', 'membercore-data-seeder');

        // Optional: Set defaults
        $this->default_count = 100;
        $this->default_batch_size = 50;

        // Optional: Add custom settings fields
        $this->settings_fields = [
            [
                'key' => 'user_role',
                'label' => __('User Role', 'membercore-data-seeder'),
                'type' => 'select',
                'default' => 'subscriber',
                'options' => [
                    'subscriber' => __('Subscriber', 'membercore-data-seeder'),
                    'contributor' => __('Contributor', 'membercore-data-seeder'),
                    'author' => __('Author', 'membercore-data-seeder')
                ],
                'required' => true
            ]
        ];
    }

    /**
     * Process a batch of items
     *
     * @param int $offset Current offset (0-based)
     * @param int $limit Number of items to process in this batch
     * @param array $settings Settings configured by the user
     * @return array Must return ['processed' => int] or ['processed' => int, 'error' => string]
     */
    public function seed_batch($offset, $limit, $settings) {
        $processed = 0;

        for ($i = 0; $i < $limit; $i++) {
            $index = $offset + $i + 1;

            // Create your data here
            $user_id = wp_create_user(
                'user_' . $index,
                wp_generate_password(),
                'user' . $index . '@example.com'
            );

            if (is_wp_error($user_id)) {
                return [
                    'processed' => $processed,
                    'error' => $user_id->get_error_message()
                ];
            }

            // Track what was created by this seeder
            update_user_meta($user_id, '_mcds_seeder_key', $this->key);

            $processed++;
        }

        return ['processed' => $processed];
    }

    /**
     * Optional: Run before seeding starts
     */
    public function before_seed($settings) {
        // Clean up old data, prepare database, etc.
    }

    /**
     * Optional: Run after seeding completes
     */
    public function after_seed($settings) {
        // Final cleanup, notifications, etc.
    }

    /**
     * Optional: Reset all data created by this seeder
     */
    public function reset() {
        $users = get_users([
            'meta_key' => '_mcds_seeder_key',
            'meta_value' => $this->key
        ]);

        foreach ($users as $user) {
            wp_delete_user($user->ID);
        }
    }
}
```

### Step 2: Register Your Seeder

In `includes/register-seeders.php`, add your seeder:

```php
add_action('mcds_register_seeders', function($registry) {
    $registry->register(new \MCDS\Seeders\UserSeeder());
}, 10, 1);
```

### Step 3: Use Your Seeder

1. Go to **Data Seeder** in the WordPress admin
2. Configure the settings for your seeder
3. Click "Start Seeding"
4. Watch the progress bar as it processes in real-time

## Settings Field Types

The framework supports multiple field types:

### Number Field
```php
[
    'key' => 'count',
    'label' => __('Number of items', 'membercore-data-seeder'),
    'type' => 'number',
    'default' => 100,
    'required' => true,
    'min' => 1,
    'max' => 10000
]
```

### Text Field
```php
[
    'key' => 'prefix',
    'label' => __('Username prefix', 'membercore-data-seeder'),
    'type' => 'text',
    'default' => 'user_',
    'required' => false
]
```

### Select Field
```php
[
    'key' => 'status',
    'label' => __('Status', 'membercore-data-seeder'),
    'type' => 'select',
    'default' => 'active',
    'options' => [
        'active' => __('Active', 'membercore-data-seeder'),
        'inactive' => __('Inactive', 'membercore-data-seeder')
    ],
    'required' => true
]
```

## Best Practices

### 1. Track Created Data
Always add metadata to track what was created by your seeder:

```php
update_post_meta($post_id, '_mcds_seeder_key', $this->key);
update_user_meta($user_id, '_mcds_seeder_key', $this->key);
```

### 2. Implement Reset Method
Make it easy to clean up test data:

```php
public function reset() {
    global $wpdb;

    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_mcds_seeder_key' AND meta_value = %s",
        $this->key
    ));

    foreach ($post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }
}
```

### 3. Handle Errors Gracefully
Return error information when something goes wrong:

```php
if (is_wp_error($result)) {
    return [
        'processed' => $processed,
        'error' => $result->get_error_message()
    ];
}
```

### 4. Use Appropriate Batch Sizes
- Small items (users, simple posts): 50-100 per batch
- Medium items (posts with metadata): 25-50 per batch
- Large items (posts with images, complex relationships): 10-25 per batch

### 5. Validate Settings
The framework automatically validates required fields and min/max values, but you can add custom validation:

```php
public function validate_settings($settings) {
    $parent_validation = parent::validate_settings($settings);
    if (is_wp_error($parent_validation)) {
        return $parent_validation;
    }

    // Custom validation
    if ($settings['some_field'] > 1000) {
        return new \WP_Error('invalid', 'Value too large');
    }

    return true;
}
```

## Architecture

### Classes

- **Plugin**: Main plugin class that initializes all components
- **Installer**: Handles plugin activation/deactivation and database setup
- **AbstractSeeder**: Base class for all seeders
- **SeederRegistry**: Manages registered seeders
- **ProgressTracker**: Handles database persistence of progress
- **AjaxHandler**: Processes AJAX requests for batch processing
- **Admin**: Renders the admin interface

### Database

The plugin creates a `wp_mcds_progress` table to track seeding progress:

- `seeder_key`: Unique identifier for the seeder
- `total`: Total number of items to create
- `processed`: Number of items processed so far
- `batch_size`: Size of each batch
- `status`: Current status (pending, running, completed, error)
- `settings`: JSON-encoded settings
- `started_at`: When seeding started
- `completed_at`: When seeding completed
- `error_message`: Error message if failed

### AJAX Flow

1. User clicks "Start Seeding"
2. JavaScript calls `mcds_start_seeder` AJAX action
3. Backend validates settings and initializes progress tracking
4. JavaScript begins calling `mcds_process_batch` in a loop
5. Each batch processes 50-100 items
6. Progress bar updates after each batch
7. Process continues until all items are created
8. `after_seed` hook runs when complete

## Planned Seeders

The following seeders will be implemented in future updates:

- **UserSeeder**: WordPress users with usermeta
- **MembershipSeeder**: MemberCore memberships and groups
- **SubscriptionSeeder**: User subscriptions with transaction history
- **CircleSeeder**: MemberCore circles
- **DirectorySeeder**: Member directories
- **ProfileSeeder**: User profiles
- **PostSeeder**: Posts with comments, likes, and reported content

## Troubleshooting

### Progress Not Saving
Check that the database table was created properly:
```sql
SELECT * FROM wp_mcds_progress;
```

### Timeouts
Reduce the batch size in your seeder settings.

### JavaScript Errors
Check browser console for errors and ensure scripts are enqueued properly.

## Support

For issues and questions, please open an issue on the GitHub repository.
