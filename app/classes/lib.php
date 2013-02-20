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
        $app['session']->setFlash('error', "The security token was incorrect. Please try again.");

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
    if (version_compare($currentversion, $requiredversion) > -1) {
        return true;
    } else {
        return false;
    }
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

    $path = str_replace("\/", "/", stripTrailingSlash($path));

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
function makeKey($length)
{
    $seed = "0123456789abcdefghijklmnopqrstuvwxyz";
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
    // replace UTF-8 non ISO-8859-1 first
    $str = strtr($str, array(
        "\xC3\x80"=>'A', "\xC3\x81"=>'A', "\xC3\x82"=>'A', "\xC3\x83"=>'A',
        "\xC3\x84"=>'A', "\xC3\x85"=>'A', "\xC3\x87"=>'C', "\xC3\x88"=>'E',
        "\xC3\x89"=>'E', "\xC3\x8A"=>'E', "\xC3\x8B"=>'E', "\xC3\x8C"=>'I',
        "\xC3\x8D"=>'I', "\xC3\x8E"=>'I', "\xC3\x8F"=>'I', "\xC3\x90"=>'D',
        "\xC3\x91"=>'N', "\xC3\x92"=>'O', "\xC3\x93"=>'O', "\xC3\x94"=>'O',
        "\xC3\x95"=>'O', "\xC3\x96"=>'O', "\xC3\x97"=>'x', "\xC3\x98"=>'O',
        "\xC3\x99"=>'U', "\xC3\x9A"=>'U', "\xC3\x9B"=>'U', "\xC3\x9C"=>'U',
        "\xC3\x9D"=>'Y', "\xC3\xA0"=>'a', "\xC3\xA1"=>'a', "\xC3\xA2"=>'a',
        "\xC3\xA3"=>'a', "\xC3\xA4"=>'a', "\xC3\xA5"=>'a', "\xC3\xA7"=>'c',
        "\xC3\xA8"=>'e', "\xC3\xA9"=>'e', "\xC3\xAA"=>'e', "\xC3\xAB"=>'e',
        "\xC3\xAC"=>'i', "\xC3\xAD"=>'i', "\xC3\xAE"=>'i', "\xC3\xAF"=>'i',
        "\xC3\xB1"=>'n', "\xC3\xB2"=>'o', "\xC3\xB3"=>'o', "\xC3\xB4"=>'o',
        "\xC3\xB5"=>'o', "\xC3\xB6"=>'o', "\xC3\xB8"=>'o', "\xC3\xB9"=>'u',
        "\xC3\xBA"=>'u', "\xC3\xBB"=>'u', "\xC3\xBC"=>'u', "\xC3\xBD"=>'y',
        "\xC3\xBF"=>'y', "\xC4\x80"=>'A', "\xC4\x81"=>'a', "\xC4\x82"=>'A',
        "\xC4\x83"=>'a', "\xC4\x84"=>'A', "\xC4\x85"=>'a', "\xC4\x86"=>'C',
        "\xC4\x87"=>'c', "\xC4\x88"=>'C', "\xC4\x89"=>'c', "\xC4\x8A"=>'C',
        "\xC4\x8B"=>'c', "\xC4\x8C"=>'C', "\xC4\x8D"=>'c', "\xC4\x8E"=>'D',
        "\xC4\x8F"=>'d', "\xC4\x90"=>'D', "\xC4\x91"=>'d', "\xC4\x92"=>'E',
        "\xC4\x93"=>'e', "\xC4\x94"=>'E', "\xC4\x95"=>'e', "\xC4\x96"=>'E',
        "\xC4\x97"=>'e', "\xC4\x98"=>'E', "\xC4\x99"=>'e', "\xC4\x9A"=>'E',
        "\xC4\x9B"=>'e', "\xC4\x9C"=>'G', "\xC4\x9D"=>'g', "\xC4\x9E"=>'G',
        "\xC4\x9F"=>'g', "\xC4\xA0"=>'G', "\xC4\xA1"=>'g', "\xC4\xA2"=>'G',
        "\xC4\xA3"=>'g', "\xC4\xA4"=>'H', "\xC4\xA5"=>'h', "\xC4\xA6"=>'H',
        "\xC4\xA7"=>'h', "\xC4\xA8"=>'I', "\xC4\xA9"=>'i', "\xC4\xAA"=>'I',
        "\xC4\xAB"=>'i', "\xC4\xAC"=>'I', "\xC4\xAD"=>'i', "\xC4\xAE"=>'I',
        "\xC4\xAF"=>'i', "\xC4\xB0"=>'I', "\xC4\xB1"=>'i', "\xC4\xB4"=>'J',
        "\xC4\xB5"=>'j', "\xC4\xB6"=>'K', "\xC4\xB7"=>'k', "\xC4\xB8"=>'k',
        "\xC4\xB9"=>'L', "\xC4\xBA"=>'l', "\xC4\xBB"=>'L', "\xC4\xBC"=>'l',
        "\xC4\xBD"=>'L', "\xC4\xBE"=>'l', "\xC4\xBF"=>'L', "\xC5\x80"=>'l',
        "\xC5\x81"=>'L', "\xC5\x82"=>'l', "\xC5\x83"=>'N', "\xC5\x84"=>'n',
        "\xC5\x85"=>'N', "\xC5\x86"=>'n', "\xC5\x87"=>'N', "\xC5\x88"=>'n',
        "\xC5\x89"=>'n', "\xC5\x8A"=>'N', "\xC5\x8B"=>'n', "\xC5\x8C"=>'O',
        "\xC5\x8D"=>'o', "\xC5\x8E"=>'O', "\xC5\x8F"=>'o', "\xC5\x90"=>'O',
        "\xC5\x91"=>'o', "\xC5\x94"=>'R', "\xC5\x95"=>'r', "\xC5\x96"=>'R',
        "\xC5\x97"=>'r', "\xC5\x98"=>'R', "\xC5\x99"=>'r', "\xC5\x9A"=>'S',
        "\xC5\x9B"=>'s', "\xC5\x9C"=>'S', "\xC5\x9D"=>'s', "\xC5\x9E"=>'S',
        "\xC5\x9F"=>'s', "\xC5\xA0"=>'S', "\xC5\xA1"=>'s', "\xC5\xA2"=>'T',
        "\xC5\xA3"=>'t', "\xC5\xA4"=>'T', "\xC5\xA5"=>'t', "\xC5\xA6"=>'T',
        "\xC5\xA7"=>'t', "\xC5\xA8"=>'U', "\xC5\xA9"=>'u', "\xC5\xAA"=>'U',
        "\xC5\xAB"=>'u', "\xC5\xAC"=>'U', "\xC5\xAD"=>'u', "\xC5\xAE"=>'U',
        "\xC5\xAF"=>'u', "\xC5\xB0"=>'U', "\xC5\xB1"=>'u', "\xC5\xB2"=>'U',
        "\xC5\xB3"=>'u', "\xC5\xB4"=>'W', "\xC5\xB5"=>'w', "\xC5\xB6"=>'Y',
        "\xC5\xB7"=>'y', "\xC5\xB8"=>'Y', "\xC5\xB9"=>'Z', "\xC5\xBA"=>'z',
        "\xC5\xBB"=>'Z', "\xC5\xBC"=>'z', "\xC5\xBD"=>'Z', "\xC5\xBE"=>'z',
        ));

    // utf8_decode assumes that the input is ISO-8859-1 characters encoded
    // with UTF-8. This is OK since we want US-ASCII in the end.
    $str = trim(utf8_decode($str));

    $str = strtr($str, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH",
        "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));

    $str=str_replace("&amp;", "", $str);

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
    require_once __DIR__ . '/htmLawed/htmLawed.php';
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
        $head = substr($str, 0, $matchedHTMLIndex);
        $tail = substr($str, $matchedHTMLIndex + $matchedHTMLLength);

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
        $str = substr($str, 0, $trimLength);
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
        return mb_strwidth($str);
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

function getConfig()
{
    global $app;

    $config = array();

    // Read the config
    $yamlparser = new Symfony\Component\Yaml\Parser();
    $config['general'] = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/config.yml') . "\n");

    $config['taxonomy'] = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/taxonomy.yml') . "\n");
    $tempcontenttypes = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/contenttypes.yml') . "\n");
    $config['menu'] = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/menu.yml') . "\n");

    // @todo: What is this? Do we want this 'local' config?
    if(file_exists(BOLT_CONFIG_DIR.'/config_local.yml')) {
        $localconfig = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/config_local.yml') . "\n");
        $config['general'] = array_merge($config['general'], $localconfig);
    }

    // @todo: If no config files can be found, get them from bolt.cm/files/default/

    // Assume some sensible defaults for some options
    $defaultconfig = array(
        'sitename' => 'Default Bolt site',
        'homepage' => 'page/*',
        'homepage_template' => 'index.twig',
        'locale' => 'en_GB',
        'sitemap_template' => 'sitemap.twig',
        'sitemap_xml_template' => 'sitemap_xml.twig',
        'recordsperpage' => 10,
        'recordsperdashboardwidget' => 5,
        'debug' => false,
        'strict_variables' => false,
        'theme' => "default",
        'debug_compressjs' => true,
        'debug_compresscss' => true,
        'listing_template' => 'listing.twig',
        'listing_records' => '5',
        'listing_sort' => 'datepublish DESC',
        'wysiwyg_images' => false,
        'wysiwyg_tables' => false,
        'wysiwyg_embed' => false,
        'wysiwyg_fontcolor' => false,
        'wysiwyg_align' => false,
        'canonical' => !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "",
        'developer_notices' => false,
        'cookies_use_remoteaddr' => true,
        'cookies_use_browseragent' => false,
        'cookies_use_httphost' => true,
        'cookies_https_only' => false,
        'cookies_lifetime' => 14*24*3600,
        'thumbnails' => array(160, 120, 'c')
    );

    $config['general'] = array_merge($defaultconfig, $config['general']);

    // Make sure the cookie_domain for the sessions is set properly.
    if (empty($config['general']['cookies_domain'])) {

        // Don't set the domain for a cookie on a "TLD" - like 'localhost', or if the server_name is an IP-address
        if (isset($_SERVER["SERVER_NAME"]) && (strpos($_SERVER["SERVER_NAME"], ".") > 0) && preg_match("/[a-z]/i", $_SERVER["SERVER_NAME"]) ) {
            if (preg_match("/^www./",$_SERVER["SERVER_NAME"])) {
                $config['general']['cookies_domain'] = "." . preg_replace("/^www./", "", $_SERVER["SERVER_NAME"]);
            } else {
                $config['general']['cookies_domain'] = "." .$_SERVER["SERVER_NAME"];
            }
        } else {
            $config['general']['cookies_domain'] = "";
        }
    }

    // @todo Think about what to do with these..
    /*
    # Date and Time formats
    shortdate: j M ’ye
    longdate: l j F Y
    shorttime: H:i
    longtime: H:i:s
    fulldatetime: Y-m-d H:i:s
    */

    // Clean up taxonomies
    foreach ($config['taxonomy'] as $key => $value) {
        if (!isset($config['taxonomy'][$key]['name'])) {
            $config['taxonomy'][$key]['name'] = ucwords($config['taxonomy'][$key]['slug']);
        }
        if (!isset($config['taxonomy'][$key]['singular_name'])) {
            $config['taxonomy'][$key]['singular_name'] = ucwords($config['taxonomy'][$key]['singular_slug']);
        }
        if (!isset($config['taxonomy'][$key]['slug'])) {
            $config['taxonomy'][$key]['slug'] = strtolower(safeString($config['taxonomy'][$key]['name']));
        }
        if (!isset($config['taxonomy'][$key]['singular_slug'])) {
            $config['taxonomy'][$key]['singular_slug'] = strtolower(safeString($config['taxonomy'][$key]['singular_name']));
        }
        if (!isset($config['taxonomy'][$key]['has_sortorder'])) {
            $config['taxonomy'][$key]['has_sortorder'] = false;
        }
    }

    // Clean up contenttypes
    $config['contenttypes'] = array();
    foreach ($tempcontenttypes as $temp) {
        if (!isset($temp['slug'])) {
            $temp['slug'] = makeSlug($temp['name']);
        }
        if (!isset($temp['singular_slug'])) {
            $temp['singular_slug'] = makeSlug($temp['singular_name']);
        }
        if (!isset($temp['show_on_dashboard'])) {
            $temp['show_on_dashboard'] = true;
        }
        if (!isset($temp['sort'])) {
            $temp['sort'] = "";
        }
        // Make sure all fields are lowercase and 'safe'.
        $tempfields = $temp['fields'];
        $temp['fields'] = array();
        foreach($tempfields as $key => $value) {
			$key = str_replace("-", "_", strtolower(safeString($key, true)));
            $temp['fields'][ $key ] = $value;
        }

        // Make sure taxonomy is an array.
        if (isset($temp['taxonomy']) && !is_array($temp['taxonomy'])) {
            $temp['taxonomy'] = array($temp['taxonomy']);
        }

        $config['contenttypes'][ $temp['slug'] ] = $temp;

    }


    $end = getWhichEnd();

    // I don't think we can set Twig's path in runtime, so we have to resort to hackishness to set the path..
    $themepath = realpath(__DIR__.'/../../theme/'. basename($config['general']['theme']));
    if ( isset( $config['general']['theme_path'] ) )
    {
        $themepath = BOLT_PROJECT_ROOT_DIR . $config['general']['theme_path'];
    }
    $config['theme_path'] = $themepath;

    if ( $end == "frontend" && file_exists($themepath) ) {
        $config['twigpath'] = array($themepath);
    } else {
        $config['twigpath'] = array(realpath(__DIR__.'/../view'));
    }

    // If the template path doesn't exist, attempt to set a Flash error on the dashboard.
    if (!file_exists($themepath) && (gettype($app['session']) == "object") ) {
        $app['session']->setFlash('error', "Template folder 'theme/" . basename($config['general']['theme']) . "' does not exist, or is not writable.");
        $app['log']->add("Template folder 'theme/" . basename($config['general']['theme']) . "' does not exist, or is not writable.", 3);
    }

    // We add these later, because the order is important: By having theme/ourtheme first,
    // files in that folder will take precedence. For instance when overriding the menu template.
    $config['twigpath'][] = realpath(__DIR__.'/../theme_defaults');
    $config['twigpath'][] = realpath(__DIR__.'/../extensions');

    return $config;

}


function getWhichEnd() {

    if (!empty($_SERVER['REQUEST_URI'])) {
        // Get the script's filename, but _without_ REQUEST_URI.
        $scripturi = str_replace("#".dirname($_SERVER['SCRIPT_NAME']), '', "#".$_SERVER['REQUEST_URI']);
    } else {
        // We're probably in CLI mode.
        return "cli";
    }

    // If the request URI starts with '/bolt' or '/async' in the URL, we assume we're in the Backend..
    // Yeah.. Awesome.. Add the theme folder if it exists and is readable.
    if ( (substr($scripturi,0,5) == "bolt/") || (strpos($scripturi, "/bolt/") !== false) ) {
        $end = 'backend';
    } else if ( (substr($scripturi,0,6) == "async/") || (strpos($scripturi, "/async/") !== false) ) {
        $end = 'async';
    } else {
        $end = 'frontend';
    }

    return $end;

}


function getDBOptions($config)
{
    $configdb = $config['general']['database'];

    if (isset($configdb['driver']) && ( $configdb['driver'] == "pdo_sqlite" || $configdb['driver'] == "sqlite" ) ) {

        $basename = isset($configdb['databasename']) ? basename($configdb['databasename']) : "bolt";
        if (getExtension($basename)!="db") {
            $basename .= ".db";
        }

        $dboptions = array(
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . "/../database/" . $basename,
            'randomfunction' => "RANDOM()"
        );

    } else {
        // Assume we configured it correctly. Yeehaa!

        if (empty($configdb['password'])) {
            $configdb['password'] = "";
        }

        $driver = (isset($configdb['driver']) ? $configdb['driver'] : 'pdo_mysql');
        if ($driver == "mysql" || $driver == "mysqli") {
            $driver = 'pdo_mysql';
            $randomfunction = "RAND()";
        }
        if ($driver == "postgres" || $driver == "postgresql") {
            $driver = 'pdo_postgres';
            $randomfunction = "RANDOM()";
        }

        $dboptions = array(
            'driver'    => $driver,
            'host'      => (isset($configdb['host']) ? $configdb['host'] : 'localhost'),
            'dbname'    => $configdb['databasename'],
            'user'      => $configdb['username'],
            'password'  => $configdb['password'],
            'port'      => (isset($configdb['port']) ? $configdb['port'] : '3306'),
            'randomfunction' => $randomfunction
        );
        if (!isset($configdb['charset'])) {
            $dboptions['charset'] = 'utf8';
        } else {
            $dboptions['charset'] = $configdb['charset'];
        }

    }

    switch($dboptions['driver']) {
        case 'pdo_mysql':
            $dboptions['reservedwords'] = explode(',', "accessible,add,all,alter,analyze,and,as,asc,asensitive,before,between," .
                "bigint,binary,blob,both,by,call,cascade,case,change,char,character,check,collate,column,condition,constraint," .
                "continue,convert,create,cross,current_date,current_time,current_timestamp,current_user,cursor,database,databases," .
                "day_hour,day_microsecond,day_minute,day_second,dec,decimal,declare,default,delayed,delete,desc,describe," .
                "deterministic,distinct,distinctrow,div,double,drop,dual,each,else,elseif,enclosed,escaped,exists,exit,explain," .
                "false,fetch,float,float4,float8,for,force,foreign,from,fulltext,get,grant,group,having,high_priority,hour_microsecond," .
                "hour_minute,hour_second,if,ignore,in,index,infile,inner,inout,insensitive,insert,int,int1,int2,int3,int4,int8," .
                "integer,interval,into,io_after_gtids,io_before_gtids,is,iterate,join,key,keys,kill,leading,leave,left,like,limit," .
                "linear,lines,load,localtime,localtimestamp,lock,long,longblob,longtext,loop,low_priority,master_bind," .
                "master_ssl_verify_server_cert,match,maxvalue,mediumblob,mediumint,mediumtext,middleint,minute_microsecond," .
                "minute_second,mod,modifies,natural,nonblocking,not,no_write_to_binlog,null,numeric,on,optimize,option,optionally," .
                "or,order,out,outer,outfile,partition,precision,primary,procedure,purge,range,read,reads,read_write,real,references," .
                "regexp,release,rename,repeat,replace,require,resignal,restrict,return,revoke,right,rlike,schema,schemas," .
                "second_microsecond,select,sensitive,separator,set,show,signal,smallint,spatial,specific,sql,sqlexception,sqlstate," .
                "sqlwarning,sql_big_result,sql_calc_found_rows,sql_small_result,ssl,starting,straight_join,table,terminated,then," .
                "tinyblob,tinyint,tinytext,to,trailing,trigger,true,undo,union,unique,unlock,unsigned,update,usage,use,using,utc_date," .
                "utc_time,utc_timestamp,values,varbinary,varchar,varcharacter,varying,when,where,while,with,write,xor,year_month," .
                "zerofill,nonblocking");
            break;
        case 'pdo_sqlite':
            $dboptions['reservedwords'] = explode(',', "abort,action,add,after,all,alter,analyze,and,as,asc,attach,autoincrement," .
                "before,begin,between,by,cascade,case,cast,check,collate,column,commit,conflict,constraint,create,cross,current_date," .
                "current_time,current_timestamp,database,default,deferrable,deferred,delete,desc,detach,distinct,drop,each,else,end," .
                "escape,except,exclusive,exists,explain,fail,for,foreign,from,full,glob,group,having,if,ignore,immediate,in,index," .
                "indexed,initially,inner,insert,instead,intersect,into,is,isnull,join,key,left,like,limit,match,natural,no,not," .
                "notnull,null,of,offset,on,or,order,outer,plan,pragma,primary,query,raise,references,regexp,reindex,release,rename," .
                "replace,restrict,right,rollback");
            break;
        case 'pdo_postgres':
            $dboptions['reservedwords'] = explode(',', "all,analyse,analyze,and,any,as,asc,authorization,between,bigint,binary,bit," .
                "boolean,both,case,cast,char,character,check,coalesce,collate,column,constraint,convert,create,cross,current_date," .
                "current_time,current_timestamp,current_user,dec,decimal,default,deferrable,desc,distinct,do,else,end,except,exists," .
                "extract,float,for,foreign,freeze,from,full,grant,group,having,ilike,in,initially,inner,int,integer,intersect,interval," .
                "into,is,isnull,join,leading,left,like,limit,localtime,localtimestamp,natural,nchar,new,none,not,notnull,null,nullif," .
                "numeric,off,offset,old,on,only,or,order,outer,overlaps,overlay,placing,position,primary,real,references,right,row," .
                "select,session_user,setof,similar,smallint,some,substring,table,then,time,timestamp,to,trailing,treat,trim,union," .
                "unique,user,using,varchar,verbose,when,where,false,true");
    }

    return $dboptions;

}

function getPaths($config = array())
{
    // Make sure $config is not empty. This is for when this function is called
    // from lowlevelError().
    if (empty($config)) {
        $config['general']['theme'] = 'default';
        $config['general']['canonical'] = $_SERVER['HTTP_HOST'];
    }

    // Set the root
    $path_prefix = dirname($_SERVER['PHP_SELF'])."/";
    $path_prefix = str_replace("//", "/", str_replace("\\", "/", $path_prefix));
    if (empty($path_prefix)) {
        $path_prefix = "/";
    }

    if (!empty($_SERVER["SERVER_PROTOCOL"])) {
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? 'https' : 'http';
    } else {
        $protocol = "cli";
    }

    $currentpath = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "/";

    // Set the paths
    $paths = array(
        'hostname' => !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "localhost",
        'root' => $path_prefix,
        'rootpath' => realpath(__DIR__ . "/../../"),
        'theme' => $path_prefix . "theme/" . $config['general']['theme'] . "/",
        'themepath' => realpath(__DIR__ . "/../../theme/" . $config['general']['theme']),
        'app' => $path_prefix . "app/",
        'apppath' => realpath(__DIR__ . "/.."),
        'bolt' => $path_prefix . "bolt/",
        'async' => $path_prefix . "async/",
        'files' => $path_prefix . "files/",
        'filespath' => realpath(__DIR__ . "/../../files"),
        'canonical' => $config['general']['canonical'],
        'current' => $currentpath
    );

    $paths['hosturl'] = sprintf("%s://%s", $protocol, $paths['hostname']);
    $paths['rooturl'] = sprintf("%s://%s%s", $protocol, $paths['canonical'], $paths['root']);
    $paths['canonicalurl'] = sprintf("%s://%s%s", $protocol, $paths['canonical'], $currentpath);
    $paths['currenturl'] = sprintf("%s://%s%s", $protocol, $paths['hostname'], $currentpath);

    if ( isset( $config['general']['theme_path'] ) ) {
        $paths['themepath'] = BOLT_PROJECT_ROOT_DIR . $config['general']['theme_path'];
    }
    if ( BOLT_COMPOSER_INSTALLED ) {
        $paths['app'] = $path_prefix . "bolt-public/";
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
 * If debug is enabled this function handles the errors and warnings
 *
 * @param integer $errno
 * @param string $errmsg
 * @param string $filename
 * @param integer $linenum
 * @param array $vars
 */
function userErrorHandler ($errno, $errmsg, $filename, $linenum, $vars)
{
    global $app;

    $replevel = error_reporting();
    if ( ( $errno & $replevel ) != $errno ) {
        // we shall remain quiet.
        return;
    }

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are 2,8,256,512 and 1024
    $errortype = array (
        1    => "Error",
        2    => "Warning",
        4    => "Parsing Error",
        8    => "Notice",
        16   => "Core Error",
        32   => "Core Warning",
        64   => "Compile Error",
        128  => "Compile Warning",
        256  => "User Error",
        512  => "User Warning",
        1024 => "User Notice",
        2048 => "Strict",
        4096 => "Recoverable Error",
        8192 => "Deprecated",
        16384 => "User Deprecated"

    );

    $root = dirname($_SERVER['DOCUMENT_ROOT']);
    $filename = str_replace($root, "", $filename);

    $err = sprintf("<b>PHP-%s</b>: %s.", $errortype[$errno], $errmsg);

    if ($app['config']['general']['developer_notices']) {
        echo "<p><strong>$err</strong>, $filename, $linenum</p>";
    }

    $app['log']->errorhandler($err, $filename, $linenum);

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
