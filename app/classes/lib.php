<?php

/**
 * Recursively creates chmodded directories. Returns true on success,
 * and false on failure.
 *
 * NB! Directories are created with permission 777 - worldwriteable -
 * unless you have set 'chmod_dir' to 0XYZ in the advanced config.
 *
 * @param string $name
 * @return boolean
 */
function makeDir($name)
{
    // if it exists, just return.
    if (file_exists($name)) {
        return true;
    }

    // If more than one level, try parent first..
    // If creating parent fails, we can abort immediately.
    if (dirname($name) != ".") {
        $success = makeDir(dirname($name));
        if (!$success) {
            return false;
        }
    }

    if (empty($mode)) {
        $mode = '0777';
    }
    $mode_dec = octdec($mode);

    $oldumask = umask(0);
    $success = @mkdir($name, $mode_dec);
    @chmod($name, $mode_dec);
    umask($oldumask);

    return $success;

}

/**
 * generate a CSRF-like token, to use in GET requests for stuff that ought to be POST-ed forms.
 *
 * @return string $token
 */
function getToken()
{
    $seed = $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $_COOKIE['bolt_session'];
    $token = substr(md5($seed), 0, 8);

    return $token;
}

/**
 * Check if a given token matches the current (correct) CSRF-like token
 *
 * @param string $token
 *
 * @return bool
 */
function checkToken($token = "")
{
    global $app;

    if (empty($token)) {
        $token = $app['request']->get('token');
    }

    if ($token === getToken()) {
        return true;
    } else {
        $app['session']->getFlashBag()->set('error', "The security token was incorrect. Please try again.");

        return false;
    }

}

/**
 * Clean posted data. Convert tabs to spaces (primarily for yaml) and
 * stripslashes when magic quotes are turned on.
 *
 * @param mixed $var
 * @return string
 */
function cleanPostedData($var)
{
    if (is_array($var)) {

        foreach ($var as $key => $value) {
            $var[$key] = cleanPostedData($value);
        }

    } elseif (is_string($var)) {

        $var = str_replace("\t", "    ", $var);

        // Ah, the joys of \"magic quotes\"!
        if (get_magic_quotes_gpc()) {
            $var = stripslashes($var);
        }

    }

    return $var;

}


function findFiles($term, $extensions = "")
{
    if (is_string($extensions)) {
        $extensions = explode(",", $extensions);
    }

    $files = array();

    findFilesHelper('', $files, strtolower($term), $extensions);

    // Sort the array, and only keep the values, not the keys.
    natcasesort($files);
    $files = array_values($files);

    return $files;

}

