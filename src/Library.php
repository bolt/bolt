<?php

namespace Bolt;

use Bolt\Common\Deprecated;
use Bolt\Common\Json;
use Bolt\Common\Serialization;
use Bolt\Legacy\AppSingleton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class for Bolt's generic library functions.
 *
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Library
{
    /**
     * Format a filesize like '10.3 KiB' or '2.5 MiB'.
     *
     * @param int $size
     *
     * @return string
     */
    public static function formatFilesize($size)
    {
        if ($size > 1024 * 1024) {
            return sprintf('%0.2f MiB', ($size / 1024 / 1024));
        } elseif ($size > 1024) {
            return sprintf('%0.2f KiB', ($size / 1024));
        }

        return $size . ' B';
    }

    /**
     * Convert a size string, such as 5M to bytes.
     *
     * @param string $size
     *
     * @return float
     */
    public static function filesizeToBytes($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    /**
     * Gets the extension (if any) of a filename.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function getExtension($filename)
    {
        Deprecated::method(3.0, 'Use pathinfo() instead.');

        $pos = strrpos($filename, '.');
        if ($pos === false) {
            return '';
        }
        $ext = substr($filename, $pos + 1);

        return $ext;
    }

    /**
     * Encodes a filename, for use in thumbnails, magnific popup, etc.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function safeFilename($filename)
    {
        Deprecated::method(3.0);

        $filename = rawurlencode($filename); // Use 'rawurlencode', because we prefer '%20' over '+' for spaces.
        $filename = str_replace('%2F', '/', $filename);

        if (substr($filename, 0, 1) == '/') {
            $filename = substr($filename, 1);
        }

        return $filename;
    }

    /**
     * Simple wrapper for $app['url_generator']->generate().
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $path
     * @param array  $param
     * @param string $add
     *
     * @return string
     */
    public static function path($path, $param = [], $add = '')
    {
        Deprecated::method(3.0, UrlGeneratorInterface::class . '::generate');

        $app = AppSingleton::get();

        if (!empty($add) && $add[0] != '?') {
            $add = '?' . $add;
        }

        if (empty($param)) {
            $param = [];
        }

        return $app['url_generator']->generate($path, $param) . $add;
    }

    /**
     * Simple wrapper for $app->redirect($app['url_generator']->generate());.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $path
     * @param array  $param
     * @param string $add
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function redirect($path, $param = [], $add = '')
    {
        Deprecated::method(3.0);

        return new RedirectResponse(self::path($path, $param, $add));
    }

    /**
     * Create a simple redirect to a page / path.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $path
     * @param bool   $abort
     *
     * @return string
     */
    public static function simpleredirect($path, $abort = false)
    {
        Deprecated::method(3.0);

        if (empty($path)) {
            $path = '/';
        }
        header("location: $path");
        echo "<p>Redirecting to <a href='$path'>$path</a>.</p>";
        echo "<script>window.setTimeout(function () { window.location='$path'; }, 500);</script>";
        if (!$abort) {
            return $path;
        }

        throw new HttpException(Response::HTTP_SEE_OTHER, "Redirecting to '$path'.");
    }

    /**
     * Leniently decode a serialized compound data structure, detecting whether
     * it's dealing with JSON-encoded data or a PHP-serialized string.
     *
     * @param string $str
     * @param bool   $assoc
     *
     * @return mixed
     */
    public static function smartUnserialize($str, $assoc = true)
    {
        if ($str[0] === '{' || $str[0] === '[') {
            return Json::parse($str, $assoc);
        }

        return Serialization::parse($str);
    }
}
