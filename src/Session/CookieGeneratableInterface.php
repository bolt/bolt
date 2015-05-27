<?php
namespace Bolt\Session;

use Symfony\Component\HttpFoundation\Cookie;

interface CookieGeneratableInterface
{
    /**
     * @return Cookie
     */
    public function generateCookie();
}