function findFilesHelper($additional, &$files, $term = "", $extensions = array())
{
    $basefolder = __DIR__."/../../files/";

    $currentfolder = realpath($basefolder."/".$additional);

    $d = dir($currentfolder);

    $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

    while (false !== ($entry = $d->read())) {

        if (in_array($entry, $ignored) || substr($entry, 0, 2) == "._") {
            continue;
        }

        if (is_file($currentfolder."/".$entry) && is_readable($currentfolder."/".$entry)) {

            // Check for 'term'..
            if (!empty($term) && (strpos(strtolower($currentfolder."/".$entry), $term) === false)) {
                continue; // skip this one..
            }

            // Check for correct extensions..
            if (!empty($extensions) && !in_array(getExtension($entry), $extensions)) {
                continue; // Skip files without correct extension..
            }

            if (!empty($additional)) {
                $filename = $additional . "/" . $entry;
            } else {
                $filename = $entry;
            }

            $files[] = $filename;
        }

        if (is_dir($currentfolder."/".$entry)) {
            findFilesHelper($additional."/".$entry, $files, $term, $extensions);
        }


    }

    $d->close();

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
 *
 */
function checkVersion($currentversion, $requiredversion)
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
 * @return string
 */
function fixPath($path, $nodoubleleadingslashes = true)
{

    $path = str_replace("\\", "/", stripTrailingSlash($path));

    // Handle double leading slash (that shouldn't be removed).
    if (!$nodoubleleadingslashes && (strpos($path,'//') === 0)) {
        $lead = '//';
        $path = substr($path,2);
    } else {
        $lead = '';
    }

    $patharray = explode('/', preg_replace('#/+#', '/', $path));
    $new_path = array();

    foreach ($patharray as $item) {
        if ($item == "..") {
            // remove the previous element
            @array_pop($new_path);
        } else if ($item == "http:") {
            // Don't break for URLs with http:// scheme
            $new_path[]="http:/";
        } else if ($item == "https:") {
            // Don't break for URLs with https:// scheme
            $new_path[]="https:/";
        } else if ( ($item != ".") ) {
            $new_path[]=$item;
        }
    }

    return $lead.implode("/", $new_path);

}



/**
 * Ensures that a path has no trailing slash
 *
 * @param string $path
 * @return string
 */
function stripTrailingSlash($path)
{
    if (substr($path, -1, 1) == "/") {
        $path = substr($path, 0, -1);
    }

    return $path;
}

/**
 * Gets current Unix timestamp (in seconds) with microseconds, as a float.
 *
 * @return float
 */
function getMicrotime()
{
    list($usec, $sec) = explode(" ", microtime());

    return ((float) $usec + (float) $sec);
}

/**
 * Calculates time that was needed for execution.
 *
 * @param integer $precision§
 * @return string
 */
function timeTaken($precision = 2)
{
    global $starttime;
    $endtime = getMicrotime();
    $time_taken = $endtime - $starttime;
    $time_taken= number_format($time_taken, $precision);

    return $time_taken;

}


/**
 * Get the amount of used memory, if memory_get_usage is defined.
 *
 * @return string
 */
function getMem()
{
    if (function_exists('memory_get_usage')) {
        $mem = memory_get_usage();

        return formatFilesize($mem);
    } else {
        return "unknown";
    }
}



/**
 * Get the maximum amount of used memory, if memory_get_usage is defined.
 *
 * @return string
 */
function getMaxMem()
{
    if (function_exists('memory_get_peak_usage')) {
        $mem = memory_get_peak_usage();

        return formatFilesize($mem);
    } else {
        return "unknown";
    }
}



/**
 * Format a filesize like '10.3 kb' or '2.5 mb'
 *
 * @param integer $size
 * @return string
 */
function formatFilesize($size)
{
    if ($size > 1024*1024) {
        return sprintf("%0.2f mb", ($size/1024/1024));
    } elseif ($size > 1024) {
        return sprintf("%0.2f kb", ($size/1024));
    } else {
        return $size." b";
    }

}


/**
 * Makes a random key with the specified length.
 *
 * @param int $length
 * @return string
 */
function makeKey($length, $stronger = false)
{
    if ($stronger) {
        $seed = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*";
    } else {
        $seed = "0123456789abcdefghijklmnopqrstuvwxyz";
    }
    $len = strlen($seed);
    $key = "";

    for ($i=0; $i<$length; $i++) {
        $key .= $seed[ rand(0, $len-1) ];
    }

    return $key;

}


/**
 * Gets the extension (if any) of a filename.
 *
 * @param string $filename
 * @return string
 */
function getExtension($filename)
{
    $pos=strrpos($filename, ".");
    if ($pos === false) {
        return "";
    } else {
        $ext=substr($filename, $pos+1);

        return $ext;
    }
}




/**
 * Returns a "safe" version of the given string - basically only US-ASCII and
 * numbers. Needed because filenames and titles and such, can't use all characters.
 *
 * @param string $str
 * @param boolean $strict
 * @return string
 */
function safeString($str, $strict = false, $extrachars = "")
{

    $str = URLify::downcode($str);

    $str = str_replace("&amp;", "", $str);

    $delim = '/';
    if ($extrachars != "") {
        $extrachars = preg_quote($extrachars, $delim);
    }
    if ($strict) {
        $str = strtolower(str_replace(" ", "-", $str));
        $regex = "[^a-zA-Z0-9_".$extrachars."-]";
    } else {
        $regex = "[^a-zA-Z0-9 _.,".$extrachars."-]";
    }

    $str = preg_replace("$delim$regex$delim", "", $str);

    return $str;
}

/**
 * Modify a string, so that we can use it for slugs. Like
 * safeString, but using hyphens instead of underscores.
 *
 * @param string $str
 * @param string $type
 * @return string
 */
function makeSlug($str)
{
    $str = safeString(strip_tags($str));

    $str = str_replace(" ", "-", $str);
    $str = strtolower(preg_replace("/[^a-zA-Z0-9_-]/i", "", $str));
    $str = preg_replace("/[-]+/i", "-", $str);
    $str = substr($str, 0, 64); // 64 chars ought to be long enough.
    $str = trim($str, " -"); // Make sure it doesn't start or end with '-'..

    return $str;

}

/**
 * Encodes a filename, for use in thumbnails, fancybox, etc.
 *
 * @param string $filename
 * @return string
 */
function safeFilename($filename) {

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
 */
function makeValuepairs($array, $key, $value)
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
 * Counts the number of white spaces on the beginning of a string.
 *
 * @param string $str
 * @return int Number of white spaces
 */
function getLeftWhiteSpaceCount($str){
    $strLenLTrimmed = getStringLength(ltrim($str));
    $count = getStringLength($str) - $strLenLTrimmed;
    return $count;
}

/**
 * Trim a text to a given length, taking html entities into account.
 *
 * @param string $str String to trim
 * @param int $desiredLength Target string length
 * @param bool $nbsp Transform spaces to their html entity
 * @param bool $hellip Add dots when the string is too long
 * @param bool $striptags Strip html tags
 * @return string Trimmed string
 */
function trimText($str, $desiredLength, $nbsp = false, $hellip = true, $striptags = true){
    $result = recursiveTrimText($str, $desiredLength, $nbsp, $hellip, $striptags);
    return $result['string'];
}

/**
 * Trim a text to a given length, taking html entities into account.
 * Uses the htmLawed library to fix html issues and recursively runs over the
 * input text.
 *
 * @param string $str String to trim
 * @param int $desiredLength Target string length
 * @param bool $nbsp Transform spaces to their html entity
 * @param bool $hellip Add dots when the string is too long
 * @param bool $striptags Strip html tags
 * @param string $returnString String pass for recursion
 * @param int $length String length pass for recursion
 * @return array With two keys: 'string' (resulting string) and length (string length)
 */
function recursiveTrimText($str, $desiredLength, $nbsp = false, $hellip = true, $striptags = true, $returnString = '', $length = 0){
    require_once(BOLT_PROJECT_ROOT_DIR.'/vendor/htmlawed/htmlawed/htmLawed.php');
    $config = array('tidy'=>1, 'schemes'=>'*:*', 'balance' => '1');
    // htmLawed trims whitespaces and setting keep_bad to 6 doesn't keep it
    // from doing it on the beginning of the string :(
    $lSpaceCount = getLeftWhiteSpaceCount($str);
    $str = str_repeat(" ", $lSpaceCount) . htmLawed($str, $config);

    // Base case: no html or strip_tags so we treat the content of this clause
    // as a regular string of which we return the result string and length.
    if ($striptags == true || !containsHTML($str)){
        $targetLength = $desiredLength - $length;
        $trimResult = trimString(strip_tags($str), $targetLength, $nbsp, $hellip);
        return array(
            'string' => $returnString . $trimResult['string'],
            'length' => $length + $trimResult['length'],
        );
    }
    else {
        // Recursive case. Steps:
        // 1) We check for tags
        // 2) We split at the first tag ($matches[0][0][0])
        // 3) We do recursiveFunction on the first part (contains no HTML)
        // 4) If we don't exceed the length yet, we need to treat the matched
        //      tag of $matches[0][0][0]. Split off the tags and put them
        //      back later. Call recursiveFunction on the content.
        // 5) If we still haven't exceeded the length, call recursiveFunction on
        //      the remainder of the split.

        // Step 1: check for tags
        preg_match_all("/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", $str, $matches, PREG_OFFSET_CAPTURE);

        // We MUST have a match as this method is also used in the containsHTML
        // method. Therefor we do not check if an array index exists.

        // Shorthands to make stuff more readable
        $matchedHTML = $matches[0][0][0];
        $matchedHTMLIndex = $matches[0][0][1];
        $matchedHTMLLength = getStringLength($matchedHTML);
        $openingTag = $matches[1][0][0];
        $content = $matches[3][0][0];
        $closingTag = $matches[4][0][0];

        // Step 2: Split at the first tag
        $head = mb_substr($str, 0, $matchedHTMLIndex, "UTF-8");
        $tail = mb_substr($str, $matchedHTMLIndex + $matchedHTMLLength, mb_strlen($str), "UTF-8");

        // Step 3: Do recursiveFunction on first part
        if ($head != ''){
            $headRes = recursiveTrimText($head, $desiredLength, $nbsp, $hellip, $striptags, $returnString, $length);
            $returnString = $headRes['string'];
            $length = $headRes['length'];
            if ($headRes['length'] >= $desiredLength){
                return array('length' => $length, 'string' => $returnString);
            }
        }
        // Step 4: Apparently length not exceeded, get length of $matchedHTML
        $returnString .= $openingTag;
        $matchRes = recursiveTrimText($content, $desiredLength, $nbsp, $hellip, $striptags, $returnString, $length);
        $returnString = $matchRes['string'] . $closingTag;
        if ($matchRes['length'] >= $desiredLength  || $tail == ''){
            return array('length' => $matchRes['length'], 'string' => $returnString);
        }
        // Step 5: Apparently length still not exceeded, recurse on $tail
        $length = $matchRes['length'];
        // (already set $returnString)
        return recursiveTrimText($tail, $desiredLength, $nbsp, $hellip, $striptags, $returnString, $length);
    }
}

/**
 * Trims the given string to a particular length. Does plain trimming.
 *
 * @param string $str Input string
 * @param int $trimLength Desired length
 * @param bool $nbsp Convert spaces to html entity
 * @param bool $hellip Replace the trimmed part with dots
 * @return array Array with two keys: 'string' and 'length'
 */
function trimString($str, $trimLength, $nbsp = false, $hellip = true){
    $strLength = getStringLength($str);
    if ($strLength > $trimLength) {
        $str = mb_substr($str, 0, $trimLength, "UTF-8");
        $resultingLength = $trimLength;
        if ($hellip) {
            $str .= '…';
        }
    }
    else {
        $resultingLength = $strLength;
    }

    if ($nbsp==true) {
        $str = str_replace(" ", "&nbsp;", $str);
    }

    return array(
        'string' => $str,
        'length' => $resultingLength,
    );
}

/**
 * String length wrapper. Uses mb_strwidth when available. Fallback to strlen.
 * @param string $str
 * @return int String length
 */
function getStringLength($str){
    if (function_exists('mb_strwidth') ) {
        return mb_strwidth($str, "UTF-8");
    } else {
        return strlen($str);
    }
}

/**
 * parse the used .twig templates from the Twig Loader object, using
 * regular expressions.
 * We use this for showing them in the debug toolbar.
 *
 * @param object $obj
 *
 */
function hackislyParseRegexTemplates($obj)
{
    $str = print_r($obj, true);

    preg_match_all('/(\/[a-z0-9_\/-]+\.twig)/i', $str, $matches);

    $templates = array();

    foreach ($matches[1] as $match) {
        $templates[] = basename(dirname($match)) . "/" . basename($match);
    }

    return $templates;

}



function getPaths($original = array() )
{

    // If we passed the entire $app, set the $config
    if ($original instanceof \Bolt\Application) {
        if (!empty($original['canonicalpath'])) {
            $canonicalpath = $original['canonicalpath'];
        }
        $config = $original['config'];
    } else {
        $config = $original;
    }

    // Make sure $config is not empty. This is for when this function is called
    // from lowlevelError().
    // Temp fix! @todo: Fix this properly.
    if ($config instanceof \Bolt\Config) {
        if (!$config->get('general/theme')) {
            $config->set('general/theme', 'base-2013');
        }
        if (!$config->get('general/canonical') && isset($_SERVER['HTTP_HOST'])) {
            $config->set('general/canonical', $_SERVER['HTTP_HOST']);
        }

        // Set the correct mountpoint..
        if ($config->get('general/branding/path')) {
            $mountpoint = substr($config->get('general/branding/path'), 1) . "/";
        } else {
            $mountpoint = "bolt/";
        }

        $theme = $config->get('general/theme');

        $canonical = $config->get('general/canonical', "");

    } else {
        if (empty($config['general']['theme'])) {
            $config['general']['theme'] = 'base-2013';
        }
        if (empty($config['general']['canonical']) && isset($_SERVER['HTTP_HOST'])) {
            $config['general']['canonical'] = $_SERVER['HTTP_HOST'];
        }

        // Set the correct mountpoint..
        if (!empty($config['general']['branding']['path'])) {
            $mountpoint = substr($config['general']['branding']['path'], 1) . "/";
        } else {
            $mountpoint = "bolt/";
        }

        $theme = $config['general']['theme'];

        $canonical = isset($config['general']['canonical']) ? $config['general']['canonical'] : "";

    }

    // Set the root
    $path_prefix = dirname($_SERVER['PHP_SELF'])."/";
    $path_prefix = preg_replace("/^[a-z]:/i", "", $path_prefix);
    $path_prefix = str_replace("//", "/", str_replace("\\", "/", $path_prefix));
    if (empty($path_prefix) || 'cli-server' === php_sapi_name()) {
        $path_prefix = "/";
    }

    // make sure we're not trying to access bolt as "/index.php/bolt/", because all paths will be broken.
    if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], "/index.php") !== false) {
        simpleredirect(str_replace("/index.php", "", $_SERVER['REQUEST_URI']));
    }

    if (!empty($_SERVER["SERVER_PROTOCOL"])) {
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? 'https' : 'http';
    } else {
        $protocol = "cli";
    }

    $currentpath = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "/";

    if (empty($canonicalpath)) {
        $canonicalpath = $currentpath;
    }

    // Set the paths
    $paths = array(
        'hostname' => !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "localhost",
        'root' => $path_prefix,
        'rootpath' => realpath(__DIR__ . "/../../"),
        'theme' => $path_prefix . "theme/" . $theme . "/",
        'themepath' => realpath(__DIR__ . "/../../theme/" . $theme),
        'app' => $path_prefix . "app/",
        'apppath' => realpath(__DIR__ . "/.."),
        'bolt' => $path_prefix . $mountpoint,
        'async' => $path_prefix . "async/",
        'files' => $path_prefix . "files/",
        'filespath' => realpath(__DIR__ . "/../../files"),
        'canonical' => $canonical,
        'current' => $currentpath
    );

    $paths['hosturl'] = sprintf("%s://%s", $protocol, $paths['hostname']);
    $paths['rooturl'] = sprintf("%s://%s%s", $protocol, $paths['canonical'], $paths['root']);
    $paths['canonicalurl'] = sprintf("%s://%s%s", $protocol, $paths['canonical'], $canonicalpath);
    $paths['currenturl'] = sprintf("%s://%s%s", $protocol, $paths['hostname'], $currentpath);

    // Temp fix! @todo: Fix this properly.
    if ($config instanceof \Bolt\Config) {
        if ($config->get('general/theme_path')) {
            $paths['themepath'] = BOLT_PROJECT_ROOT_DIR . $config->get('general/theme_path');
        }
    } else {
        if ( isset( $config['general']['theme_path'] ) ) {
            $paths['themepath'] = BOLT_PROJECT_ROOT_DIR . $config['general']['theme_path'];
        }
    }

    if ( BOLT_COMPOSER_INSTALLED ) {
        $paths['app'] = $path_prefix . "bolt-public/";
    }

    // Set it in $app, optionally.
    if ($original instanceof \Bolt\Application) {
        $original['paths'] = $paths;
        $original['twig']->addGlobal('paths', $paths);
    }

    return $paths;

}



