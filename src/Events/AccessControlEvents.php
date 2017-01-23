<?php

namespace Bolt\Events;

class AccessControlEvents
{
    const LOGIN_SUCCESS = 'login.success';
    const LOGIN_FAILURE = 'login.failure';

    const RESET_REQUEST = 'reset.request';
    const RESET_SUCCESS = 'reset.success';
    const RESET_FAILURE = 'reset.failure';

    /**
     * Singleton constructor.
     */
    private function __construct()
    {
    }
}
