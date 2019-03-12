<?php

namespace Bolt\Helpers;

use Bolt\Collection\Bag;
use Bolt\Collection\MutableBag;
use Bolt\Legacy\Content as LegacyContent;
use Bolt\Storage\Entity\Content;
use Parsedown;
use Twig\Markup;

class Excerpt
{
    /** @var Content|LegacyContent|array|string */
    protected $body;
    /** @var string */
    protected $title;

    /**
     * Constructor.
     *
     * @param Content|LegacyContent|array|string $body
     * @param string|null                        $title
     */
    public function __construct($body, $title = null)
    {
        $this->body = $body;
        $this->title = trim($title);
    }

    /**
     * Get the excerpt of a given piece of text.
     *
     * @param int               $length
     * @param bool              $includeTitle
     * @param array|string|null $focus
     * @param array             $stripFields
     *
     * @return string|null
     */
    public function getExcerpt($length = 200, $includeTitle = false, $focus = null, $stripFields = [])
    {
        $title = null;
        if ($includeTitle && $this->title !== null) {
            $title = Html::trimText(strip_tags($this->title), $length);
            $length -= strlen($title);
        }

        if ($this->body instanceof Content) {
            $this->body = $this->body->toArray();
        }

        if ($this->body instanceof LegacyContent) {
            $this->body = $this->body->getValues();
        }

        if (!is_array($stripFields)) {
            trigger_error(sprintf('Wrong type for "stripField" parameter. Expected array, got %s. Ignoring "stripField".', gettype($stripFields)), E_USER_DEPRECATED);
            $stripFields = [];
        }

        if (is_array($this->body)) {
            // Assume it's an array, strip some common
            // fields that we don't need, merge with
            // the unwanted fields , implode the rest.
            $stripKeys = array_merge([
                'id',
                'slug',
                'datepublish',
                'datedepublish',
                'datecreated',
                'datechanged',
                'username',
                'ownerid',
                'title',
                'contenttype',
                'status',
                'taxonomy',
                'templatefields',
                'sortorder',
            ], $stripFields);

            $excerpt = '';
            array_walk($this->body, function ($value, $key) use (&$excerpt, $stripKeys) {
                if (in_array($key, $stripKeys)) {
                    return;
                }
                // We need non-empty strings that don't look like serialized JSON.
                // Otherwise, Twig Markup is also OK.
                if (is_string($value) && !empty($value) && !in_array($value[0], ['{', '[']) ||
                    $value instanceof Markup) {
                    $excerpt .= (string) $value . ' ';
                }
            });
        } elseif (is_string($this->body) || (is_object($this->body) && method_exists($this->body, '__toString'))) {
            // otherwise we just use the string.
            $excerpt = (string) $this->body;
        } else {
            // Nope, got nothing.
            $excerpt = '';
        }

        $excerpt = str_replace('>', '> ', $excerpt);

        if (empty($focus)) {
            $excerpt = Html::trimText(strip_tags($excerpt), $length);
        } else {
            $excerpt = $this->extractRelevant($focus, strip_tags($excerpt), $length);
        }

        if (!empty($title)) {
            $excerpt = '<b>' . $title . '</b> ' . $excerpt;
        }

        return trim($excerpt);
    }

    /**
     * @internal
     *
     * @param Content   $entity
     * @param Bag       $contentType
     * @param int       $length
     * @param Parsedown $markdown
     *
     * @return string
     */
    public static function createFromEntity(Content $entity, Bag $contentType, $length, Parsedown $markdown)
    {
        $parts = new MutableBag();

        foreach ($contentType->get('fields', []) as $key => $field) {
            // Skip empty fields, and fields used as 'title'.
            $fieldValue = $entity->get($key);
            if (!$fieldValue || $fieldValue === $entity->getTitle()) {
                continue;
            }
            // Add 'text', 'html' and 'textarea' fields.
            if (in_array($field['type'], ['text', 'html', 'textarea'])) {
                $parts[] = $fieldValue;
            }
            // Add 'markdown' field
            if ($field['type'] === 'markdown') {
                $parts[] = $markdown->text($fieldValue);
            }
        }
        $excerpt = new static($parts->join(' '), $entity->getTitle());

        return $excerpt->getExcerpt($length, true, false);
    }

