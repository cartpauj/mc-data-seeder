<?php

namespace MCDS;

/**
 * Admin page handler
 */
class Admin {

    /**
     * Seeder registry
     */
    private $registry;

    /**
     * Progress tracker
     */
    private $tracker;

    /**
     * Constructor
     */
    public function __construct($registry) {
        $this->registry = $registry;
        $this->tracker = new ProgressTracker();

        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        add_menu_page(
            __('MC Data Seeder', 'membercore-data-seeder'),
            __('MC Data Seeder', 'membercore-data-seeder'),
            'manage_options',
            'mcds-seeder',
            [$this, 'render_page'],
            'dashicons-database-import',
            80
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_mcds-seeder') {
            return;
        }

        wp_enqueue_style(
            'mcds-admin',
            MCDS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            MCDS_VERSION
        );

        wp_enqueue_script(
            'mcds-admin',
            MCDS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            MCDS_VERSION,
            true
        );

        wp_localize_script('mcds-admin', 'mcdsAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mcds_nonce'),
            'strings' => [
                'confirm_reset' => __('Are you sure you want to reset this seeder? This will clear all progress.', 'membercore-data-seeder'),
                'confirm_reset_all' => __('Are you sure you want to reset ALL seeders? This will clear all progress and may delete seeded data.', 'membercore-data-seeder'),
                'error' => __('An error occurred', 'membercore-data-seeder'),
                'starting' => __('Starting...', 'membercore-data-seeder'),
                'processing' => __('Processing...', 'membercore-data-seeder'),
                'completed' => __('Completed!', 'membercore-data-seeder'),
                'error_occurred' => __('Error occurred', 'membercore-data-seeder'),
                'resetting' => __('Resetting...', 'membercore-data-seeder'),
                'saving' => __('Saving...', 'membercore-data-seeder'),
                'save_plugin' => __('Save Plugin Selection', 'membercore-data-seeder')
            ]
        ]);
    }

    /**
     * Render admin page
     */
    public function render_page() {
        $seeders = $this->registry->get_all();
        $progress_records = [];

        foreach ($seeders as $seeder) {
            $progress = $this->tracker->get($seeder->get_key());
            if ($progress) {
                $progress_records[$seeder->get_key()] = $progress;
            }
        }

        // Get plugin configuration
        $active_plugin = \MCDS\PluginConfig::get_active_plugin();
        $installed_plugins = \MCDS\PluginConfig::detect_installed_plugins();
        $all_plugins = \MCDS\PluginConfig::get_plugins();

        ?>
        <div class="wrap mcds-admin-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="mcds-intro">
                <p><?php _e('Use this tool to seed your database with test data. Each seeder can be configured and run independently. Progress is tracked so you can safely navigate away and return later.', 'membercore-data-seeder'); ?></p>
            </div>

            <div class="mcds-plugin-selector">
                <h2><?php _e('Target Plugin', 'membercore-data-seeder'); ?></h2>
                <p class="description"><?php _e('Select which plugin you want to seed data for. The seeder will use the appropriate prefixes and database tables for the selected plugin.', 'membercore-data-seeder'); ?></p>

                <?php if (empty($installed_plugins)): ?>
                    <div class="notice notice-error inline">
                        <p><?php _e('No supported plugins detected. Please install MemberCore, MemberPress, or WishList LMS to use this seeder.', 'membercore-data-seeder'); ?></p>
                    </div>
                <?php else: ?>
                    <select id="mcds-plugin-select" name="mcds_plugin">
                        <?php foreach ($all_plugins as $key => $plugin): ?>
                            <option
                                value="<?php echo esc_attr($key); ?>"
                                <?php selected($active_plugin, $key); ?>
                                <?php disabled(!in_array($key, $installed_plugins)); ?>
                            >
                                <?php echo esc_html($plugin['name']); ?>
                                <?php if (!in_array($key, $installed_plugins)): ?>
                                    <?php _e('(Not Installed)', 'membercore-data-seeder'); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="mcds-save-plugin" class="button button-primary">
                        <?php _e('Save Plugin Selection', 'membercore-data-seeder'); ?>
                    </button>
                    <span class="mcds-plugin-saved" style="display:none; color: #46b450; margin-left: 10px;">
                        <?php _e('✓ Saved', 'membercore-data-seeder'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($seeders)): ?>
                <div class="notice notice-warning">
                    <p><?php _e('No seeders are currently registered. Seeders can be registered using the <code>mcds_register_seeders</code> action hook.', 'membercore-data-seeder'); ?></p>
                </div>
            <?php else: ?>

                <div class="mcds-actions">
                    <button type="button" class="button mcds-reset-all">
                        <?php _e('Reset All Seeders', 'membercore-data-seeder'); ?>
                    </button>
                </div>

                <div class="mcds-seeders">
                    <?php foreach ($seeders as $seeder): ?>
                        <?php
                        $key = $seeder->get_key();
                        $progress = $progress_records[$key] ?? null;
                        $status = $progress ? $progress->status : 'idle';
                        $percentage = 0;

                        if ($progress && $progress->total > 0) {
                            $percentage = round(($progress->processed / $progress->total) * 100, 2);
                        }
                        ?>
                        <div class="mcds-seeder" data-seeder-key="<?php echo esc_attr($key); ?>" data-status="<?php echo esc_attr($status); ?>">
                            <div class="mcds-seeder-header">
                                <h2><?php echo esc_html($seeder->get_name()); ?></h2>
                                <?php if ($seeder->get_description()): ?>
                                    <p class="description"><?php echo esc_html($seeder->get_description()); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="mcds-seeder-body">
                                <form class="mcds-seeder-form">
                                    <?php foreach ($seeder->get_settings_fields() as $field): ?>
                                        <div class="mcds-field">
                                            <label for="<?php echo esc_attr($key . '_' . $field['key']); ?>">
                                                <?php echo esc_html($field['label']); ?>
                                                <?php if (!empty($field['required'])): ?>
                                                    <span class="required">*</span>
                                                <?php endif; ?>
                                            </label>

                                            <?php if ($field['type'] === 'number'): ?>
                                                <input
                                                    type="number"
                                                    id="<?php echo esc_attr($key . '_' . $field['key']); ?>"
                                                    name="<?php echo esc_attr($field['key']); ?>"
                                                    value="<?php echo esc_attr($field['default'] ?? ''); ?>"
                                                    <?php if (!empty($field['min'])): ?>min="<?php echo esc_attr($field['min']); ?>"<?php endif; ?>
                                                    <?php if (!empty($field['max'])): ?>max="<?php echo esc_attr($field['max']); ?>"<?php endif; ?>
                                                    <?php if (!empty($field['required'])): ?>required<?php endif; ?>
                                                />
                                            <?php elseif ($field['type'] === 'text'): ?>
                                                <input
                                                    type="text"
                                                    id="<?php echo esc_attr($key . '_' . $field['key']); ?>"
                                                    name="<?php echo esc_attr($field['key']); ?>"
                                                    value="<?php echo esc_attr($field['default'] ?? ''); ?>"
                                                    <?php if (!empty($field['required'])): ?>required<?php endif; ?>
                                                />
                                            <?php elseif ($field['type'] === 'select'): ?>
                                                <select
                                                    id="<?php echo esc_attr($key . '_' . $field['key']); ?>"
                                                    name="<?php echo esc_attr($field['key']); ?>"
                                                    <?php if (!empty($field['required'])): ?>required<?php endif; ?>
                                                >
                                                    <?php foreach ($field['options'] as $option_value => $option_label): ?>
                                                        <option
                                                            value="<?php echo esc_attr($option_value); ?>"
                                                            <?php selected($option_value, $field['default'] ?? ''); ?>
                                                        >
                                                            <?php echo esc_html($option_label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="mcds-actions">
                                        <button type="submit" class="button button-primary mcds-start">
                                            <?php _e('Start Seeding', 'membercore-data-seeder'); ?>
                                        </button>
                                        <button type="button" class="button mcds-reset">
                                            <?php _e('Reset', 'membercore-data-seeder'); ?>
                                        </button>
                                    </div>
                                </form>

                                <div class="mcds-progress-container" <?php if ($status === 'idle' || $status === 'completed' || $status === 'cancelled'): ?>style="display:none;"<?php endif; ?>>
                                    <div class="mcds-progress-info">
                                        <span class="mcds-progress-text">
                                            <?php if ($progress): ?>
                                                <?php echo sprintf(__('%d of %d', 'membercore-data-seeder'), $progress->processed, $progress->total); ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="mcds-progress-percentage"><?php echo $percentage; ?>%</span>
                                    </div>
                                    <div class="mcds-progress-bar">
                                        <div class="mcds-progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <div class="mcds-progress-status <?php echo esc_attr($status); ?>">
                                        <?php
                                        switch ($status) {
                                            case 'running':
                                                _e('Processing...', 'membercore-data-seeder');
                                                break;
                                            case 'completed':
                                                _e('Completed!', 'membercore-data-seeder');
                                                break;
                                            case 'cancelled':
                                                _e('Cancelled', 'membercore-data-seeder');
                                                break;
                                            case 'error':
                                                echo esc_html($progress->error_message ?? __('An error occurred', 'membercore-data-seeder'));
                                                break;
                                        }
                                        ?>
                                    </div>
                                    <div class="mcds-progress-actions">
                                        <button type="button" class="button mcds-stop" <?php if ($status !== 'running'): ?>style="display:none;"<?php endif; ?>>
                                            <?php _e('Stop', 'membercore-data-seeder'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
