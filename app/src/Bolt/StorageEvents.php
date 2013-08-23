<?php
namespace Bolt;

/**
 * Definitions for all possible StorageEvents
 */
final class StorageEvents
{
    private function __construct() {}

    // we make no distinction between insert/update
    const preSave      = 'preSave';
    const postSave     = 'postSave';

    const preDelete    = 'preDelete';
    const postDelete   = 'postDelete';
    
    const preSearch    = 'preSearch';
}
