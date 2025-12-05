<?php

namespace MCDS;

/**
 * Progress tracker for database persistence
 */
class ProgressTracker {

    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mcds_progress';
    }

    /**
     * Create or update progress record
     *
     * @param string $seeder_key
     * @param array $data
     * @return bool|int
     */
    public function save($seeder_key, $data) {
        global $wpdb;

        // Serialize settings if it's an array
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        // Check if record exists
        $existing = $this->get($seeder_key);

        if ($existing) {
            // Update existing record - only update provided fields
            $result = $wpdb->update(
                $this->table_name,
                $data,
                ['seeder_key' => $seeder_key],
                $this->get_format($data),
                ['%s']
            );
            return $result !== false;
        } else {
            // Insert new record - apply defaults
            $defaults = [
                'total' => 0,
                'processed' => 0,
                'batch_size' => 50,
                'status' => 'pending',
                'settings' => '[]',
                'started_at' => null,
                'completed_at' => null,
                'error_message' => null
            ];

            $data = wp_parse_args($data, $defaults);
            $data['seeder_key'] = $seeder_key;

            $result = $wpdb->insert(
                $this->table_name,
                $data,
                $this->get_format($data)
            );
            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Get progress record
     *
     * @param string $seeder_key
     * @return object|null
     */
    public function get($seeder_key) {
        global $wpdb;

        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE seeder_key = %s",
            $seeder_key
        ));

        if ($record && !empty($record->settings)) {
            $record->settings = json_decode($record->settings, true);
        }

        return $record;
    }

    /**
     * Get all progress records
     *
     * @param array $args Query args
     * @return array
     */
    public function get_all($args = []) {
        global $wpdb;

        $defaults = [
            'status' => null,
            'orderby' => 'id',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);

        $where = '';
        if (!empty($args['status'])) {
            $where = $wpdb->prepare("WHERE status = %s", $args['status']);
        }

        $orderby = in_array($args['orderby'], ['id', 'seeder_key', 'status', 'started_at', 'completed_at'])
            ? $args['orderby']
            : 'id';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $records = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY {$orderby} {$order}"
        );

        foreach ($records as $record) {
            if (!empty($record->settings)) {
                $record->settings = json_decode($record->settings, true);
            }
        }

        return $records;
    }

    /**
     * Check if any seeder is currently running
     *
     * @return object|null Returns the running seeder record or null if none running
     */
    public function get_running_seeder() {
        global $wpdb;

        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE status = %s LIMIT 1",
            'running'
        ));

        if ($record && !empty($record->settings)) {
            $record->settings = json_decode($record->settings, true);
        }

        return $record;
    }

    /**
     * Start a seeder
     *
     * @param string $seeder_key
     * @param int $total
     * @param int $batch_size
     * @param array $settings
     * @return bool|array Returns true on success, or array with error info on failure
     */
    public function start($seeder_key, $total, $batch_size, $settings = []) {
        // Check if any seeder is already running
        $running_seeder = $this->get_running_seeder();

        if ($running_seeder && $running_seeder->seeder_key !== $seeder_key) {
            return [
                'error' => true,
                'message' => sprintf(
                    __('Cannot start seeder. Another seeder (%s) is currently running.', 'membercore-data-seeder'),
                    $running_seeder->seeder_key
                ),
                'running_seeder' => $running_seeder->seeder_key
            ];
        }

        $result = $this->save($seeder_key, [
            'total' => $total,
            'processed' => 0,
            'batch_size' => $batch_size,
            'status' => 'running',
            'settings' => $settings,
            'started_at' => current_time('mysql'),
            'completed_at' => null,
            'error_message' => null
        ]);

        return $result ? true : false;
    }

    /**
     * Update progress
     *
     * @param string $seeder_key
     * @param int $processed
     * @return bool
     */
    public function update_progress($seeder_key, $processed) {
        global $wpdb;

        $record = $this->get($seeder_key);
        if (!$record) {
            return false;
        }

        // Don't update status if already cancelled
        if ($record->status === 'cancelled') {
            return $this->save($seeder_key, [
                'processed' => $processed
            ]);
        }

        $status = $processed >= $record->total ? 'completed' : 'running';
        $completed_at = $processed >= $record->total ? current_time('mysql') : null;

        return $this->save($seeder_key, [
            'processed' => $processed,
            'status' => $status,
            'completed_at' => $completed_at
        ]);
    }

    /**
     * Set error status
     *
     * @param string $seeder_key
     * @param string $error_message
     * @return bool
     */
    public function set_error($seeder_key, $error_message) {
        return $this->save($seeder_key, [
            'status' => 'error',
            'error_message' => $error_message,
            'completed_at' => current_time('mysql')
        ]);
    }

    /**
     * Cancel a running seeder
     *
     * @param string $seeder_key
     * @return bool
     */
    public function cancel($seeder_key) {
        return $this->save($seeder_key, [
            'status' => 'cancelled',
            'completed_at' => current_time('mysql')
        ]);
    }

    /**
     * Reset progress for a seeder
     *
     * @param string $seeder_key
     * @return bool
     */
    public function reset($seeder_key) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            ['seeder_key' => $seeder_key],
            ['%s']
        ) !== false;
    }

    /**
     * Reset all progress
     *
     * @return bool
     */
    public function reset_all() {
        global $wpdb;

        return $wpdb->query("TRUNCATE TABLE {$this->table_name}") !== false;
    }

    /**
     * Get format array for wpdb
     *
     * @param array $data
     * @return array
     */
    private function get_format($data) {
        $format = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'total':
                case 'processed':
                case 'batch_size':
                    $format[] = '%d';
                    break;
                default:
                    $format[] = '%s';
                    break;
            }
        }
        return $format;
    }
}
