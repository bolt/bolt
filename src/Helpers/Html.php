<?php

namespace Bolt\Helpers;

use Maid\Maid;

class Html
{
    /**
     * Wrapper around trimToHTML for backwards-compatibility
     *
     * @param  string $str           String to trim
     * @param  int    $desiredLength Target string length
     * @param  bool   $nbsp          Transform spaces to their html entity
     * @param  bool   $hellip        Add dots when the string is too long
     * @param  bool   $striptags     Strip html tags
     * @return string Trimmed string
     */
    public static function trimText($str, $desiredLength, $nbsp = false, $hellip = true, $striptags = true)
    {
        if ($hellip) {
            $ellipseStr = '…';
        } else {
            $ellipseStr = '';
        }

        return self::trimToHTML($str, $desiredLength, $ellipseStr, $striptags, $nbsp);
    }

    /**
     * Recursively collect nodes from a DOM tree until the tree is exhausted or the
     * desired text length is fulfilled.
     *
     * @param \DOMNode $node            The current node
     * @param \DOMNode $parentNode      A target node that will receive copies of all
     *                                 collected nodes as child nodes.
     * @param int      $remainingLength The remaining number of characters to collect.
     *                                 When this value reaches zero, the traversal is
     *                                 stopped.
     * @param string   $ellipseStr      If non-empty, this string will be appended to the
     *                                 last collected node when the document gets
     *                                 truncated.
     */
    private static function collectNodesUpToLength(\DOMNode $node, \DOMNode $parentNode, &$remainingLength, $ellipseStr = '…')
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
     * @param \DOMNode $node The node to process. Note that processing is in-place.
     */
    private static function domSpacesToNBSP(\DOMNode $node)
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
     * @param string $html          The HTML fragment to clean up
     * @param int    $desiredLength The desired number of characters, or NULL to do
     *                              just the cleanup (but no truncating).
     * @param string $ellipseStr    If non-empty, this string will be appended to the
     *                              last collected node when the document gets
     *                              truncated.
     * @param bool   $stripTags     If TRUE, remove *all* HTML tags. Otherwise, keep a
     *                              whitelisted 'safe' set.
     * @param bool   $nbsp          If TRUE, convert all whitespace runs to non-breaking
     *                              spaces ('&nbsp;' entities).
     * @return string
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
     * @param  string $str Input string. Treated as plain text.
     * @return string The resulting HTML
     */
    public static function decorateTT($str)
    {
        $str = htmlspecialchars($str, ENT_QUOTES);
        $str = preg_replace('/`([^`]*)`/', '<tt>\\1</tt>', $str);

        return $str;
    }
}
