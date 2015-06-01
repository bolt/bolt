<?php

namespace Bolt;

use Bolt\Configuration\ResourceManager;
use Bolt\Exception\LowlevelException;
use Bolt\Translation\Translator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class for Bolt's generic library functions.
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
            return sprintf("%0.2f MiB", ($size / 1024 / 1024));
        } elseif ($size > 1024) {
            return sprintf("%0.2f KiB", ($size / 1024));
        } else {
            return $size . ' B';
        }
    }

    /**
     * Convert a size string, such as 5M to bytes.
     *
     * @param string $size
     *
     * @return integer
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
        $filename = str_replace("%2F", "/", $filename);

        if (substr($filename, 0, 1) == "/") {
            $filename = substr($filename, 1);
        }

        return $filename;
    }

    /**
     * @param object $obj
     *
     * @return array
     *
     * @deprecated
     */
    public static function hackislyParseRegexTemplates($obj)
    {
        return self::parseTwigTemplates($obj);
    }

    /**
     * parse the used .twig templates from the Twig Loader object, using regular expressions.
     *
     * We use this for showing them in the debug toolbar.
     *
     * @param \Twig_LoaderInterface $obj
     *
     * @return array
     */
    public static function parseTwigTemplates($obj)
    {
        $app = ResourceManager::getApp();

        $str = print_r($obj, true);

        preg_match_all('| => (.+\.twig)|i', $str, $matches);

        $templates = array();

        foreach ($matches[1] as $match) {
            $templates[] = str_replace($app['resources']->getPath('rootpath'), '', $match);
        }

        return $templates;
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
    public static function path($path, $param = array(), $add = '')
    {
        $app = ResourceManager::getApp();

        if (!empty($add) && $add[0] != "?") {
            $add = "?" . $add;
        }

        if (empty($param)) {
            $param = array();
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
    public static function redirect($path, $param = array(), $add = '')
    {
        $app = ResourceManager::getApp();

        // If the user doesn't have access to the backend, redirect them to the frontend
        if ($path === 'dashboard' && $app['users']->isValidSession() && !$app['users']->isAllowed('dashboard')) {
            $app['session']->getFlashBag()->clear();
            $path = 'homepage';
        }

        // Only set the 'retreat' when redirecting to 'login' but not FROM logout.
        if (($path === 'login') && ($app['request']->get('_route') !== 'logout')) {
            $app['session']->set(
                'retreat',
                array(
                    'route'  => $app['request']->get('_route'),
                    'params' => $app['request']->get('_route_params')
                )
            );
        } else {
            $app['session']->set('retreat', '');
        }

        return $app->redirect(self::path($path, $param, $add));
    }

    /**
     * Get an array of the query parameters
     * 
     * @internal
     *
     * @param string $url
     *
     * @return array
     */
    public static function getQueryParameters($url)
    {
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'], $parameters);
        $parameters = array_diff($parameters, array(''));

        return $parameters;
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
            $path = "/";
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
     * Loads a serialized file, unserializes it, and returns it.
     *
     * If the file isn't readable (or doesn't exist) or reading it fails,
     * false is returned.
     *
     * @param string  $filename
     * @param boolean $silent   Set to true if you want an visible error.
     *
     * @throws \Bolt\Exception\LowlevelException
     *
     * @return mixed
     */
    public static function loadSerialize($filename, $silent = false)
    {
        if (! is_readable($filename)) {
            if ($silent) {
                return false;
            }

            $part = Translator::__(
                'Try logging in with your ftp-client and make the file readable. ' .
                'Else try to go <a>back</a> to the last page.'
            );
            $message = '<p>' . Translator::__('The following file could not be read:') . '</p>' .
                '<pre>' . htmlspecialchars($filename) . '</pre>' .
                '<p>' . str_replace('<a>', '<a href="javascript:history.go(-1)">', $part) . '</p>';

            throw new LowlevelException(Translator::__('File is not readable!'), $message);
        }

        $serializedData = trim(implode('', file($filename)));
        $serializedData = str_replace('<?php /* bolt */ die(); ?' . '>', '', $serializedData);

        // new-style JSON-encoded data; detect automatically
        if (substr($serializedData, 0, 5) === 'json:') {
            $serializedData = substr($serializedData, 5);
            $data = json_decode($serializedData, true);

            return $data;
        }

        // old-style serialized data; to be phased out, but leaving intact for
        // backwards-compatibility. Up until Bolt 1.5, we used to serialize certain
        // fields, so reading in those old records will still use the code below.
        try {
            $data = unserialize($serializedData);
            if (is_array($data)) {
                return $data;
            } else {
                $tempSerializedData = preg_replace("/\r\n/", "\n", $serializedData);
                if ($data = unserialize($tempSerializedData)) {
                    return $data;
                } else {
                    $tempSerializedData = preg_replace("/\n/", "\r\n", $serializedData);
                    if ($data = unserialize($tempSerializedData)) {
                        return $data;
                    } else {
                        return false;
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Serializes some data and then saves it.
     *
     * @param string $filename
     * @param mixed  $data
     *
     * @throws \Bolt\Exception\LowlevelException
     *
     * @return bool
     */
    public static function saveSerialize($filename, &$data)
    {
        $serString = '<?php /* bolt */ die(); ?>json:' . json_encode($data);

        // disallow user to interrupt
        ignore_user_abort(true);

        $oldUmask = umask(0111);

        // open the file and lock it.
        if ($fp = fopen($filename, 'a')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {

                // Truncate the file (since we opened it for 'appending')
                ftruncate($fp, 0);

                // Write to our locked, empty file.
                if (fwrite($fp, $serString)) {
                    flock($fp, LOCK_UN);
                    fclose($fp);
                } else {
                    flock($fp, LOCK_UN);
                    fclose($fp);

                    $message = 'Error opening file<br/><br/>' .
                        'The file <b>' . $filename . '</b> could not be written! <br /><br />' .
                        'Try logging in with your ftp-client and check to see if it is chmodded to be readable by ' .
                        'the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />' .
                        'Current path: ' . getcwd() . '.';
                    throw new LowlevelException($message);
                }
            } else {
                fclose($fp);

                $message = 'Error opening file<br/><br/>' .
                    'Could not lock <b>' . $filename . '</b> for writing! <br /><br />' .
                    'Try logging in with your ftp-client and check to see if it is chmodded to be readable by the ' .
                    'webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />' .
                    'Current path: ' . getcwd() . '.';
                throw new LowlevelException($message);
            }
        } else {
            $message = 'Error opening file<br/><br/>' .
                'The file <b>' . $filename . '</b> could not be opened for writing! <br /><br />' .
                'Try logging in with your ftp-client and check to see if it is chmodded to be readable by the ' .
                'webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />' .
                'Current path: ' . getcwd() . '.';
            throw new LowlevelException($message);
        }
        umask($oldUmask);

        // reset the users ability to interrupt the script
        ignore_user_abort(false);

        return true;
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