/**
 *
 * Simple wrapper for $app['url_generator']->generate()
 *
 * @param string $path
 * @param array $param
 * @param string $add
 * @return string
 */
function path($path, $param = array(), $add = '')
{
    global $app;

    if (!empty($add) && $add[0]!="?") {
        $add = "?" . $add;
    }

    if (empty($param)) {
        $param = array();
    }

    return $app['url_generator']->generate($path, $param). $add;

}

/**
 *
 * Simple wrapper for $app->redirect($app['url_generator']->generate());
 *
 * @param string $path
 * @param array $param
 * @param string $add
 * @return string
 */
function redirect($path, $param = array(), $add = '')
{
    global $app;

    return $app->redirect(path($path, $param, $add));

}


/**
 * Create a simple redirect to a page / path and die.
 *
 * @param string $path
 * @param boolean $die
 */
function simpleredirect($path, $die = true)
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
 * Apparently, some servers don't have fnmatch. Define it here, for those who don't have it.
 *
 * @see http://www.php.net/manual/en/function.fnmatch.php#100207
 *
 */
if (!function_exists('fnmatch')) {
    define('FNM_PATHNAME', 1);
    define('FNM_NOESCAPE', 2);
    define('FNM_PERIOD', 4);
    define('FNM_CASEFOLD', 16);

    /**
     * Match filename against a pattern
     *
     * @param  string $pattern
     * @param  string $string
     * @param  int    $flags
     * @return bool
     */
    function fnmatch($pattern, $string, $flags = 0)
    {
        return pcreFnmatch($pattern, $string, $flags);
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
function pcreFnmatch($pattern, $string, $flags = 0)
{
    $modifiers = null;
    $transforms = array(
        '\*'    => '.*',
        '\?'    => '.',
        '\[\!'    => '[^',
        '\['    => '[',
        '\]'    => ']',
        '\.'    => '\.',
        '\\'    => '\\\\'
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
function isHtml($html)
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
 * Detect whether or not a given string is (likely) HTML. It has a different
 * approach than the isHTML method. It's stricter (it assumes well-balanced
 * tags) but more accurate.
 * @param string $str
 * @return bool True if the string contains any html tags and, with that, is HTML
 */
function containsHTML($str)
{
    preg_match_all("/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", $str, $matches, PREG_OFFSET_CAPTURE);
    return !empty($matches[3]);
}

/**
 * Simple PHP Browser Detection
 */
function getBrowserInfo() {

    // Create a new Browscap object (loads or creates the cache)
    $bc = new \phpbrowscap\Browscap(dirname(__DIR__)."/resources/browscap/");

    $bc->doAutoUpdate = false;

    try {
        $browser = $bc->getBrowser()->Parent;
        if (strpos($bc->getBrowser()->browser_name, "CriOS") > 0) {
            $browser = "Chrome";
        }

        $platformversion = ($bc->getBrowser()->Platform_Version == "unknown") ? "" : $bc->getBrowser()->Platform_Version;

        $browser = sprintf("%s / %s %s",
            $browser,
            $bc->getBrowser()->Platform,
            $platformversion
        );

    } catch (Exception $e) {
        $browser = "Unknown";
    }
    //\util::var_dump($bc->getBrowser());

    return trim($browser);

}

/**
 * Update our app/resources/browscap/ files.
 */
function updateBrowscap() {

    // Create a new Browscap object (loads or creates the cache)
    $bc = new \phpbrowscap\Browscap(dirname(__DIR__)."/resources/browscap/");

    $bc->doAutoUpdate = true;

    $browser = $bc->getBrowser();

    print_r($browser);

}



/**
 * Loads a serialized file, unserializes it, and returns it.
 *
 * If the file isn't readable (or doesn't exist) or reading it fails,
 * false is returned.
 *
 * @param string $filename
 * @param boolean $silent Set to true if you want an visible error.
 * @return mixed
 */
function loadSerialize($filename, $silent=false) {

    $filename = fixpath($filename);

    if (!is_readable($filename)) {

        // If we're setting up PivotX, we can't set the paths before we initialise
        // the configuration and vice-versa. So, we just bail out if the paths aren't
        // set yet.
        if(empty($PIVOTX['paths']['pivotx_path'])) { return; }

        if (is_readable($PIVOTX['paths']['pivotx_path'].$filename)) {
            $filename = $PIVOTX['paths']['pivotx_path'].$filename;
        } else {
            $filename = "../".$filename;
        }
    }

    if (!is_readable($filename)) {

        if ($silent) {
            return FALSE;
        }

        $message = sprintf(__("<p>The following file could not be read:</p>%s" .
            "<p>Try logging in with your ftp-client and make the file readable. " .
            "Else try to go <a href='javascript:history.go(-1)'>back</a> to the last page.</p>"),
            '<pre>' . htmlspecialchars($filename) . '</pre>'
        );
        renderErrorpage(__("File is not readable!"), $message);
    }

    $serialized_data = trim(implode("", file($filename)));

    $serialized_data = str_replace("<?php /* bolt */ die(); ?>", "", $serialized_data);

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
                return FALSE;
            }
        }
    }
}

