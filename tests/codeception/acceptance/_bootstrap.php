<?php

use Codeception\Util\Fixtures;

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
if (file_exists('app/config/permissions.yml') && !file_exists('app/config/permissions.yml.codeception-backup')) {
    rename('app/config/permissions.yml', 'app/config/permissions.yml.codeception-backup');
}
copy('tests/codeception/_data/permissions.yml', 'app/config/permissions.yml');

// Back up the Sqlite DB if it exists
if (file_exists('app/database/bolt.db') && !file_exists('app/database/bolt.db.codeception-backup')) {
    rename('app/database/bolt.db', 'app/database/bolt.db.codeception-backup');
} elseif (file_exists('app/database/bolt.db')) {
    unlink('app/database/bolt.db');
}

// Empty the cache
system('php app/nut cache:clear');
