<?php

use Codeception\Util\Fixtures;

/**
 * Bootstrap for Codeception tests
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */

// User IDs
Fixtures::add('users', [
    'admin' => [
        'username'    => 'admin',
        'password'    => 'topsecret',
        'email'       => 'admin@example.com',
        'displayname' => 'Admin Person'
    ],
    'editor' => [
        'username'    => 'editor',
        'password'    => 'nomoresecrets',
        'email'       => 'editor@example.com',
        'displayname' => 'Editor Person'
    ],
    'manager' => [
        'username'    => 'manager',
        'password'    => 'cantkeepsecrets',
        'email'       => 'manager@example.com',
        'displayname' => 'Manager Person'
    ],
    'developer' => [
        'username'    => 'developer',
        'password'    => '~n0Tne1k&nGu3$$',
        'email'       => 'developer@example.com',
        'displayname' => 'Developer Person'
    ],
    'lemmings' => [
        'username'    => 'lemmings',
        'password'    => 'MikeDaillyDavidJones',
        'email'       => 'lemmings@example.com',
        'displayname' => 'Lemmings Person'
    ]
]);

// Files that we'll backup. If they exist when we run, then we keep the
// original in tact before starting the suite run
Fixtures::add('backups', [
    INSTALL_ROOT . '/app/config/config.yml'                                => false,
    INSTALL_ROOT . '/app/config/contenttypes.yml'                          => false,
    INSTALL_ROOT . '/app/config/menu.yml'                                  => false,
    INSTALL_ROOT . '/app/config/permissions.yml'                           => false,
    INSTALL_ROOT . '/app/config/routing.yml'                               => false,
    INSTALL_ROOT . '/app/config/taxonomy.yml'                              => false,
    BOLT_ROOT . '/app/resources/translations/en_GB/messages.en_GB.yml'     => true,
    BOLT_ROOT . '/app/resources/translations/en_GB/infos.en_GB.yml'        => true,
    BOLT_ROOT . '/app/resources/translations/en_GB/contenttypes.en_GB.yml' => true,
    INSTALL_ROOT . '/app/database/bolt.db'                                 => false,
    INSTALL_ROOT . '/theme/base-2014/_footer.twig'                         => true,
]);

// Session and authentication tokens
Fixtures::add('tokenNames', [
    'session'   => 'bolt_session_' . md5('localhost:8123/'),
    'authtoken' => 'bolt_authtoken_' . md5('localhost:8123/'),
]);
