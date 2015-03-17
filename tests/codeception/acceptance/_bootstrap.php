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

// Set up a fixture for the permissions-yml
if (file_exists(TEST_ROOT. '/app/config/permissions.yml') && !file_exists(TEST_ROOT. '/app/config/permissions.yml.codeception-backup')) {
    rename(TEST_ROOT. '/app/config/permissions.yml', TEST_ROOT. '/app/config/permissions.yml.codeception-backup');
}
copy(TEST_ROOT. '/tests/codeception/_data/permissions.yml', TEST_ROOT. '/app/config/permissions.yml');

// Back up the Sqlite DB if it exists
if (file_exists(TEST_ROOT. '/app/database/bolt.db') && !file_exists(TEST_ROOT. '/app/database/bolt.db.codeception-backup')) {
    rename(TEST_ROOT. '/app/database/bolt.db', TEST_ROOT. '/app/database/bolt.db.codeception-backup');
} elseif (file_exists(TEST_ROOT. '/app/database/bolt.db')) {
    unlink(TEST_ROOT. '/app/database/bolt.db');
}

// Install the local extension
$fs = new Filesystem();
$fs->mirror(CODECEPTION_ROOT . '/_data/extensions/local/', TEST_ROOT . '/extensions/local/', null, array('override' => true, 'delete' => true));

// Empty the cache
system('php ' . NUT_PATH . ' cache:clear');
