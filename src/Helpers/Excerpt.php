<?php

namespace Bolt\Helpers;

class Excerpt
{

    protected $source = null;

    public function __construct($source)
    {
        $this->source = $source;
    }

    public function getExcerpt($length = 200, $includetitle = false, $focus = null)
    {
        dump($this->source);
        die();

        if (is_object($content)) {
            if (method_exists($content, 'excerpt')) {
                return $content->excerpt($length);
            } else {
                $output = $content;
            }
        } elseif (is_array($content)) {
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
                unset($content[$key]);
            }
            $output = implode(' ', $content);
        } elseif (is_string($content)) {
            // otherwise we just use the string.
            $output = $content;
        } else {
            // Nope, got nothing.
            $output = '';
        }

        $output = str_replace('>', '> ', $output);
//        dump($focus);
        $output = Html::trimText(strip_tags($output), $length);

        return $output;

    }



}