// This function serializes some data and then saves it.
function saveSerialize($filename, &$data) {

    $filename = fixPath($filename);

    $ser_string = "<?php /* bolt */ die(); ?>".serialize($data);

    // disallow user to interrupt
    ignore_user_abort(true);

    $old_umask = umask(0111);

    // open the file and lock it.
    if($fp=fopen($filename, "a")) {

        if (flock( $fp, LOCK_EX | LOCK_NB )) {

            // Truncate the file (since we opened it for 'appending')
            ftruncate($fp, 0);

            // Write to our locked, empty file.
            if (fwrite($fp, $ser_string)) {
                flock( $fp, LOCK_UN );
                fclose($fp);
            } else {
                flock( $fp, LOCK_UN );
                fclose($fp);

                // todo: handle errors better.
                echo("Error opening file<br/><br/>The file <b>$filename</b> could not be written! <br /><br />Try logging in with your ftp-client and check to see if it is chmodded to be readable by the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />Current path: ".getcwd()."." );
                die();
                return false;
            }

        } else {
            fclose($fp);

            // todo: handle errors better.
            echo("Error opening file<br/><br/>Could not lock <b>$filename</b> for writing! <br /><br />Try logging in with your ftp-client and check to see if it is chmodded to be readable by the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />Current path: ".getcwd()."." );
            die();
            return false;

        }

    } else {
        // todo: handle errors better.
        echo("Error opening file<br/><br/>The file <b>$filename</b> could not be opened for writing! <br /><br />Try logging in with your ftp-client and check to see if it is chmodded to be readable by the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />Current path: ".getcwd()."." );
        debug_printbacktrace();
        die();
        return false;
    }
    umask($old_umask);

    // reset the users ability to interrupt the script
    ignore_user_abort(false);


    return true;

}



