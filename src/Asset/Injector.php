<?php
namespace Bolt\Asset;

use Bolt\Helpers\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class for matching HTML elements and injecting text.
 *
 * @author Bob den Otter <bob@twokings.nl>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Injector
{
    /**
     * Get a map of function names to locations.
     *
     * @return array
     */
    public function getMap()
    {
        return [
            Target::END_OF_HEAD      => 'headTagEnd',
            Target::AFTER_HEAD_JS    => 'headTagEnd', // same as end of head because we cheat a little
            Target::AFTER_HEAD_CSS   => 'headTagEnd', // same as end of head because we cheat a little
            Target::AFTER_HEAD_META  => 'headTagEnd', // same as end of head because meta tags are unordered

            Target::BEFORE_CSS       => 'cssTagsBefore',
            Target::BEFORE_JS        => 'jsTagsBefore',
            Target::AFTER_META       => 'metaTagsAfter',
            Target::AFTER_CSS        => 'cssTagsAfter',
            Target::AFTER_JS         => 'jsTagsAfter',

            Target::START_OF_HEAD    => 'headTagStart',
            Target::BEFORE_HEAD_JS   => 'headTagStart', // same as start of head because we cheat a little
            Target::BEFORE_HEAD_CSS  => 'headTagStart', // same as start of head because we cheat a little
            Target::BEFORE_HEAD_META => 'headTagStart', // same as start of head because meta tags are unordered

            Target::START_OF_BODY    => 'bodyTagStart',
            Target::BEFORE_BODY_JS   => 'bodyTagStart', // same as start of body because we cheat a little
            Target::BEFORE_BODY_CSS  => 'bodyTagStart', // same as start of body because we cheat a little

            Target::END_OF_BODY      => 'bodyTagEnd',
            Target::AFTER_BODY_JS    => 'bodyTagEnd',   // same as end of body because we cheat a little
            Target::AFTER_BODY_CSS   => 'bodyTagEnd',   // same as end of body because we cheat a little

            Target::END_OF_HTML      => 'htmlTagEnd',
        ];
    }

    /**
     * @param AssetInterface $asset
     * @param string         $location
     * @param Response       $response
     */
    public function inject(AssetInterface $asset, $location, Response $response)
    {
        $html = $response->getContent();
        $functionMap = $this->getMap();
        if (isset($functionMap[$location])) {
            $html = $this->{$functionMap[$location]}($asset, $html);
        } else {
            $html .= "$asset\n";
        }

        $response->setContent($html);
    }

    /**
     * Helper function to insert some HTML into the start of the head section of
     * an HTML page, right after the <head> tag.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function headTagStart(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '<head', true, false)) {
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], (string) $asset);

            return Str::replaceFirst($matches[0], $replacement, $rawHtml);
        }

        return $this->tagSoup($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML into the head section of an HTML
     * page, right before the </head> tag.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function headTagEnd(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '</head', false, false)) {
            $replacement = sprintf("%s\t%s\n%s", $matches[1], (string) $asset, $matches[0]);

            return Str::replaceFirst($matches[0], $replacement, $rawHtml);
        }

        return $this->tagSoup($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML into the start of the head section of
     * an HTML page, right after the <body> tag.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function bodyTagStart(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '<body', true, false)) {
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], (string) $asset);

            return Str::replaceFirst($matches[0], $replacement, $rawHtml);
        }

        return $this->tagSoup($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML into the body section of an HTML
     * page, right before the </body> tag.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function bodyTagEnd(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '</body', false, false)) {
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $asset, $matches[0]);

            return Str::replaceFirst($matches[0], $replacement, $rawHtml);
        }

        return $this->tagSoup($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML into the html section of an HTML
     * page, right before the </html> tag.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function htmlTagEnd(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '</html', false, false)) {
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $asset, $matches[0]);

            return Str::replaceFirst($matches[0], $replacement, $rawHtml);
        }

        return $this->tagSoup($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function metaTagsAfter(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '<meta', true, true)) {
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], (string) $asset);

            return Str::replaceFirst($matches[0][$last], $replacement, $rawHtml);
        }

        return $this->headTagEnd($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function cssTagsAfter(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '<link', true, true)) {
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], (string) $asset);

            return Str::replaceFirst($matches[0][$last], $replacement, $rawHtml);
        }

        return $this->headTagEnd($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML before the first CSS include in the page.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function cssTagsBefore(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '<link', true, false)) {
            $replacement = sprintf("%s%s\n%s\t%s", $matches[1], $asset, $matches[0], $matches[1]);

            return Str::replaceFirst($matches[0], $replacement, $rawHtml);
        }

        return $this->tagSoup($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML before the first javascript include in the page.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    protected function jsTagsBefore(AssetInterface $asset, $rawHtml)
    {
        if ($matches = $this->getMatches($rawHtml, '<script', true, false)) {
            $replacement = sprintf("%s%s\n%s\t%s", $matches[1], $asset, $matches[0], $matches[1]);

            return Str::replaceFirst($matches[0], $replacement, $rawHtml);
        }

        return $this->tagSoup($asset, $rawHtml);
    }

    /**
     * Helper function to insert some HTML after the last javascript include.
     * First in the head section, but if there is no script in the head, place
     * it anywhere.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     * @param boolean        $insidehead
     *
     * @return string
     */
    protected function jsTagsAfter($asset, $rawHtml, $insidehead = false)
    {
        if ($insidehead) {
            $pos = strpos($rawHtml, '</head>');
            $context = substr($rawHtml, 0, $pos);
        } else {
            $context = $rawHtml;
        }

        // This match tag is a unique case
        if ($matches = $this->getMatches($context, '(.*)</script>', false, true)) {
            // Attempt to insert it after the last <script> tag within context, matching indentation.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], (string) $asset);

            return Str::replaceFirst($matches[0][$last], $replacement, $rawHtml);
        } elseif ($insidehead) {
            // Second attempt: entire document
            return $this->jsTagsAfter($asset, $rawHtml, false);
        }

        return $this->headTagEnd($asset, $rawHtml);
    }

    /**
     * Get a set of matches.
     *
     * @param string  $rawHtml        The original HTML
     * @param string  $htmlTag        HTML tag fragment we're matching, e.g. '<head' or '</head'
     * @param boolean $matchRemainder TRUE matches the remainder of the line, not just the tag - (.*)
     * @param boolean $matchAll       TRUE returns all matched instances - preg_match_all()
     *
     * @return string[]
     */
    private function getMatches($rawHtml, $htmlTag, $matchRemainder, $matchAll)
    {
        $matches = null;
        $matchRemainder = $matchRemainder ? '(.*)' : '';
        $regex = sprintf("~^([ \t]*)%s%s~mi", $htmlTag, $matchRemainder);

        if ($matchAll && preg_match_all($regex, $rawHtml, $matches)) {
            return $matches;
        } elseif (!$matchAll && preg_match($regex, $rawHtml, $matches)) {
            return $matches;
        }
    }

    /**
     * Since we're serving tag soup, just append the tag to the HTML we're given.
     *
     * @param AssetInterface $asset
     * @param string         $rawHtml
     *
     * @return string
     */
    private function tagSoup(AssetInterface $asset, $rawHtml)
    {
        return $rawHtml . (string) $asset . "\n";
    }
}
