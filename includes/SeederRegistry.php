<?php

namespace MCDS;

/**
 * Registry for managing seeders
 */
class SeederRegistry {

    /**
     * Registered seeders
     */
    private $seeders = [];

    /**
     * Register a seeder
     *
     * @param AbstractSeeder $seeder
     * @return bool
     */
    public function register($seeder) {
        if (!($seeder instanceof AbstractSeeder)) {
            return false;
        }

        $key = $seeder->get_key();
        if (empty($key)) {
            return false;
        }

        $this->seeders[$key] = $seeder;
        return true;
    }

    /**
     * Get all registered seeders
     *
     * @return AbstractSeeder[]
     */
    public function get_all() {
        return $this->seeders;
    }

    /**
     * Get a specific seeder by key
     *
     * @param string $key
     * @return AbstractSeeder|null
     */
    public function get($key) {
        return isset($this->seeders[$key]) ? $this->seeders[$key] : null;
    }

    /**
     * Check if a seeder is registered
     *
     * @param string $key
     * @return bool
     */
    public function has($key) {
        return isset($this->seeders[$key]);
    }

    /**
     * Unregister a seeder
     *
     * @param string $key
     * @return bool
     */
    public function unregister($key) {
        if (!$this->has($key)) {
            return false;
        }

        unset($this->seeders[$key]);
        return true;
    }
}
