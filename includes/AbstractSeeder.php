<?php

namespace MCDS;

/**
 * Abstract base class for all seeders
 */
abstract class AbstractSeeder {

    /**
     * Unique key for this seeder
     */
    protected $key;

    /**
     * Display name for this seeder
     */
    protected $name;

    /**
     * Description of what this seeder does
     */
    protected $description;

    /**
     * Default number of items to seed
     */
    protected $default_count = 100;

    /**
     * Default batch size for processing
     */
    protected $default_batch_size = 50;

    /**
     * Settings fields for this seeder
     */
    protected $settings_fields = [];

    /**
     * Progress tracker instance
     */
    protected $tracker = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Set progress tracker
     */
    public function set_tracker($tracker) {
        $this->tracker = $tracker;
    }

    /**
     * Check if this seeder has been cancelled
     */
    protected function is_cancelled() {
        if (!$this->tracker) {
            return false;
        }

        $progress = $this->tracker->get($this->key);
        return $progress && $progress->status === 'cancelled';
    }

    /**
     * Initialize seeder - override in child classes
     */
    protected function init() {
        // Override in child classes to set key, name, description, etc.
    }

    /**
     * Get seeder key
     */
    public function get_key() {
        return $this->key;
    }

    /**
     * Get seeder name
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get seeder description
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get default count
     */
    public function get_default_count() {
        return $this->default_count;
    }

    /**
     * Get default batch size
     */
    public function get_default_batch_size() {
        return $this->default_batch_size;
    }

    /**
     * Get settings fields
     */
    public function get_settings_fields() {
        return array_merge([
            [
                'key' => 'count',
                'label' => __('Number of items to create', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => $this->default_count,
                'required' => true,
                'min' => 1,
                'max' => 10000
            ],
            [
                'key' => 'batch_size',
                'label' => __('Batch size', 'membercore-data-seeder'),
                'type' => 'number',
                'default' => $this->default_batch_size,
                'required' => true,
                'min' => 1,
                'max' => 200
            ]
        ], $this->settings_fields);
    }

    /**
     * Validate settings before starting
     *
     * @param array $settings
     * @return true|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_settings($settings) {
        $modified = false;

        foreach ($this->get_settings_fields() as $field) {
            if (!empty($field['required']) && empty($settings[$field['key']])) {
                return new \WP_Error(
                    'missing_field',
                    sprintf(__('Field "%s" is required', 'membercore-data-seeder'), $field['label'])
                );
            }

            if ($field['type'] === 'number' && isset($settings[$field['key']])) {
                $value = intval($settings[$field['key']]);
                // Clamp value to min/max instead of showing error
                if (!empty($field['min']) && $value < $field['min']) {
                    $settings[$field['key']] = $field['min'];
                    $modified = true;
                }
                if (!empty($field['max']) && $value > $field['max']) {
                    $settings[$field['key']] = $field['max'];
                    $modified = true;
                }
            }
        }

        // Return modified settings if we clamped any values
        if ($modified) {
            return ['settings' => $settings];
        }

        return true;
    }

    /**
     * Run a batch of seeding
     *
     * @param int $offset Current offset
     * @param int $limit Batch size
     * @param array $settings Seeder settings
     * @return array Results with 'processed' count and optional 'error' message
     */
    abstract public function seed_batch($offset, $limit, $settings);

    /**
     * Clean up before starting a new seed (optional)
     * Override in child classes if needed
     *
     * @param array $settings Seeder settings
     */
    public function before_seed($settings) {
        // Override in child classes if needed
    }

    /**
     * Clean up after completing seed (optional)
     * Override in child classes if needed
     *
     * @param array $settings Seeder settings
     */
    public function after_seed($settings) {
        // Override in child classes if needed
    }

    /**
     * Get list of seeder keys that depend on this seeder
     * When this seeder is reset, all dependent seeders will also be reset
     * Override in child classes to declare dependencies
     *
     * @return array Array of seeder keys that depend on this seeder
     */
    public function get_dependents() {
        return [];
    }

    /**
     * Reset/clear all data created by this seeder (optional)
     * Override in child classes if needed
     */
    public function reset() {
        // Override in child classes if needed
    }
}