    /**
     * Find the locations of each of the words.
     * Nothing exciting here. The array_unique is required, unless you decide
     * to make the words unique before passing in.
     *
     * @param array  $words
     * @param string $fulltext
     *
     * @return array
     */
    private function extractLocations(array $words, $fulltext)
    {
        $locations = [];
        foreach ($words as $word) {
            $wordLen = strlen($word);
            $loc = stripos($fulltext, $word);
            while ($loc !== false) {
                $locations[] = $loc;
                $loc = stripos($fulltext, $word, $loc + $wordLen);
            }
        }
        $locations = array_unique($locations);
        sort($locations);

        return $locations;
    }

    /**
     * Work out which is the most relevant portion to display
     * This is done by looping over each match and finding the smallest distance between two found
     * strings. The idea being that the closer the terms are the better match the snippet would be.
     * When checking for matches we only change the location if there is a better match.
     * The only exception is where we have only two matches in which case we just take the
     * first as will be equally distant.
     *
     * @param array $locations
     * @param int   $prevCount
     *
     * @return int
     */
    private function determineSnipLocation(array $locations, $prevCount)
    {
        // If we only have 1 match we don't actually do the for loop so set to the first
        $startPos = (int) reset($locations);
        $locCount = count($locations);
        $smallestDiff = PHP_INT_MAX;

        // If we only have 2, skip as it's probably equally relevant
        if ($locCount > 2) {
            // skip the first as we check 1 behind
            for ($i = 1; $i < $locCount; ++$i) {
                if ($i === $locCount - 1) { // at the end
                    $diff = $locations[$i] - $locations[$i - 1];
                } else {
                    $diff = $locations[$i + 1] - $locations[$i];
                }

                if ($smallestDiff > $diff) {
                    $smallestDiff = $diff;
                    $startPos = $locations[$i];
                }
            }
        }

        $startPos = $startPos > $prevCount ? $startPos - $prevCount : 0;

        return $startPos;
    }

    /**
     * Center on, and highlight search terms in excerpts.
     *
     * @see: http://www.boyter.org/2013/04/building-a-search-result-extract-generator-in-php/
     *
     * @param string|array $words
     * @param string       $fulltext
     * @param int          $relLength
     *
     * @return string
     */
    private function extractRelevant($words, $fulltext, $relLength = 300)
    {
        if (!is_array($words)) {
            $words = explode(' ', $words);
        }

        // 1/6 ratio on prevcount tends to work pretty well and puts the terms
        // in the middle of the extract
        $prevCount = floor($relLength / 6);

        $indicator = '…';

        $textlength = strlen($fulltext);
        if ($textlength <= $relLength) {
            return $fulltext;
        }

        $locations = $this->extractLocations($words, $fulltext);
        $startPos = $this->determineSnipLocation($locations, $prevCount);

        // if we are going to snip too much...
        if ($textlength - $startPos < $relLength) {
            $startPos -= ($textlength - $startPos) / 2;
        }

        $relText = substr($fulltext, $startPos, $relLength);

        // check to ensure we don't snip the last word if that's the match
        if ($startPos + $relLength < $textlength) {
            $relText = substr($relText, 0, strrpos($relText, ' ')) . $indicator; // remove last word
        }

        // If we trimmed from the front add '…'
        if ($startPos != 0) {
            $relText = $indicator . substr($relText, strpos($relText, ' ') + 1); // remove first word
        }

        // Highlight the words, using the `<mark>` tag.
        foreach ($words as $word) {
            if ($word) {
                $relText = preg_replace('/\b(' . preg_quote($word, '/') . ')\b/i', '<mark>$1</mark>', $relText);
            }
        }

        return $relText;
    }
}
