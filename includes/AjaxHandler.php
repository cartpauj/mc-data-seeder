<?php

namespace MCDS;

/**
 * AJAX handler for batch processing
 */
class AjaxHandler {

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

        // Register AJAX actions
        add_action('wp_ajax_mcds_start_seeder', [$this, 'start_seeder']);
        add_action('wp_ajax_mcds_process_batch', [$this, 'process_batch']);
        add_action('wp_ajax_mcds_get_status', [$this, 'get_status']);
        add_action('wp_ajax_mcds_get_global_status', [$this, 'get_global_status']);
        add_action('wp_ajax_mcds_cancel_seeder', [$this, 'cancel_seeder']);
        add_action('wp_ajax_mcds_check_reset_dependencies', [$this, 'check_reset_dependencies']);
        add_action('wp_ajax_mcds_start_reset', [$this, 'start_reset']);
        add_action('wp_ajax_mcds_process_reset_batch', [$this, 'process_reset_batch']);
        add_action('wp_ajax_mcds_reset_seeder', [$this, 'reset_seeder']);
        add_action('wp_ajax_mcds_start_reset_all', [$this, 'start_reset_all']);
        add_action('wp_ajax_mcds_process_reset_all_batch', [$this, 'process_reset_all_batch']);
        add_action('wp_ajax_mcds_reset_all', [$this, 'reset_all']);
    }

    /**
     * Start a seeder
     */
    public function start_seeder() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');
        $settings = json_decode(stripslashes($_POST['settings'] ?? '{}'), true);

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $seeder = $this->registry->get($seeder_key);
        if (!$seeder) {
            wp_send_json_error(['message' => __('Seeder not found', 'membercore-data-seeder')]);
        }

        // Validate settings
        $validation = $seeder->validate_settings($settings);
        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()]);
        }

        // Some seeders return modified settings array instead of true
        // Check if validation returned an array with settings
        if (is_array($validation) && isset($validation['settings'])) {
            $settings = $validation['settings'];
        }

        // Run before_seed hook
        try {
            $seeder->before_seed($settings);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        // Initialize progress tracking
        $total = intval($settings['count'] ?? $seeder->get_default_count());
        $batch_size = intval($settings['batch_size'] ?? $seeder->get_default_batch_size());

        $result = $this->tracker->start($seeder_key, $total, $batch_size, $settings);

        // Check if another seeder is already running
        if (is_array($result) && isset($result['error'])) {
            wp_send_json_error([
                'message' => $result['message'],
                'running_seeder' => $result['running_seeder'] ?? null
            ]);
        }

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to start seeder', 'membercore-data-seeder')]);
        }

        wp_send_json_success([
            'message' => __('Seeder started successfully', 'membercore-data-seeder'),
            'total' => $total,
            'batch_size' => $batch_size
        ]);
    }

    /**
     * Process a batch
     */
    public function process_batch() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $seeder = $this->registry->get($seeder_key);
        if (!$seeder) {
            wp_send_json_error(['message' => __('Seeder not found', 'membercore-data-seeder')]);
        }

        // Inject tracker so seeder can check for cancellation
        $seeder->set_tracker($this->tracker);

        // Get progress record
        $progress = $this->tracker->get($seeder_key);
        if (!$progress) {
            wp_send_json_error(['message' => __('Progress record not found', 'membercore-data-seeder')]);
        }

        // Check if cancelled
        if ($progress->status === 'cancelled') {
            wp_send_json_success([
                'completed' => true,
                'cancelled' => true,
                'processed' => $progress->processed,
                'total' => $progress->total,
                'percentage' => $progress->total > 0 ? round(($progress->processed / $progress->total) * 100, 2) : 0,
                'message' => __('Seeding cancelled', 'membercore-data-seeder')
            ]);
        }

        if ($progress->status !== 'running') {
            wp_send_json_error(['message' => __('Seeder is not running', 'membercore-data-seeder')]);
        }

        // Calculate batch parameters
        $offset = $progress->processed;
        $limit = min($progress->batch_size, $progress->total - $offset);

        if ($limit <= 0) {
            // Already completed
            $this->tracker->update_progress($seeder_key, $progress->total);

            // Run after_seed hook
            try {
                $seeder->after_seed($progress->settings);
            } catch (\Exception $e) {
                // Log but don't fail
                error_log('MCDS after_seed error: ' . $e->getMessage());
            }

            wp_send_json_success([
                'completed' => true,
                'processed' => $progress->total,
                'total' => $progress->total,
                'percentage' => 100,
                'message' => __('Seeding completed!', 'membercore-data-seeder')
            ]);
        }

        // Process batch
        try {
            $result = $seeder->seed_batch($offset, $limit, $progress->settings);

            if (isset($result['error'])) {
                $this->tracker->set_error($seeder_key, $result['error']);
                wp_send_json_error(['message' => $result['error']]);
            }

            $processed_count = isset($result['processed']) ? intval($result['processed']) : $limit;
            $new_processed = $offset + $processed_count;

            // Update progress
            $this->tracker->update_progress($seeder_key, $new_processed);

            $completed = $new_processed >= $progress->total;
            $percentage = round(($new_processed / $progress->total) * 100, 2);

            // If completed, run after_seed hook
            if ($completed) {
                try {
                    $seeder->after_seed($progress->settings);
                } catch (\Exception $e) {
                    error_log('MCDS after_seed error: ' . $e->getMessage());
                }
            }

            wp_send_json_success([
                'completed' => $completed,
                'processed' => $new_processed,
                'total' => $progress->total,
                'percentage' => $percentage,
                'message' => $completed
                    ? __('Seeding completed!', 'membercore-data-seeder')
                    : sprintf(__('Processed %d of %d', 'membercore-data-seeder'), $new_processed, $progress->total)
            ]);

        } catch (\Exception $e) {
            $this->tracker->set_error($seeder_key, $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Get seeder status
     */
    public function get_status() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $progress = $this->tracker->get($seeder_key);

        if (!$progress) {
            wp_send_json_success([
                'status' => 'idle',
                'processed' => 0,
                'total' => 0,
                'percentage' => 0
            ]);
        }

        $percentage = $progress->total > 0
            ? round(($progress->processed / $progress->total) * 100, 2)
            : 0;

        wp_send_json_success([
            'status' => $progress->status,
            'processed' => $progress->processed,
            'total' => $progress->total,
            'percentage' => $percentage,
            'error_message' => $progress->error_message ?? null
        ]);
    }

    /**
     * Get global status (check if any seeder is running)
     */
    public function get_global_status() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $running_seeder = $this->tracker->get_running_seeder();

        if ($running_seeder) {
            $seeder = $this->registry->get($running_seeder->seeder_key);
            $seeder_name = $seeder ? $seeder->get_name() : $running_seeder->seeder_key;

            $percentage = $running_seeder->total > 0
                ? round(($running_seeder->processed / $running_seeder->total) * 100, 2)
                : 0;

            wp_send_json_success([
                'is_running' => true,
                'seeder_key' => $running_seeder->seeder_key,
                'seeder_name' => $seeder_name,
                'processed' => $running_seeder->processed,
                'total' => $running_seeder->total,
                'percentage' => $percentage
            ]);
        } else {
            wp_send_json_success([
                'is_running' => false
            ]);
        }
    }

    /**
     * Cancel a running seeder
     */
    public function cancel_seeder() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $progress = $this->tracker->get($seeder_key);
        if (!$progress || $progress->status !== 'running') {
            wp_send_json_error(['message' => __('Seeder is not running', 'membercore-data-seeder')]);
        }

        $result = $this->tracker->cancel($seeder_key);

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to cancel seeder', 'membercore-data-seeder')]);
        }

        wp_send_json_success(['message' => __('Seeder cancelled successfully', 'membercore-data-seeder')]);
    }

    /**
     * Check if resetting a seeder will affect dependent seeders
     */
    public function check_reset_dependencies() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $seeder = $this->registry->get($seeder_key);
        if (!$seeder) {
            wp_send_json_error(['message' => __('Seeder not found', 'membercore-data-seeder')]);
        }

        // Get dependent seeders
        $dependent_keys = method_exists($seeder, 'get_dependents') ? $seeder->get_dependents() : [];
        $dependents = [];

        foreach ($dependent_keys as $dep_key) {
            $dep_seeder = $this->registry->get($dep_key);
            if ($dep_seeder) {
                // Check if the dependent seeder has data
                if (method_exists($dep_seeder, 'get_reset_count')) {
                    $count = $dep_seeder->get_reset_count();
                    if ($count > 0) {
                        $dependents[] = $dep_seeder->get_name();
                    }
                }
            }
        }

        // If dependents have data, block the reset
        if (!empty($dependents)) {
            $message = sprintf(
                __('Cannot reset %s until %s reset first.', 'membercore-data-seeder'),
                $seeder->get_name(),
                implode(' and ', $dependents)
            );
            wp_send_json_error(['message' => $message]);
        }

        wp_send_json_success(['can_reset' => true]);
    }

    /**
     * Start a reset operation (batched)
     */
    public function start_reset() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $seeder = $this->registry->get($seeder_key);
        if (!$seeder) {
            wp_send_json_error(['message' => __('Seeder not found', 'membercore-data-seeder')]);
        }

        // Check if seeder has get_reset_count method
        if (!method_exists($seeder, 'get_reset_count')) {
            // Fallback to old reset method
            try {
                $seeder->reset();
                $this->tracker->reset($seeder_key);
                wp_send_json_success(['message' => __('Seeder reset successfully', 'membercore-data-seeder')]);
            } catch (\Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
            return;
        }

        // Get count of items to reset
        $total = $seeder->get_reset_count();

        if ($total == 0) {
            wp_send_json_success([
                'completed' => true,
                'message' => __('Nothing to reset', 'membercore-data-seeder')
            ]);
            return;
        }

        // Initialize progress tracking for reset operation
        $batch_size = 500; // Process 500 items per batch (larger for faster resets)
        $result = $this->tracker->start($seeder_key . '_reset', $total, $batch_size, ['seeder_key' => $seeder_key]);

        if (is_array($result) && isset($result['error'])) {
            wp_send_json_error(['message' => $result['message']]);
        }

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to start reset', 'membercore-data-seeder')]);
        }

        wp_send_json_success([
            'message' => __('Reset started successfully', 'membercore-data-seeder'),
            'total' => $total,
            'batch_size' => $batch_size
        ]);
    }

    /**
     * Process a batch of reset operations
     */
    public function process_reset_batch() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $seeder = $this->registry->get($seeder_key);
        if (!$seeder) {
            wp_send_json_error(['message' => __('Seeder not found', 'membercore-data-seeder')]);
        }

        // Get progress record for reset operation
        $progress = $this->tracker->get($seeder_key . '_reset');
        if (!$progress) {
            wp_send_json_error(['message' => __('Reset progress record not found', 'membercore-data-seeder')]);
        }

        if ($progress->status !== 'running') {
            wp_send_json_error(['message' => __('Reset is not running', 'membercore-data-seeder')]);
        }

        // Calculate batch parameters
        $offset = $progress->processed;
        $limit = min($progress->batch_size, $progress->total - $offset);

        if ($limit <= 0) {
            // Already completed
            $this->tracker->update_progress($seeder_key . '_reset', $progress->total);
            $this->tracker->reset($seeder_key);

            wp_send_json_success([
                'completed' => true,
                'processed' => $progress->total,
                'total' => $progress->total,
                'percentage' => 100,
                'message' => __('Reset completed!', 'membercore-data-seeder')
            ]);
        }

        // Process batch
        try {
            $result = $seeder->reset_batch($offset, $limit);

            if (isset($result['error'])) {
                $this->tracker->set_error($seeder_key . '_reset', $result['error']);
                wp_send_json_error(['message' => $result['error']]);
            }

            $processed_count = isset($result['processed']) ? intval($result['processed']) : $limit;
            $new_processed = $offset + $processed_count;

            // Update progress
            $this->tracker->update_progress($seeder_key . '_reset', $new_processed);

            $completed = $new_processed >= $progress->total;
            $percentage = round(($new_processed / $progress->total) * 100, 2);

            // If completed, clear the main seeder progress
            if ($completed) {
                $this->tracker->reset($seeder_key);
            }

            wp_send_json_success([
                'completed' => $completed,
                'processed' => $new_processed,
                'total' => $progress->total,
                'percentage' => $percentage,
                'message' => $completed
                    ? __('Reset completed!', 'membercore-data-seeder')
                    : sprintf(__('Reset %d of %d', 'membercore-data-seeder'), $new_processed, $progress->total)
            ]);

        } catch (\Exception $e) {
            $this->tracker->set_error($seeder_key . '_reset', $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Reset a seeder (legacy - for seeders without batched reset)
     */
    public function reset_seeder() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $seeder_key = sanitize_text_field($_POST['seeder_key'] ?? '');

        if (empty($seeder_key)) {
            wp_send_json_error(['message' => __('Seeder key is required', 'membercore-data-seeder')]);
        }

        $seeder = $this->registry->get($seeder_key);
        if ($seeder) {
            try {
                $seeder->reset();
            } catch (\Exception $e) {
                error_log('MCDS reset error: ' . $e->getMessage());
            }
        }

        $result = $this->tracker->reset($seeder_key);

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to reset seeder', 'membercore-data-seeder')]);
        }

        wp_send_json_success(['message' => __('Seeder reset successfully', 'membercore-data-seeder')]);
    }

    /**
     * Start reset all operation (resets seeders one-by-one)
     */
    public function start_reset_all() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        // Get all seeders with data to reset
        $seeders_to_reset = [];
        foreach ($this->registry->get_all() as $seeder) {
            if (method_exists($seeder, 'get_reset_count')) {
                $count = $seeder->get_reset_count();
                if ($count > 0) {
                    $seeders_to_reset[] = [
                        'key' => $seeder->get_key(),
                        'name' => $seeder->get_name(),
                        'count' => $count
                    ];
                }
            }
        }

        if (empty($seeders_to_reset)) {
            wp_send_json_success([
                'completed' => true,
                'message' => __('Nothing to reset', 'membercore-data-seeder')
            ]);
            return;
        }

        // Store the list of seeders to process in an option
        update_option('mcds_reset_all_queue', $seeders_to_reset, false);
        update_option('mcds_reset_all_current', 0, false);

        wp_send_json_success([
            'total_seeders' => count($seeders_to_reset),
            'seeders' => $seeders_to_reset
        ]);
    }

    /**
     * Process reset all - resets one seeder at a time
     */
    public function process_reset_all_batch() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        $queue = get_option('mcds_reset_all_queue', []);
        $current_index = get_option('mcds_reset_all_current', 0);

        if (empty($queue) || $current_index >= count($queue)) {
            // All done - clean up
            delete_option('mcds_reset_all_queue');
            delete_option('mcds_reset_all_current');
            $this->tracker->reset_all();

            wp_send_json_success([
                'completed' => true,
                'message' => __('All seeders reset successfully', 'membercore-data-seeder')
            ]);
            return;
        }

        $current_seeder = $queue[$current_index];
        $seeder_key = $current_seeder['key'];
        $seeder = $this->registry->get($seeder_key);

        if (!$seeder) {
            // Skip this seeder and move to next
            update_option('mcds_reset_all_current', $current_index + 1, false);
            wp_send_json_success([
                'completed' => false,
                'current_seeder' => $current_index + 1,
                'total_seeders' => count($queue),
                'seeder_name' => $current_seeder['name'],
                'message' => sprintf(__('Skipping %s (seeder not found)', 'membercore-data-seeder'), $current_seeder['name'])
            ]);
            return;
        }

        // Process one batch of the current seeder
        $batch_size = 500; // Process 500 items per batch (larger for faster resets)
        $progress_key = $seeder_key . '_reset_all';
        $progress = $this->tracker->get($progress_key);

        if (!$progress) {
            // Initialize this seeder's reset
            $this->tracker->start($progress_key, $current_seeder['count'], $batch_size, ['seeder_key' => $seeder_key]);
            $progress = $this->tracker->get($progress_key);
        }

        $offset = $progress->processed;
        $limit = min($batch_size, $progress->total - $offset);

        if ($limit <= 0 || $progress->status === 'completed') {
            // This seeder is done, move to next
            $this->tracker->reset($progress_key);
            $this->tracker->reset($seeder_key);
            update_option('mcds_reset_all_current', $current_index + 1, false);

            $next_index = $current_index + 1;
            if ($next_index >= count($queue)) {
                // All done
                delete_option('mcds_reset_all_queue');
                delete_option('mcds_reset_all_current');
                $this->tracker->reset_all();

                wp_send_json_success([
                    'completed' => true,
                    'message' => __('All seeders reset successfully', 'membercore-data-seeder')
                ]);
            } else {
                wp_send_json_success([
                    'completed' => false,
                    'current_seeder' => $next_index + 1,
                    'total_seeders' => count($queue),
                    'seeder_name' => $queue[$next_index]['name'],
                    'message' => sprintf(__('Starting reset of %s', 'membercore-data-seeder'), $queue[$next_index]['name'])
                ]);
            }
            return;
        }

        // Process one batch
        try {
            $result = $seeder->reset_batch($offset, $limit);
            $processed_count = isset($result['processed']) ? intval($result['processed']) : $limit;

            // If nothing was processed but we're not at the end, something is wrong
            // Force progression to avoid infinite loop
            if ($processed_count === 0 && $offset < $progress->total) {
                error_log("MCDS: Reset batch returned 0 items processed at offset $offset. Forcing progression.");
                $processed_count = min($limit, $progress->total - $offset);
            }

            $new_processed = $offset + $processed_count;

            $this->tracker->update_progress($progress_key, $new_processed);

            $seeder_percentage = round(($new_processed / $progress->total) * 100, 2);

            wp_send_json_success([
                'completed' => false,
                'current_seeder' => $current_index + 1,
                'total_seeders' => count($queue),
                'seeder_name' => $current_seeder['name'],
                'seeder_processed' => $new_processed,
                'seeder_total' => $progress->total,
                'seeder_percentage' => $seeder_percentage,
                'message' => sprintf(
                    __('Resetting %s: %d of %d (%s%%)', 'membercore-data-seeder'),
                    $current_seeder['name'],
                    $new_processed,
                    $progress->total,
                    $seeder_percentage
                )
            ]);

        } catch (\Exception $e) {
            error_log('MCDS reset error: ' . $e->getMessage());
            // Skip this seeder and move to next
            $this->tracker->reset($progress_key);
            update_option('mcds_reset_all_current', $current_index + 1, false);
            wp_send_json_success([
                'completed' => false,
                'current_seeder' => $current_index + 1,
                'total_seeders' => count($queue),
                'message' => sprintf(__('Error resetting %s, skipping...', 'membercore-data-seeder'), $current_seeder['name'])
            ]);
        }
    }

    /**
     * Reset all seeders (legacy - falls back to sequential reset)
     */
    public function reset_all() {
        check_ajax_referer('mcds_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'membercore-data-seeder')]);
        }

        // Call reset on all seeders
        foreach ($this->registry->get_all() as $seeder) {
            try {
                $seeder->reset();
            } catch (\Exception $e) {
                error_log('MCDS reset error: ' . $e->getMessage());
            }
        }

        $result = $this->tracker->reset_all();

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to reset all seeders', 'membercore-data-seeder')]);
        }

        wp_send_json_success(['message' => __('All seeders reset successfully', 'membercore-data-seeder')]);
    }
}
