<?php

namespace Bolt;

use Bolt\Helpers\Deprecated;

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
        } else {
            $ext = substr($filename, $pos + 1);

            return $ext;
        }
    }
}
