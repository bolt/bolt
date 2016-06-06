<?php

namespace Bolt\Helpers;

class Html
{
    /**
     * Trim text to a given length.
     *
     * @param string $str           String to trim
     * @param int    $desiredLength Target string length
     * @param bool   $hellip        Add dots when the string is too long
     *
     * @return string Trimmed string
     */
    public static function trimText($str, $desiredLength, $hellip = true)
    {
        if ($hellip) {
            $ellipseStr = '…';
            $newLength = $desiredLength - 1;
        } else {
            $ellipseStr = '';
            $newLength = $desiredLength;
        }

        $str = trim(strip_tags($str));

        if (mb_strlen($str) > $desiredLength) {
            $str = mb_substr($str, 0, $newLength) . $ellipseStr;
        }

        return $str;
    }

    /**
     * Transforms plain text to HTML. Plot twist: text between backticks (`) is
     * wrapped in a <tt> element.
     *
     * @param string $str Input string. Treated as plain text.
     *
     * @return string The resulting HTML
     */
    public static function decorateTT($str)
    {
        $str = htmlspecialchars($str, ENT_QUOTES);
        $str = preg_replace('/`([^`]*)`/', '<tt>\\1</tt>', $str);

        return $str;
    }

    /**
     * Check if a given string looks like it could be a URL, with or without the protocol.
     *
     * @see https://mathiasbynens.be/demo/url-regex
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function isURL($str)
    {
        $pattern = '~^(?:\b[a-z\d.-]+://[^<>\s]+|\b(?:(?:(?:[^\s!@#$%^&*()_=+[\]{}\|;:\'",.<>/?]+)\.)+(?:ac|ad|aero|ae|af|ag|ai|al|am|an|ao|aq|arpa|ar|asia|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|biz|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|cat|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|coop|com|co|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|info|int|in|io|iq|ir|is|it|je|jm|jobs|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mobi|mo|mp|mq|mr|ms|mt|museum|mu|mv|mw|mx|my|mz|name|na|nc|net|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pro|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|travel|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|xn--0zwm56d|xn--11b5bs3a9aj6g|xn--80akhbyknj4f|xn--9t4b11yi5a|xn--deba0ad|xn--g6w251d|xn--hgbk6aj7f53bba|xn--hlcj6aya9esc7a|xn--jxalpdlp|xn--kgbechtv|xn--zckzah|ye|yt|yu|za|zm|zw)|(?:(?:[0-9]|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.){3}(?:[0-9]|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5]))(?:[;/][^#?<>\s]*)?(?:\?[^#<>\s]*)?(?:#[^<>\s]*)?(?!\w))$~iS';

        // Special case that isn't caught by this regex: 'http://' or 'https://' without a domain.
        if (preg_match('~^https?://$~i', $str)) {
            return false;
        }

        return (bool) preg_match($pattern, $str . '/');
    }

    /**
     * Add 'http://' to a link, if it has no protocol already.
     *
     * @param string $url
     * @param string $scheme
     *
     * @return string
     */
    public static function addScheme($url, $scheme = 'http://')
    {
        $url = parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;

        return $url;
    }

    /**
     * Create 'provider' link, as used in the footer, to link to either an
     * email address or website URL.
     *
     * @param array $providedby
     *
     * @return string
     */
    public static function providerLink($providedby)
    {
        // If nothing is provided, we don't make a link.
        if (empty($providedby) || !is_array($providedby)) {
            return '';
        }

        // If we forgot the second element in the array, substitute the first for it.
        if (empty(strip_tags($providedby[1]))) {
            $providedby[1] = $providedby[0];
        }

        $scheme = parse_url($providedby[0], PHP_URL_SCHEME);

        if ($scheme === 'http' || $scheme === 'https') {
            // Link is OK, just add a target
            $link = sprintf('<a href="%s" target="_blank">', $providedby[0]);
        } elseif ($scheme === 'mailto') {
            // Already a `mailto:` include.
            $link = sprintf('<a href="%s">', $providedby[0]);
        } elseif (self::isURL($providedby[0])) {
            // An URL, without a scheme
            $link = sprintf('<a href="http://%s" target="_blank">', $providedby[0]);
        } else {
            // Fall back to old behaviour, assume an e-mail address
            $link = sprintf('<a href="mailto:%s">', $providedby[0]);
        }

        // Add the label and closing tag.
        $link .= strip_tags($providedby[1]) . '</a>';

        return $link;
    }
}
