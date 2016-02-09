<?php

namespace Bolt\Helpers;

class Excerpt
{

    protected $body = null;
    protected $title = null;

    public function __construct($body, $title = null)
    {
        $this->body = $body;
        $this->title = $title;
    }

    /**
     * Get the excerpt of a given piece of text.
     *
     * @param int  $length
     * @param bool $includetitle
     * @param null $focus
     *
     * @return mixed|null|string
     */
    public function getExcerpt($length = 200, $includetitle = false, $focus = null)
    {
        if ($includetitle && !empty($this->title)) {
            $title = Html::trimText(strip_tags($this->title), $length);
            $length = $length - strlen($title);
        } else {
            $title = '';
        }

        if (is_array($this->body)) {
            // Assume it's an array, strip some common fields that we don't need, implode the rest.
            $stripKeys = [
                'id',
                'slug',
                'datecreated',
                'datechanged',
                'username',
                'ownerid',
                'title',
                'contenttype',
                'status',
                'taxonomy',
            ];

            foreach ($stripKeys as $key) {
                unset($this->body[$key]);
            }
            $excerpt = implode(' ', $this->body);
        } elseif (is_string($this->body)) {
            // otherwise we just use the string.
            $excerpt = $this->body;
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

        return $excerpt;
    }


    /**
     * Find the locations of each of the words.
     * Nothing exciting here. The array_unique is required, unless you decide
     * to make the words unique before passing in.
     *
     * @param $words
     * @param $fulltext
     * @return array
     */
    function _extractLocations($words, $fulltext)
    {
        $locations = array();
        foreach($words as $word) {
            $wordlen = strlen($word);
            $loc = stripos($fulltext, $word);
            while($loc !== FALSE) {
                $locations[] = $loc;
                $loc = stripos($fulltext, $word, $loc + $wordlen);
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
     * @param $locations
     * @param $prevcount
     * @return int
     */
    function _determineSnipLocation($locations, $prevcount)
    {
        // If we only have 1 match we don't actually do the for loop so set to the first
        $startpos = $locations[0];
        $loccount = count($locations);
        $smallestdiff = PHP_INT_MAX;

        // If we only have 2, skip as it's probably equally relevant
        if(count($locations) > 2) {
            // skip the first as we check 1 behind
            for($i=1; $i < $loccount; $i++) {
                if($i == $loccount-1) { // at the end
                    $diff = $locations[$i] - $locations[$i-1];
                }
                else {
                    $diff = $locations[$i+1] - $locations[$i];
                }

                if($smallestdiff > $diff) {
                    $smallestdiff = $diff;
                    $startpos = $locations[$i];
                }
            }
        }

        $startpos = $startpos > $prevcount ? $startpos - $prevcount : 0;

        return $startpos;
    }

    /**
     * Center on, and highlight search terms in excerpts.
     *
     * @see: http://www.boyter.org/2013/04/building-a-search-result-extract-generator-in-php/
     *
     * @param string|array $words
     * @param string       $fulltext
     * @param int          $rellength
     * @return mixed|string
     */
    function extractRelevant($words, $fulltext, $rellength=300)
    {
        if (!is_array($words)) {
            $words = explode(' ', $words);
        }

        // 1/6 ratio on prevcount tends to work pretty well and puts the terms
        // in the middle of the extract
        $prevcount = floor($rellength / 6);

        $indicator = '…';

        $textlength = strlen($fulltext);
        if($textlength <= $rellength) {
            return $fulltext;
        }

        $locations = $this->_extractLocations($words, $fulltext);
        $startpos  = $this->_determineSnipLocation($locations,$prevcount);

        // if we are going to snip too much...
        if($textlength - $startpos < $rellength) {
            $startpos = $startpos - ($textlength-$startpos)/2;
        }

        $reltext = substr($fulltext, $startpos, $rellength);

        // check to ensure we dont snip the last word if thats the match
        if( $startpos + $rellength < $textlength) {
            $reltext = substr($reltext, 0, strrpos($reltext, " ")).$indicator; // remove last word
        }

        // If we trimmed from the front add …
        if($startpos != 0) {
            $reltext = $indicator.substr($reltext, strpos($reltext, " ") + 1); // remove first word
        }

        // Highlight the words, using the `<mark>` tag.
        foreach($words as $word) {
            $reltext = preg_replace('/\b(' . $word . ')\b/i', '<mark>$1</mark>', $reltext);
        }

        return $reltext;
    }

}
