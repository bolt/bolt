<?php

namespace Bolt;

use Bolt\Configuration\ResourceManager;
use Symfony\Component\HttpFoundation\Response;

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
     * @param integer $size
     *
     * @return string
     */
    public static function formatFilesize($size)
    {
        if ($size > 1024 * 1024) {
            return sprintf('%0.2f MiB', ($size / 1024 / 1024));
        } elseif ($size > 1024) {
            return sprintf('%0.2f KiB', ($size / 1024));
        } else {
            return $size . ' B';
        }
    }

    /**
     * Convert a size string, such as 5M to bytes.
     *
     * @param string $size
     *
     * @return double
     */
    public static function filesizeToBytes($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    /**
     * Gets the extension (if any) of a filename.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function getExtension($filename)
    {
        $pos = strrpos($filename, '.');
        if ($pos === false) {
            return '';
        } else {
            $ext = substr($filename, $pos + 1);

            return $ext;
        }
    }

    /**
     * Encodes a filename, for use in thumbnails, magnific popup, etc.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function safeFilename($filename)
    {
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
     * @param string $path
     * @param array  $param
     * @param string $add
     *
     * @return string
     */
    public static function path($path, $param = [], $add = '')
    {
        $app = ResourceManager::getApp();

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
     * @param string $path
     * @param array  $param
     * @param string $add
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function redirect($path, $param = [], $add = '')
    {
        return ResourceManager::getApp()->redirect(self::path($path, $param, $add));
    }

    /**
     * Create a simple redirect to a page / path.
     *
     * @param string $path
     * @param bool   $abort
     *
     * @return string
     */
    public static function simpleredirect($path, $abort = false)
    {
        $app = ResourceManager::getApp();

        if (empty($path)) {
            $path = '/';
        }
        header("location: $path");
        echo "<p>Redirecting to <a href='$path'>$path</a>.</p>";
        echo "<script>window.setTimeout(function () { window.location='$path'; }, 500);</script>";
        if (!$abort) {
            return $path;
        }

        $app->abort(Response::HTTP_SEE_OTHER, "Redirecting to '$path'.");
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
            $data = json_decode($str, $assoc);
            if ($data !== false) {
                return $data;
            }
        } else {
            $data = unserialize($str);

            return $data;
        }
    }
}