/**
 * Replace the first occurence of a string only. Behaves like str_replace, but
 * replaces _only_ the _first_ occurence.
 * @see http://stackoverflow.com/a/2606638
 */
function str_replace_first($search, $replace, $subject) {
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
 */
function array_merge_recursive_distinct (array &$array1, array &$array2) {
    $merged = $array1;

    foreach($array2 as $key => &$value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = array_merge_recursive_distinct($merged [$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }

    return $merged;
}



/**
 * Checks if the text is a valid email address.
 *
 * Given a chain it returns true if $theAdr conforms to RFC 2822.
 * It does not check the existence of the address.
 * Suppose a mail of the form
 *  <pre>
 *  addr-spec     = local-part "@" domain
 *  local-part    = dot-atom / quoted-string / obs-local-part
 *  dot-atom      = [CFWS] dot-atom-text [CFWS]
 *  dot-atom-text = 1*atext *("." 1*atext)
 *  atext         = ALPHA / DIGIT /    ; Any character except controls,
 *        "!" / "#" / "$" / "%" /      ;  SP, and specials.
 *        "&" / "'" / "*" / "+" /      ;  Used for atoms
 *        "-" / "/" / "=" / "?" /
 *        "^" / "_" / "`" / "{" /
 *        "|" / "}" / "~" / "." /
 * </pre>
 *
 * @param string $theAdr
 * @return boolean
 */
function isEmail($theAdr) {

    // default
    $result = FALSE;

    // go ahead
    if(( ''!=$theAdr )||( is_string( $theAdr ))) {
        $mail_array = explode( '@',$theAdr );
    }

    if( !is_array( $mail_array )) { return FALSE; }

    if( 2 == count( $mail_array )) {
        $localpart = $mail_array[0];
        $domain_array  = explode( '.',$mail_array[1] );
    } else {
        return FALSE;
    }
    if( !is_array( $domain_array ))  { return FALSE; }
    if( 1 == count( $domain_array )) { return FALSE; }

    /* relevant info:
     * $mail_array[0] contains atext
     * $adr_array  contains parts of address
     *          and last one must be at least 2 chars
     */

    $domain_toplevel = array_pop( $domain_array );
    if(is_string($domain_toplevel) && (strlen($domain_toplevel) > 1)) {
        // put back
        $domain_array[] = $domain_toplevel;
        $domain = implode( '',$domain_array );
        // now we have two string to test
        // $domain and $localpart
        $domain    = preg_replace( "/[a-z0-9]/i","",$domain );
        $domain    = preg_replace( "/[-|\_]/","",$domain );
        $localpart = preg_replace( "/[a-z0-9]/i","",$localpart);
        $localpart = preg_replace(
            "#[-.|\!|\#|\$|\%|\&|\'|\*|\+|\/|\=|\? |\^|\_|\`|\{|\||\}|\~]#","",$localpart);
        // If there are no characters left in localpart or domain, the
        // email address is valid.
        if(( '' == $domain )&&( '' == $localpart )) { $result = TRUE; }
    }

    return $result;
}



/**
 * Checks whether the text is an URL or not.
 *
 * @param string $url
 * @return boolean
 */
function isUrl($url) {

    return (preg_match("/((ftp|https?):\/\/)?([a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)+(com\b|edu\b|biz\b|org\b|gov\b|in(?:t|fo)\b|mil\b|net\b|name\b|museum\b|coop\b|aero\b|[a-z][a-z]\b|[0-9]{1,3})/i",$url));

}



/**
 * i18n made right, second attempt...
 *
 * Instead of calling directly $app['translator']->trans(), we check
 * for the presence of a placeholder named '%contentype%'.
 *
 * if one is found, we replace it with the contenttype.name parameter,
 * and try to get a translated string. If there is not, we revert to
 * the generic (%contenttype%) string, which must have a translation.
 *
 */
function __() {
    global $app;
    $num_args = func_num_args();
    if (0==$num_args) {
        return null;
    }
    $args = func_get_args();
    if ($num_args > 4) {
        $fn = 'transChoice';
    } elseif ($num_args == 1 || is_array($args[1])) {
        // if only 1 arg or 2nd arg is an array call trans
        $fn = 'trans';
    } else {
        $fn = 'transChoice';
    }
    $tr_args=null;
    if ( $fn == 'trans' && $num_args > 1) {
        $tr_args = $args[1];
    } elseif ($fn == 'transChoice' && $num_args > 2) {
        $tr_args = $args[2];
    }
    // check for contenttype(s) placeholder
    if ($tr_args) {
        $keytype='%contenttype%';
        $keytypes='%contenttypes%';
        $have_singular = array_key_exists($keytype,$tr_args);
        $have_plural = array_key_exists($keytypes,$tr_args);
        if ($have_singular || $have_plural) {
            // have a %contenttype% placeholder, try to find a specialized translation
            if ($have_singular) {
                $text=str_replace($keytype,$tr_args[$keytype],$args[0]);
                unset($tr_args[$keytype]);
            } else {
                $text=str_replace($keytypes,$tr_args[$keytypes],$args[0]);
                unset($tr_args[$keytypes]);
            }
            //echo "\n" . '<!-- contenttype replaced: '.htmlentities($text)." -->\n";
            if ($fn == 'transChoice') {
                    $trans = $app['translator']->transChoice(
                        $text,$args[1],$tr_args,
                        isset($args[3]) ? $args[3] : 'contenttypes',
                        isset($args[4]) ? $args[4] : $app['request']->getLocale()
                    );
            } else {
                    $trans = $app['translator']->trans(
                        $text,$tr_args,
                        isset($args[2]) ? $args[2] : 'contenttypes',
                        isset($args[3]) ? $args[3] : $app['request']->getLocale()
                    );
            }
            //echo '<!-- translation : '.htmlentities($trans)." -->\n";
            if ($text != $trans) {
                return $trans;
            }
        }
    }

    //try {
    switch($num_args) {
        case 5:
            return $app['translator']->transChoice($args[0],$args[1],$args[2],$args[3],$args[4]);
        case 4:
            //echo "<!-- 4. call: $fn($args[0],$args[1],$args[2],$args[3]) -->\n";
            return $app['translator']->$fn($args[0],$args[1],$args[2],$args[3]);
        case 3:
            //echo "<!-- 3. call: $fn($args[0],$args[1],$args[2]) -->\n";
            return $app['translator']->$fn($args[0],$args[1],$args[2]);
        case 2:
            //echo "<!-- 2. call: $fn($args[0],$args[1] -->\n";
            return $app['translator']->$fn($args[0],$args[1]);
        case 1:
            //echo "<!-- 1. call: $fn($args[0]) -->\n";
            return $app['translator']->$fn($args[0]);
    }
    /*}
    catch (\Exception $e) {
        echo "<!-- ARGHH !!! -->\n";
        //return $args[0];
        die($e->getMessage());
    }*/
}

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Escaper;

/**
 * find all twig templates and bolt php code, extract translatables
 * strings, merge with existing translations, return
 *
 */
function gatherTranslatableStrings($locale=null,$translated=array())
{
    global $app;

    $isPhp = function($fname) {
        return pathinfo(strtolower($fname), PATHINFO_EXTENSION) == 'php';
    };

    $isTwig = function($fname) {
        return pathinfo(strtolower($fname), PATHINFO_EXTENSION) == 'twig';
    };

    $ctypes = $app['config']->get('contenttypes');

    // function that generates a string for each variation of contenttype/contenttypes
    $genContentTypes = function($txt) use ($ctypes) {
        $stypes=array();
        if (strpos($txt,'%contenttypes%') !== false) {
            foreach ($ctypes as $key => $ctype) {
                $stypes[]=str_replace('%contenttypes%',$ctype['name'],$txt);
            }
        }
        if (strpos($txt,'%contenttype%') !== false) {
            foreach ($ctypes as $key => $ctype) {
                $stypes[]=str_replace('%contenttype%',$ctype['singular_name'],$txt);
            }
        }
        return $stypes;
    };

    // step one: gather all translatable strings

    $finder = new Finder();
    $finder->files()
        ->ignoreVCS(true)
        ->name('*.twig')
        ->name('*.php')
        ->notName('*~')
        ->exclude(array('cache','config','database','resources','tests'))
        ->in(BOLT_PROJECT_ROOT_DIR.'/theme') //
        ->in(BOLT_PROJECT_ROOT_DIR.'/app')
    ;
    // regex from: stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
    $re_dq = '/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s';
    $re_sq = "/'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'/s";
    $nstr=0;
    $strings=array();
    foreach ($finder as $file) {
        $s = file_get_contents($file);

        // scan twig templates for  __('...' and __("..."
        if ($isTwig($file)) {
            // __('single_quoted_string'...
            if (preg_match_all("/\b__\(\s*'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'(?U).*\)/s",$s,$matches)) {
                //print_r($matches[1]);
                foreach($matches[1] as $t) {
                    $nstr++;
                    if (!in_array($t,$strings)) {
                        $strings[]=$t;
                        sort($strings);
                    }
                }
            }
            // __("double_quoted_string"...
            if (preg_match_all('/\b__\(\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"(?U).*\)/s',$s,$matches)) {
                //print_r($matches[1]);
                foreach($matches[1] as $t) {
                    $nstr++;
                    if (!in_array($t,$strings)) {
                        $strings[]=$t;
                        sort($strings);
                    }
                }
            }
        }

        // php :
        /** all translatables strings have to be called with:
         *  __("text",$params=array(),$domain='messages',locale=null) // $app['translator']->trans()
         *  __("text",count,$params=array(),$domain='messages',locale=null) // $app['translator']->transChoice()
         */
        if ($isPhp($file)) {
            $tokens = token_get_all($s);
            $num_tokens = count($tokens);
            for ($x=0; $x < $num_tokens; $x++) {
                $token = $tokens[$x];
                if (is_array($token) && $token[0] == T_STRING && $token[1] == '__') {
                    $token = $tokens[++$x];
                    if ($x < $num_tokens && is_array($token) && $token[0] == T_WHITESPACE) {
                        $token = $tokens[++$x];
                    }
                    if ($x < $num_tokens && !is_array($token) && $token == '(') {
                        // in our func args...
                        $token = $tokens[++$x];
                        if ($x < $num_tokens && is_array($token) && $token[0] == T_WHITESPACE) {
                            $token = $tokens[++$x];
                        }
                        if (!is_array($token)) {
                            // give up
                            continue;
                        }
                        if ($token[0] == T_CONSTANT_ENCAPSED_STRING ) {
                            $t = substr($token[1],1,strlen($token[1])-2);
                            $nstr++;
                            if (!in_array($t,$strings)) {
                                $strings[]=$t;
                                sort($strings);
                            }
                            // TODO: retrieve domain ?
                        }
                    }
                }
            }// end for $x
        }
    }

    // add fields name|label for contenttype (forms)
    foreach($ctypes as $ckey => $contenttype) {
        foreach($contenttype['fields'] as $fkey => $field) {
            if (isset($field['label'])) {
                $t = $field['label'];
            } else {
                $t = ucfirst($fkey);
            }
            if (!in_array($t,$strings)) {
                $strings[]=$t;
            }
        }
        // relation name|label if exists
        if (array_key_exists('relations',$contenttype)) {
            foreach($contenttype['relations'] as $fkey => $field) {
                if (isset($field['label'])) {
                    $t = $field['label'];
                } else {
                    $t = ucfirst($fkey);
                }
                if (!in_array($t,$strings)) {
                    $strings[]=$t;
                }
            }
        }
    }

    // add name + singular_name for taxonomies
    foreach($app['config']->get('taxonomy') as $txkey => $value) {
        foreach(array('name','singular_name') as $key) {
            $t = $value[$key];
            if (!in_array($t,$strings)) {
                $strings[]=$t;
            }
        }
    }

    // return the previously translated string if exists,
    // return an empty string otherwise
    $getTranslated = function($key) use ($app, $translated) {
        if ( ($trans = $app['translator']->trans($key)) == $key ) {
            if (is_array($translated) && array_key_exists($key, $translated) && !empty($translated[$key])) {
                return $translated[$key];
            }
            return '';
        }
        return $trans;
    };

    // step 2: find already translated strings

    sort($strings);
    if (!$locale) {
        $locale = $app['request']->getLocale();
    }
    $msg_domain = array(
        'translated' => array(),
        'not_translated'=>array()
    );
    $ctype_domain=array(
        'translated'=>array(),
        'not_translated'=>array()
    );

    foreach($strings as $idx=>$key) {
        $key = stripslashes($key);
        $raw_key = $key;
        $key = Escaper::escapeWithDoubleQuotes($key);
        if ( ($trans = $getTranslated($raw_key)) == '' && ($trans = $getTranslated($key)) == '' ) {
            $msg_domain['not_translated'][] = $key;
        } else {
            $trans = Escaper::escapeWithDoubleQuotes($trans);
            $msg_domain['translated'][$key] = $trans;
        }
        // step 3: generate additionals strings for contenttypes
        if (strpos($raw_key,'%contenttype%') !== false || strpos($raw_key,'%contenttypes%') !== false) {
            foreach($genContentTypes($raw_key) as $ctypekey) {
                $key = Escaper::escapeWithDoubleQuotes($ctypekey);
                if ( ($trans = $getTranslated($ctypekey)) == '' && ($trans = $getTranslated($key)) == '' ) {
                    // not translated
                    $ctype_domain['not_translated'][] = $key;
                } else {
                    $trans = Escaper::escapeWithDoubleQuotes($trans);
                    $ctype_domain['translated'][$key] = $trans;
                }
            }
        }
    }

    sort($msg_domain['not_translated']);
    ksort($msg_domain['translated']);

    sort($ctype_domain['not_translated']);
    ksort($ctype_domain['translated']);

    return array($msg_domain,$ctype_domain);
}
