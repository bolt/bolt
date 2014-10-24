<?php

namespace Bolt;

use Maid\Maid;
use Bolt\Configuration\ResourceManager;

/**
 * Class for Bolt's generic library functions
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Library
{
    /**
     * Clean posted data. Convert tabs to spaces (primarily for yaml) and
     * stripslashes when magic quotes are turned on.
     *
     * @param mixed $var
     * @param bool $stripslashes
     * @param bool $strip_control_chars
     * @return string
     */
    public static function cleanPostedData($var, $stripslashes = true, $strip_control_chars = false)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[$key] = self::cleanPostedData($value);
            }
        } elseif (is_string($var)) {
            // expand tabs
            $var = str_replace("\t", "    ", $var);

            // prune control characters
            if ($strip_control_chars) {
                $var = preg_replace('/[[:cntrl:][:space:]]/', ' ', $var);
            }

            // Ah, the joys of \"magic quotes\"!
            if ($stripslashes && get_magic_quotes_gpc()) {
                $var = stripslashes($var);
            }
        }

        return $var;
    }


    /**
     * Compares versions of software.
     *
     * Versions should use the "MAJOR.MINOR.EDIT" scheme, or in other words
     * the format "x.y.z" where (x, y, z) are numbers in [0-9].
     *
     * @param string $currentversion
     * @param string $requiredversion
     * @return boolean
     */
    public static function checkVersion($currentversion, $requiredversion)
    {
        return version_compare($currentversion, $requiredversion) > -1;
    }

    /**
     * Cleans up/fixes a relative paths.
     *
     * As an example '/site/pivotx/../index.php' becomes '/site/index.php'.
     * In addition (non-leading) double slashes are removed.
     *
     * @param string $path
     * @param bool $nodoubleleadingslashes
     * @return string
     */
    public static function fixPath($path, $nodoubleleadingslashes = true)
    {
        $path = str_replace("\\", "/", self::stripTrailingSlash($path));

        // Handle double leading slash (that shouldn't be removed).
        if (!$nodoubleleadingslashes && (strpos($path, '//') === 0)) {
            $lead = '//';
            $path = substr($path, 2);
        } else {
            $lead = '';
        }

        $patharray = explode('/', preg_replace('#/+#', '/', $path));
        $new_path = array();

        foreach ($patharray as $item) {
            if ($item == '..') {
                // remove the previous element
                @array_pop($new_path);
            } elseif ($item == 'http:') {
                // Don't break for URLs with http:// scheme
                $new_path[] = 'http:/';
            } elseif ($item == 'https:') {
                // Don't break for URLs with https:// scheme
                $new_path[] = 'https:/';
            } elseif (($item != '.')) {
                $new_path[] = $item;
            }
        }

        return $lead . implode('/', $new_path);
    }

    /**
     * Ensures that a path has no trailing slash
     *
     * @param string $path
     * @return string
     */
    public static function stripTrailingSlash($path)
    {
        if (substr($path, -1, 1) == "/") {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    /**
     * Format a filesize like '10.3 kb' or '2.5 mb'
     *
     * @param integer $size
     * @return string
     */
    public static function formatFilesize($size)
    {
        if ($size > 1024 * 1024) {
            return sprintf("%0.2f mb", ($size / 1024 / 1024));
        } elseif ($size > 1024) {
            return sprintf("%0.2f kb", ($size / 1024));
        } else {
            return $size . ' b';
        }
    }

    /**
     * Gets the extension (if any) of a filename.
     *
     * @param string $filename
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
     * Returns a "safe" version of the given string - basically only US-ASCII and
     * numbers. Needed because filenames and titles and such, can't use all characters.
     *
     * @param string $str
     * @param boolean $strict
     * @param string $extrachars
     * @return string
     */
    public static function safeString($str, $strict = false, $extrachars = "")
    {
        $str = \URLify::downcode($str);
        $str = str_replace("&amp;", "", $str);

        $delim = '/';
        if ($extrachars != "") {
            $extrachars = preg_quote($extrachars, $delim);
        }
        if ($strict) {
            $str = strtolower(str_replace(" ", "-", $str));
            $regex = "[^a-zA-Z0-9_" . $extrachars . "-]";
        } else {
            $regex = "[^a-zA-Z0-9 _.," . $extrachars . "-]";
        }

        $str = preg_replace($delim . $regex . $delim, '', $str);

        return $str;
    }

    /**
     * Modify a string, so that we can use it for slugs. Like
     * safeString, but using hyphens instead of underscores.
     *
     * @param string $str
     * @param int $length
     * @internal param string $type
     * @return string
     */
    public static function makeSlug($str, $length = 64)
    {

        if (is_array($str)) {
            $str = implode(" ", $str);
        }

        $str = self::safeString(strip_tags($str));

        $str = str_replace(" ", "-", $str);
        $str = strtolower(preg_replace("/[^a-zA-Z0-9_-]/i", "", $str));
        $str = preg_replace("/[-]+/i", "-", $str);
        if ($length > 0) {
            $str = substr($str, 0, $length);
        }
        $str = trim($str, " -"); // Make sure it doesn't start or end with '-'..

        return $str;
    }

    /**
     * Encodes a filename, for use in thumbnails, magnific popup, etc.
     *
     * @param string $filename
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
     * Make a simple array consisting of key=>value pairs, that can be used
     * in select-boxes in forms.
     *
     * @param array $array
     * @param string $key
     * @param string $value
     * @return array
     */
    public static function makeValuepairs($array, $key, $value)
    {
        $temp_array = array();

        if (is_array($array)) {
            foreach ($array as $item) {
                if (empty($key)) {
                    $temp_array[] = $item[$value];
                } else {
                    $temp_array[$item[$key]] = $item[$value];
                }

            }
        }

        return $temp_array;
    }

    /**
     * Wrapper around trimToHTML for backwards-compatibility
     *
     * @param string $str String to trim
     * @param int $desiredLength Target string length
     * @param bool $nbsp Transform spaces to their html entity
     * @param bool $hellip Add dots when the string is too long
     * @param bool $striptags Strip html tags
     * @return string Trimmed string
     */
    public static function trimText($str, $desiredLength, $nbsp = false, $hellip = true, $striptags = true)
    {
        if ($hellip) {
            $ellipseStr = '…';
        } else {
            $ellipseStr = '';
        }

        return trimToHTML($str, $desiredLength, $ellipseStr, $striptags, $nbsp);
    }


    /**
     * Recursively collect nodes from a DOM tree until the tree is exhausted or the
     * desired text length is fulfilled.
     *
     * @param DOMNode $node The current node
     * @param DOMNode $parentNode A target node that will receive copies of all
     *                            collected nodes as child nodes.
     * @param int $remainingLength The remaining number of characters to collect.
     *                             When this value reaches zero, the traversal is
     *                             stopped.
     * @param string $ellipseStr If non-empty, this string will be appended to the
     *                           last collected node when the document gets
     *                           truncated.
     */
    private function collectNodesUpToLength(\DOMNode $node, \DOMNode $parentNode, &$remainingLength, $ellipseStr = '…')
    {
        if ($remainingLength <= 0) {
            return;
        }
        if ($node === null) {
            return;
        }
        if (strlen($node->textContent) <= $remainingLength) {
            $remainingLength -= strlen($node->textContent);
            $parentNode->appendChild($parentNode->ownerDocument->importNode($node, true));

            return;
        }
        // OK, so we need to descend into this node.
        // If it's a text node, we can trim the text content directly:
        if ($node instanceof \DOMCharacterData) {
            $newNode = $parentNode->ownerDocument->importNode($node, false);
            $newNode->data = substr($node->data, 0, $remainingLength);
            if (strlen($node->data) > $remainingLength) {
                $newNode->data .= $ellipseStr;
            }
            $parentNode->appendChild($newNode);
            $remainingLength = 0;

            return;
        }
        // It's not a text node, so we'll shallow-clone the current node and then
        // recurse.
        $newNode = $parentNode->ownerDocument->importNode($node, false);
        $parentNode->appendChild($newNode);
        for ($childNode = $node->firstChild; $childNode; $childNode = $childNode->nextSibling) {
            self::collectNodesUpToLength($childNode, $newNode, $remainingLength, $ellipseStr);
            if ($remainingLength <= 0) {
                break;
            }
        }
    }

    /**
     * Helper function to convert 'soft' spaces to non-breaking spaces in a given DOMNode.
     *
     * @param DOMNode $node The node to process. Note that processing is in-place.
     */
    public static function domSpacesToNBSP(\DOMNode $node)
    {
        $nbsp = html_entity_decode('&nbsp;');
        if ($node instanceof \DOMCharacterData) {
            $node->data = str_replace(' ', $nbsp, $node->data);
        }
        if (!empty($node->childNodes)) {
            foreach ($node->childNodes as $child) {
                self::domSpacesToNBSP($child);
            }
        }
    }

    /**
     * Truncate a given HTML fragment to the desired length (measured as character
     * count), additionally performing some cleanup.
     *
     * @param string $html The HTML fragment to clean up
     * @param int $desiredLength The desired number of characters, or NULL to do
     *                           just the cleanup (but no truncating).
     * @param string $ellipseStr If non-empty, this string will be appended to the
     *                           last collected node when the document gets
     *                           truncated.
     * @param bool $stripTags If TRUE, remove *all* HTML tags. Otherwise, keep a
     *                        whitelisted 'safe' set.
     * @param bool $nbsp If TRUE, convert all whitespace runs to non-breaking
     *                   spaces ('&nbsp;' entities).
     */
    public static function trimToHTML($html, $desiredLength = null, $ellipseStr = "…", $stripTags = false, $nbsp = false)
    {
        // We'll use htmlmaid to clean up the HTML, but because we also have to
        // step through the DOM ourselves to perform the trimming, so we'll do
        // the DOM loading ourselves, rather than leave it to Maid.

        // Do not load external entities - this would be a security risk.
        $prevEntityLoaderDisabled = libxml_disable_entity_loader(true);
        // Don't crash on invalid HTML, but recover gracefully
        $prevInternalErrors = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();

        // We need a bit of wrapping here to keep DOMDocument from adding rogue nodes
        // around our HTML. By doing it explicitly, we keep things under control.
        $doc->loadHTML(
            '<!DOCTYPE html><html>' .
            '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8"/></head>' .
            '<body><div>' . $html . '</div></body>' .
            '</html>'
        );
        $options = array();
        if ($stripTags) {
            $options['allowed-tags'] = array();
        } else {
            $options['allowed-tags'] = array('a', 'div', 'p', 'b', 'i', 'hr', 'br', 'strong', 'em');
        }
        $options['allowed-attribs'] = array('href', 'src', 'id', 'class', 'style');
        $maid = new Maid($options);
        $cleanedNodes = $maid->clean($doc->documentElement->firstChild->nextSibling->firstChild);
        // To collect the cleaned nodes from a node list into a containing node,
        // we have to create yet another document, because cloning nodes inside
        // the same ownerDocument for some reason modifies our node list.
        // I have no idea why, but it does.
        $cleanedDoc = new \DOMDocument();
        $cleanedNode = $cleanedDoc->createElement('div');
        $length = $cleanedNodes->length;
        for ($i = 0; $i < $length; ++$i) {
            $node = $cleanedNodes->item($i);
            $cnode = $cleanedDoc->importNode($node, true);
            $cleanedNode->appendChild($cnode);
        }

        // And now we'll create yet another document (who's keeping count?) to
        // collect our trimmed nodes.
        $newDoc = new \DOMDocument();
        // Again, some wrapping is necessary here...
        $newDoc->loadHTML('<html><body><div></div></body></html>');
        $newNode = $newDoc->documentElement->firstChild->firstChild;
        $length = $desiredLength;
        self::collectNodesUpToLength($cleanedNode, $newNode, $length, $ellipseStr);
        // Convert spaces inside text nodes to &nbsp;
        // This will actually insert the unicode non-breaking space, so we'll have
        // to massage our output at the HTML byte-string level later.
        if ($nbsp) {
            self::domSpacesToNBSP($newNode->firstChild->firstChild);
        }

        // This is some terrible shotgun hacking; for some reason, the above code
        // will sometimes put our desired nodes two levels deep, but in other
        // cases, it'll descend one less level. The proper solution would be
        // to sort out why this is, but for now, just detecting which of the
        // two happened seems to work well enough.
        if (isset($newNode->firstChild->firstChild->childNodes)) {
            $nodes = $newNode->firstChild->firstChild->childNodes;
        } elseif (isset($newNode->firstChild->childNodes)) {
            $nodes = $newNode->firstChild->childNodes;
        } else {
            $nodes = array();
        }

        // And now we convert our target nodes to HTML.
        // Because we don't want any of the wrapper nodes to appear in the
        // output, we'll have to convert them one by one and concatenate the
        // HTML.
        $result = '';
        foreach ($nodes as $node) {
            $result .= Maid::renderFragment($node);
        }
        if ($nbsp) {
            $result = str_replace(html_entity_decode('&nbsp;'), '&nbsp;', $result);
        }
        // Restore previous libxml settings
        libxml_disable_entity_loader($prevEntityLoaderDisabled);
        libxml_use_internal_errors($prevInternalErrors);

        return $result;
    }

    /**
     * Transforms plain text to HTML. Plot twist: text between backticks (`) is
     * wrapped in a <tt> element.
     *
     * @param string $str Input string. Treated as plain text.
     * @return string The resulting HTML
     */
    public static function decorateTT($str)
    {
        $str = htmlspecialchars($str, ENT_QUOTES);
        $str = preg_replace('/`([^`]*)`/', '<tt>\\1</tt>', $str);

        return $str;
    }


    /**
     * String length wrapper. Uses mb_strwidth when available. Fallback to strlen.
     *
     * @param string $str
     * @return int String length
     */
    public static function getStringLength($str)
    {
        if (function_exists('mb_strwidth')) {
            return mb_strwidth($str, 'UTF-8');
        } else {
            return strlen($str);
        }
    }

    /**
     * parse the used .twig templates from the Twig Loader object, using regular expressions.
     *
     * We use this for showing them in the debug toolbar.
     *
     * @param object $obj
     * @return array
     */
    public static function hackislyParseRegexTemplates($obj)
    {
        $str = print_r($obj, true);

        preg_match_all('| => (.+\.twig)|i', $str, $matches);

        $templates = array();

        foreach ($matches[1] as $match) {
            $templates[] = str_replace(BOLT_PROJECT_ROOT_DIR . DIRECTORY_SEPARATOR, '', $match);
        }

        return $templates;
    }



    /**
     * Simple wrapper for $app['url_generator']->generate()
     *
     * @param string $path
     * @param array $param
     * @param string $add
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
     * Simple wrapper for $app->redirect($app['url_generator']->generate());
     *
     * @param string $path
     * @param array $param
     * @param string $add
     * @return string
     */
    public static function redirect($path, $param = array(), $add = '')
    {
        $app = ResourceManager::getApp();

        // Only set the 'retreat' when redirecting to 'login' but not FROM logout.
        if (($path == 'login') && ($app['request']->get('_route') !== 'logout')) {

            $app['session']->set(
                'retreat',
                array(
                    'route' => $app['request']->get('_route'),
                    'params' => $app['request']->get('_route_params')
                )
            );
        } else {
            $app['session']->set('retreat', '');
        }

        return $app->redirect(path($path, $param, $add));
    }

    /**
     * Create a simple redirect to a page / path and die.
     *
     * @param string $path
     * @param boolean $die
     */
    public static function simpleredirect($path, $die = true)
    {
        if (empty($path)) {
            $path = "/";
        }
        header("location: $path");
        echo "<p>Redirecting to <a href='$path'>$path</a>.</p>";
        echo "<script>window.setTimeout(function(){ window.location='$path'; }, 500);</script>";
        if ($die) {
            die();
        }
    }

    /**
     * Helper function for fnmatch() - Match filename against a pattern
     *
     * @param string $pattern
     * @param string $string
     * @param int $flags
     * @return bool
     */
    public static function pcreFnmatch($pattern, $string, $flags = 0)
    {
        $modifiers = null;
        $transforms = array(
            '\*' => '.*',
            '\?' => '.',
            '\[\!' => '[^',
            '\[' => '[',
            '\]' => ']',
            '\.' => '\.',
            '\\' => '\\\\'
        );

        // Forward slash in string must be in pattern:
        if ($flags & FNM_PATHNAME) {
            $transforms['\*'] = '[^/]*';
        }

        // Back slash should not be escaped:
        if ($flags & FNM_NOESCAPE) {
            unset($transforms['\\']);
        }

        // Perform case insensitive match:
        if ($flags & FNM_CASEFOLD) {
            $modifiers .= 'i';
        }

        // Period at start must be the same as pattern:
        if ($flags & FNM_PERIOD) {
            if (strpos($string, '.') === 0 && strpos($pattern, '.') !== 0) {
                return false;
            }
        }

        $pattern = '#^'
            . strtr(preg_quote($pattern, '#'), $transforms)
            . '$#'
            . $modifiers;

        return (boolean) preg_match($pattern, $string);
    }

    /**
     * Detect whether or not a given string is (likely) HTML. It does this by comparing
     * the lengths of the strings before and after strip_tagging. If it's significantly
     * shorter, it's probably HTML.
     *
     * @param string $html
     * @return bool
     */
    public static function isHtml($html)
    {
        $len = strlen($html);

        $trimlen = strlen(strip_tags($html));

        $factor = $trimlen / $len;

        if ($factor < 0.97) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Loads a serialized file, unserializes it, and returns it.
     *
     * If the file isn't readable (or doesn't exist) or reading it fails,
     * false is returned.
     *
     * @param string $filename
     * @param boolean $silent
     *            Set to true if you want an visible error.
     * @return mixed
     */
    public static function loadSerialize($filename, $silent = false)
    {
        $filename = self::fixPath($filename);

        if (! is_readable($filename)) {

            if ($silent) {
                return false;
            }

            $part = self::__(
                'Try logging in with your ftp-client and make the file readable. ' .
                'Else try to go <a>back</a> to the last page.'
            );
            $message = '<p>' . self::__('The following file could not be read:') . '</p>' .
                '<pre>' . htmlspecialchars($filename) . '</pre>' .
                '<p>' . str_replace('<a>', '<a href="javascript:history.go(-1)">', $part) . '</p>';

            renderErrorpage(self::__('File is not readable!'), $message);
        }

        $serialized_data = trim(implode("", file($filename)));
        $serialized_data = str_replace("<?php /* bolt */ die(); ?" . ">", "", $serialized_data);

        // new-style JSON-encoded data; detect automatically
        if (substr($serialized_data, 0, 5) === 'json:') {
            $serialized_data = substr($serialized_data, 5);
            $data = json_decode($serialized_data, true);

            return $data;
        }

        // old-style serialized data; to be phased out, but leaving intact for
        // backwards-compatibility. Up until Bolt 1.5, we used to serialize certain
        // fields, so reading in those old records will still use the code below.
        @$data = unserialize($serialized_data);
        if (is_array($data)) {
            return $data;
        } else {
            $temp_serialized_data = preg_replace("/\r\n/", "\n", $serialized_data);
            if (@$data = unserialize($temp_serialized_data)) {
                return $data;
            } else {
                $temp_serialized_data = preg_replace("/\n/", "\r\n", $serialized_data);
                if (@$data = unserialize($temp_serialized_data)) {
                    return $data;
                } else {
                    return false;
                }
            }
        }

    }


    /**
     * Serializes some data and then saves it.
     *
     * @param string $filename
     * @param mixed $data
     * @return boolean
     */
    public static function saveSerialize($filename, &$data)
    {
        $filename = self::fixPath($filename);

        $ser_string = '<?php /* bolt */ die(); ?>json:' . json_encode($data);

        // disallow user to interrupt
        ignore_user_abort(true);

        $old_umask = umask(0111);

        // open the file and lock it.
        if ($fp = fopen($filename, 'a')) {

            if (flock($fp, LOCK_EX | LOCK_NB)) {

                // Truncate the file (since we opened it for 'appending')
                ftruncate($fp, 0);

                // Write to our locked, empty file.
                if (fwrite($fp, $ser_string)) {
                    flock($fp, LOCK_UN);
                    fclose($fp);
                } else {
                    flock($fp, LOCK_UN);
                    fclose($fp);

                    // todo: handle errors better.
                    die(
                        'Error opening file<br/><br/>' .
                        'The file <b>' . $filename . '</b> could not be written! <br /><br />' .
                        'Try logging in with your ftp-client and check to see if it is chmodded to be readable by ' .
                        'the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />' .
                        'Current path: ' . getcwd() . '.'
                    );
                }
            } else {
                fclose($fp);

                // todo: handle errors better.
                die(
                    'Error opening file<br/><br/>' .
                    'Could not lock <b>' . $filename . '</b> for writing! <br /><br />' .
                    'Try logging in with your ftp-client and check to see if it is chmodded to be readable by the ' .
                    'webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />' .
                    'Current path: ' . getcwd() . '.'
                );
            }
        } else {
            // todo: handle errors better.
            print
                'Error opening file<br/><br/>' .
                'The file <b>' . $filename . '</b> could not be opened for writing! <br /><br />' .
                'Try logging in with your ftp-client and check to see if it is chmodded to be readable by the ' .
                'webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />' .
                'Current path: ' . getcwd() . '.';
            debug_print_backtrace();
            die();
        }
        umask($old_umask);

        // reset the users ability to interrupt the script
        ignore_user_abort(false);

        return true;
    }

    /**
     * Replace the first occurence of a string only. Behaves like str_replace, but
     * replaces _only_ the _first_ occurence.
     *
     * @see http://stackoverflow.com/a/2606638
     */
    public static function str_replace_first($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @author Bob for bolt-specific excludes
     */
    public static function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {

            // if $key = 'accept_file_types, don't merge..
            if ($key == 'accept_file_types') {
                $merged[$key] = $array2[$key];
                continue;
            }

            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function getReferrer(Symfony\Component\HttpFoundation\Request $request)
    {
        $tmp = parse_url($request->server->get('HTTP_REFERER'));

        $referrer = $tmp['path'];
        if (!empty($tmp['query'])) {
            $referrer .= "?" . $tmp['query'];
        }

        return $referrer;
    }

    /**
     * htmlencode() and htmlencode_params() are not used in the codebase.
     */
    public static function htmlencode($str)
    {
        return htmlspecialchars($str, ENT_QUOTES);
    }

    public static function htmlencode_params($params)
    {
        $result = array();
        foreach ($params as $key => $val) {
            $result[$key] = self::htmlencode($val);
        }

        return $result;
    }

    /**
     * i18n made right, second attempt...
     *
     * Instead of calling directly $app['translator']->trans(), we check
     * for the presence of a placeholder named '%contenttype%'.
     *
     * If one is found, we replace it with the contenttype.name parameter,
     * and try to get a translated string. If there is not, we revert to
     * the generic (%contenttype%) string, which must have a translation.
     */
    public static function __()
    {
        $app = ResourceManager::getApp();

        $num_args = func_num_args();
        if (0 == $num_args) {
            return null;
        }
        $args = func_get_args();
        if ($num_args > 4) {
            $fn = 'transChoice';
        } elseif ($num_args == 1 || is_array($args[1])) {
            // If only 1 arg or 2nd arg is an array call trans
            $fn = 'trans';
        } else {
            $fn = 'transChoice';
        }
        $tr_args = null;
        if ($fn == 'trans' && $num_args > 1) {
            $tr_args = $args[1];
        } elseif ($fn == 'transChoice' && $num_args > 2) {
            $tr_args = $args[2];
        }
        // Check for contenttype(s) placeholder
        if ($tr_args) {
            $x = $tr_args;
            if (isset($tr_args['%contenttype%'])) {
                $key_arg = '%contenttype%';
            } elseif (isset($tr_args['%contenttypes%'])) {
                $key_arg = '%contenttypes%';
            } else {
                $key_arg = false;
            }
            $key_generic = $args[0];
            if ($key_arg && substr($key_generic, 0, 21) == 'contenttypes.generic.') {

                // Translates a key to text, returns false when not found
                $fnc_trans = function ($key, $tr_args, $domain) use ($fn, $args, $app) {
                    if ($fn == 'transChoice') {
                        $trans = $app['translator']->transChoice(
                            $key,
                            $args[1],
                            self::htmlencode_params($tr_args),
                            isset($args[3]) ? $args[3] : $domain,
                            isset($args[4]) ? $args[4] : $app['request']->getLocale()
                        );
                    } else {
                        $trans = $app['translator']->trans(
                            $key,
                            self::htmlencode_params($tr_args),
                            isset($args[2]) ? $args[2] : $domain,
                            isset($args[3]) ? $args[3] : $app['request']->getLocale()
                        );
                    }

                    return ($trans == $key) ? false : $trans;
                };
                $ctype = $tr_args[$key_arg];
                unset($tr_args[$key_arg]);
                $key_ctype = 'contenttypes.' . $ctype . '.text.' . substr($key_generic, 21);

                // Try to get a direct translation, fallback to en
                $trans = $fnc_trans($key_ctype, $tr_args, 'contenttypes');

                // No translation found, use generic translation
                if ($trans === false) {
                    // Get contenttype name
                    $key_name = 'contenttypes.' . $ctype . '.name.' . (($key_arg == '%contenttype%') ? 'singular' : 'plural');
                    $key_ctname = ($key_arg == '%contenttype%') ? 'singular_name' : 'name';

                    $ctname = $fnc_trans($key_name, $tr_args, 'contenttypes');
                    if ($ctname === false) {
                        $ctypes = $app['config']->get('contenttypes');
                        $ctname = empty($ctypes[$ctype][$key_ctname]) ? ucfirst($ctype) : $ctypes[$ctype][$key_ctname];
                    }
                    // Get generic translation with name replaced
                    $tr_args[$key_arg] = $ctname;
                    $trans = $fnc_trans($key_generic, $tr_args, 'messages');
                }

                return $trans;
            }
            // BEGIN OLD CODE
            $keytype = '%contenttype%';
            $keytypes = '%contenttypes%';
            $have_singular = array_key_exists($keytype, $tr_args);
            $have_plural = array_key_exists($keytypes, $tr_args);
            if ($have_singular || $have_plural) {
                // have a %contenttype% placeholder, try to find a specialized translation
                if ($have_singular) {
                    $text = str_replace($keytype, $tr_args[$keytype], $args[0]);
                    unset($tr_args[$keytype]);
                } else {
                    $text = str_replace($keytypes, $tr_args[$keytypes], $args[0]);
                    unset($tr_args[$keytypes]);
                }

                if ($fn == 'transChoice') {
                        $trans = $app['translator']->transChoice(
                            $text,
                            $args[1],
                            self::htmlencode_params($tr_args),
                            isset($args[3]) ? $args[3] : 'contenttypes',
                            isset($args[4]) ? $args[4] : $app['request']->getLocale()
                        );
                } else {
                        $trans = $app['translator']->trans(
                            $text,
                            self::htmlencode_params($tr_args),
                            isset($args[2]) ? $args[2] : 'contenttypes',
                            isset($args[3]) ? $args[3] : $app['request']->getLocale()
                        );
                }

                if ($text != $trans) {
                    return $trans;
                }
            }
            // END OLD CODE
        }

        //try {
        if (isset($args[1])) {
            $args[1] = self::htmlencode_params($args[1]);
        }
        switch($num_args) {
            case 5:
                return $app['translator']->transChoice($args[0], $args[1], $args[2], $args[3], $args[4]);
            case 4:
                return $app['translator']->$fn($args[0], $args[1], $args[2], $args[3]);
            case 3:
                return $app['translator']->$fn($args[0], $args[1], $args[2]);
            case 2:
                return $app['translator']->$fn($args[0], $args[1]);
            case 1:
                return $app['translator']->$fn($args[0]);
        }

    }

    /**
     * Leniently decode a serialized compound data structure, detecting whether
     * it's dealing with JSON-encoded data or a PHP-serialized string.
     */
    public static function smart_unserialize($str, $assoc = true)
    {
        if ($str[0] === '{' || $str[0] === '[') {
            $data = json_decode($str, $assoc);
            if ($data !== false) {
                return $data;
            }
        }
        $data = unserialize($str);

        return $data;
    }
}