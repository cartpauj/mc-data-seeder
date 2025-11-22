<?php
/**
 * Register default seeders
 *
 * This file demonstrates how to register seeders with the framework.
 * Add your own seeders here or use the 'mcds_register_seeders' action hook.
 */

// Register seeders
add_action('mcds_register_seeders', function($registry) {
    // Register the example seeder
    $registry->register(new \MCDS\Seeders\ExampleSeeder());

    // Register the user seeder
    $registry->register(new \MCDS\Seeders\UserSeeder());

    // Register the membership seeder
    $registry->register(new \MCDS\Seeders\MembershipSeeder());

    // Register the subscription seeder
    $registry->register(new \MCDS\Seeders\SubscriptionSeeder());

    // Add more seeders here as they are created:
    // $registry->register(new \MCDS\Seeders\CircleSeeder());
    // etc...
}, 10, 1);
