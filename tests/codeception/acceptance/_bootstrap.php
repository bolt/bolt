<?php

use Codeception\Util\Fixtures;
use Symfony\Component\Filesystem\Filesystem;

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
    ]
]);

// Files that we'll back and and if we keep the original in tact before starting
// the suite run
Fixtures::add('backups', [
    '/app/config/config.yml'                               => false,
    '/app/config/contenttypes.yml'                         => false,
    '/app/config/menu.yml'                                 => false,
    '/app/config/permissions.yml'                          => false,
    '/app/config/routing.yml'                              => false,
    '/app/config/taxonomy.yml'                             => false,
    '/app/resources/translations/en_GB/messages.en_GB.yml' => true,
    '/app/resources/translations/en_GB/infos.en_GB.yml'    => true,
    '/app/database/bolt.db'                                => false,
    '/theme/base-2014/_footer.twig'                        => true,
]);
