<?php

namespace Bolt\Events;

/**
 * AccessControl event constants.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessControlEvents
{
    const LOGIN_SUCCESS = 'login.success';
    const LOGIN_FAILURE = 'login.failure';

    const LOGOUT_SUCCESS = 'logout.success';

    const RESET_REQUEST = 'reset.request';
    const RESET_SUCCESS = 'reset.success';
    const RESET_FAILURE = 'reset.failure';

    const ACCESS_CHECK_REQUEST = 'access.check.request';
    const ACCESS_CHECK_SUCCESS = 'access.check.success';
    const ACCESS_CHECK_FAILURE = 'access.check.failure';

    const FAILURE_INVALID  = 1;
    const FAILURE_PASSWORD = 2;
    const FAILURE_DISABLED = 4;
    const FAILURE_LOCKED   = 8;
    const FAILURE_DENIED   = 16;

    /**
     * Singleton constructor.
     */
    private function __construct()
    {
    }
}
