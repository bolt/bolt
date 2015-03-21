<?php

use Codeception\Util\Fixtures;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Bootstrap for Codeception tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */

// User IDs
Fixtures::add('users', array(
    'admin' => array(
        'username'    => 'admin',
        'password'    => 'topsecret',
        'email'       => 'admin@example.com',
        'displayname' => 'Admin Person'
    ),
    'editor' => array(
        'username'    => 'editor',
        'password'    => 'nomoresecrets',
        'email'       => 'editor@example.com',
        'displayname' => 'Editor Person'
    ),
    'manager' => array(
        'username'    => 'manager',
        'password'    => 'cantkeepsecrets',
        'email'       => 'manager@example.com',
        'displayname' => 'Manager Person'
    ),
    'developer' => array(
        'username'    => 'developer',
        'password'    => '~n0Tne1k&nGu3$$',
        'email'       => 'developer@example.com',
        'displayname' => 'Developer Person'
    )
));

// Set up a test-specific app/config/
$configs = ['config.yml', 'contenttypes.yml', 'menu.yml', 'permissions.yml',  'routing.yml', 'taxonomy.yml'];
foreach ($configs as $config) {
    if (file_exists(PROJECT_ROOT . "/app/config/$config")) {
        if (!file_exists(PROJECT_ROOT . "/app/config/$config.codeception-backup")) {
            rename(PROJECT_ROOT . "/app/config/$config", PROJECT_ROOT. "/app/config/$config.codeception-backup");
        } else {
            unlink(PROJECT_ROOT . "/app/config/$config");
        }
    }
}

// Back up the Sqlite DB if it exists
if (file_exists(PROJECT_ROOT . '/app/database/bolt.db') && !file_exists(PROJECT_ROOT . '/app/database/bolt.db.codeception-backup')) {
    rename(PROJECT_ROOT . '/app/database/bolt.db', PROJECT_ROOT. '/app/database/bolt.db.codeception-backup');
} elseif (file_exists(PROJECT_ROOT . '/app/database/bolt.db')) {
    unlink(PROJECT_ROOT . '/app/database/bolt.db');
}

// Install the local extension
$fs = new Filesystem();
$fs->mirror(CODECEPTION_ROOT . '/_data/extensions/local/', PROJECT_ROOT . '/extensions/local/', null, array('override' => true, 'delete' => true));

// Empty the cache
system('php ' . NUT_PATH . ' cache:clear');
