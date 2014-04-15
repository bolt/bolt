<?php

namespace Bolt;

use util;
use Silex;

/**
 * The class for Bolt' Twig tags, functions and filters.
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * @var \Silex\Application
     */
    private $app;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return 'Bolt';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('print', array($this, 'printDump'), array('is_safe' => array('html'))), // Deprecated..
            new \Twig_SimpleFunction('dump', array($this, 'printDump'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('backtrace', array($this, 'printBacktrace'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('excerpt', array($this, 'excerpt'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('trimtext', array($this, 'trim'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('markdown', array($this, 'markdown'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('current', array($this, 'current')),
            new \Twig_SimpleFunction('token', array($this, 'token')),
            new \Twig_SimpleFunction('listtemplates', array($this, 'listtemplates')),
            new \Twig_SimpleFunction('listcontent', array($this, 'listcontent')),
            new \Twig_SimpleFunction('htmllang', array($this, 'htmllang')),
            new \Twig_SimpleFunction('pager', array($this, 'pager'), array('needs_environment' => true)),
            new \Twig_SimpleFunction('max', array($this, 'max')),
            new \Twig_SimpleFunction('min', array($this, 'min')),
            new \Twig_SimpleFunction('request', array($this, 'request')),
            new \Twig_SimpleFunction('debugbar', array($this, 'debugbar')),
            new \Twig_SimpleFunction('ismobileclient', array($this, 'isMobileClient')),
            new \Twig_SimpleFunction('menu', array($this, 'menu'), array('needs_environment' => true, 'is_safe' => array('html'))),
            new \Twig_SimpleFunction('randomquote', array($this, 'randomquote'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('widget', array($this, 'widget')),
            new \Twig_SimpleFunction('isallowed', array($this, 'isAllowed')),
            new \Twig_SimpleFunction('thumbnail', array($this, 'thumbnail')),
            new \Twig_SimpleFunction('image', array($this, 'image')),
            new \Twig_SimpleFunction('showimage', array($this, 'showimage'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('fancybox', array($this, 'fancybox'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('first', array($this, 'first')),
            new \Twig_SimpleFunction('last', array($this, 'last')),
            new \Twig_SimpleFunction('__', array($this, 'trans'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('redirect', array($this, 'redirect'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('stackitems', array($this, 'stackitems')),
            new \Twig_SimpleFunction('stacked', array($this, 'stacked')),
            new \Twig_SimpleFunction('imageinfo', array($this, 'imageinfo'))
        );
    }


    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('localdate', array($this, 'localedatetime'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('localedatetime', array($this, 'localedatetime'), array('is_safe' => array('html'))), // Deprecated
            new \Twig_SimpleFilter('rot13', array($this, 'rot13Filter')),
            new \Twig_SimpleFilter('trimtext', array($this, 'trim'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('markdown', array($this, 'markdown'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('twig', array($this, 'twig'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('tt', array($this, 'decorateTT'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('ucfirst', array($this, 'ucfirst')),
            new \Twig_SimpleFilter('excerpt', array($this, 'excerpt'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('ymllink', array($this, 'ymllink'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('slug', array($this, 'slug')),
            new \Twig_SimpleFilter('current', array($this, 'current')),
            new \Twig_SimpleFilter('thumbnail', array($this, 'thumbnail')),
            new \Twig_SimpleFilter('image', array($this, 'image')),
            new \Twig_SimpleFilter('showimage', array($this, 'showimage'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('fancybox', array($this, 'fancybox'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('editable', array($this, 'editable'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('order', array($this, 'order')),
            new \Twig_SimpleFilter('first', array($this, 'first')),
            new \Twig_SimpleFilter('last', array($this, 'last')),
            new \Twig_SimpleFilter('__', array($this, 'trans'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('safestring', array($this, 'safestring'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('round', array($this, 'round')),
            new \Twig_SimpleFilter('floor', array($this, 'floor')),
            new \Twig_SimpleFilter('ceil', array($this, 'ceil')),
            new \Twig_SimpleFilter('imageinfo', array($this, 'imageinfo')),
            new \Twig_SimpleFilter('selectfield', array($this, 'selectfield'))
        );
    }


    /**
     * Output pretty-printed arrays / objects.
     *
     * @see \Dumper::dump
     *
     * @param  mixed $var
     * @return string
     */
    public function printDump($var)
    {

        return \Dumper::dump($var, DUMPER_CAPTURE);

    }

    /**
     * Output pretty-printed backtrace.
     *
     * @see \Dumper::backtrace
     *
     * @param  mixed $var
     * @return string
     */
    public function printBacktrace($depth = 15)
    {

        return \Dumper::backtrace($depth, true);

    }

    /**
     * Returns the language value for in tags where the language attribute is
     * required. The _ in the locale will be replaced for -
     *
     * @return string
     */
    public function htmllang()
    {
        $locale = $this->app['config']->get('general/locale');
        if ($locale == "") {
            // return fallback
            return 'en';
        }

        return str_replace("_", "-", $locale);
    }


    /**
     * Returns the date time in a particular format. Takes the locale into
     * account.
     * @param  string|\DateTime $dateTime
     * @param  string $format
     * @return string           Formatted date and time
     */
    public function localedatetime($dateTime, $format = "%B %e, %Y %H:%M")
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }


        // Check for Windows to find and replace the %e modifier correctly
        // @see: http://php.net/strftime
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
        }

        // According to http://php.net/manual/en/function.setlocale.php manual
        // if the second parameter is "0", the locale setting is not affected,
        // only the current setting is returned.
        $result = setlocale(LC_ALL, 0);
        if ($result === false) {
            // This shouldn't occur, but.. Dude!
            // You ain't even got locale or English on your platform??
            // Various things we could do. We could fail miserably, but a more
            // graceful approach is to use the datetime to display a default
            // format
            $this->app['log']->add(
                "No valid locale detected. Fallback on DateTime active.",
                2
            );

            return $dateTime->format('Y-m-d H:i:s');
        } else {
            $timestamp = $dateTime->getTimestamp();

            return strftime($format, $timestamp);
        }
    }


    /**
     * Create an excerpt for the given content
     *
     * @param  string $content
     * @param  int $length  Defaults to 200 characters
     * @return string Resulting excerpt
     */
    public function excerpt($content, $length = 200)
    {
        // If it's an content object, let the object handle it.
        if (is_object($content)) {
            if (method_exists($content, 'excerpt')) {
                return $content->excerpt($length);
            } else {
                $output = $content;
            }

        } elseif (is_array($content)) {
            // Assume it's an array, strip some common fields that we don't need, implode the rest..
            $stripKeys = array(
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
                    );
            foreach ($stripKeys as $key) {
                unset($content[$key]);
            }
            $output = implode(" ", $content);

        } elseif (is_string($content)) {
            // otherwise we just use the string..
            $output = $content;

        } else {
            // Nope, got nothing..
            $output = "";
        }

        $output = str_replace(">", "> ", $output);
        $output = trimText(strip_tags($output), $length);

        return $output;

    }


    /**
     * Create a link to edit a .yml file, if a filename is detected in the string. Mostly
     * for use in Flashbag messages, to allow easy editing.
     *
     * @param string $str
     * @return string Resulting string
     */
    public function ymllink($str)
    {

        if (preg_match("/ ([a-z0-9_-]+\.yml)/i", $str, $matches)) {
            $path = path('fileedit', array('file' => "app/config/" . $matches[1]));
            $link = sprintf(" <a href='%s'>%s</a>", $path, $matches[1]);
            $str = preg_replace("/ ([a-z0-9_-]+\.yml)/i", $link, $str);
        }

        return $str;

    }



    /**
     * Get an array with the dimensions of an image, together with its
     * aspectratio and some other info.
     *
     * @param string $filename
     * @return array Specifics
     */
    public function imageinfo($filename)
    {

        $fullpath = sprintf("%s/%s", $this->app['paths']['filespath'], $filename);

        if (!is_readable($fullpath) || !is_file($fullpath)) {
            return false;
        }

        $types = array(
            0=>'unknown',
            1=>'gif',
            2=>'jpeg',
            3=>'png',
            4=>'swf',
            5=>'psd',
            6=>'bmp'
        );

        // Get the dimensions of the image
        $imagesize = getimagesize($fullpath);

        // Get the aspectratio
        if ($imagesize[1] > 0) {
            $ar = $imagesize[0] / $imagesize[1];
        } else {
            $ar = 0;
        }

        $info = array(
            'width' => $imagesize[0],
            'height' => $imagesize[1],
            'type' => $types[ $imagesize[2] ],
            'mime' => $imagesize['mime'],
            'aspectratio' => $ar,
            'filename' => $filename,
            'fullpath' => realpath($fullpath),
            'url' => str_replace("//", "/", $this->app['paths']['files'] . $filename)
        );

        // Landscape if aspectratio > 5:4
        $info['landscape'] = ($ar >= 1.25) ? true : false;

        // Portrait if aspectratio < 4:5
        $info['portrait'] = ($ar <= 0.8) ? true : false;

        // Square-ish, if neither portrait or landscape
        $info['square'] = !$info['landscape'] && !$info['portrait'];

        return $info;

    }


    /**
     * Return the 'sluggified' version of a string.
     *
     * @param $str string input value
     * @return string slug
     */
    public function slug($str)
    {

        $slug = makeSlug($str);

        return $slug;

    }

    /**
     * Trims the given string to a particular length.
     *
     * @param  string $content
     * @param  int $length  Defaults to 200
     * @return string Trimmed output
     *
     */
    public function trim($content, $length = 200)
    {
        $output = trimText(strip_tags($content), $length);

        return $output;
    }

    /**
     * Formats the given string as Markdown in HTML
     *
     * @param  string $content
     * @return string Markdown output
     */
    public function markdown($content)
    {
        // Parse the field as Markdown, return HTML
        $output = \Parsedown::instance()->parse($content);

        return $output;
    }


    /**
     * Formats the given string as Twig in HTML
     *
     * Note: this is partially duplicating the template_from_string functionality:
     * http://twig.sensiolabs.org/doc/functions/template_from_string.html
     *
     * We can't use that functionality though, since it requires the Twig_Extension_StringLoader()
     * extension. If we would use that, when instantiating Twig, it screws up the rendering: Every
     * template that has a filename that doesn't exist will be rendered as literal string. This
     * _really_ messes up the 'cascading rendering' of our theme templates.
     *
     * @param  string $content
     * @return string Twig output
     */
    public function twig($snippet, $extravars = array())
    {

        // Remember the current Twig loaders.
        $oldloader = $this->app['twig']->getLoader();

        $this->app['twig']->setLoader(new \Twig_Loader_String());

        // Parse the snippet.
        $html = $this->app['render']->render($snippet, $extravars);

        // Re-set the loaders back to the old situation.
        $this->app['twig']->setLoader($oldloader);

        return $html;

    }




    public function decorateTT($str)
    {
        return decorateTT($str);
    }

    /**
     * UCfirsts the given string.
     * @param  string $str;
     * @return string Same string where first character is in upper case
     */
    public function ucfirst($str)
    {
        return ucfirst($str);

    }

    /**
     * Sorts / orders items of an array
     *
     * @param  array $array
     * @param  string $on
     * @return array
     */
    public function order($array, $on, $on_secondary = '')
    {

        // Set the 'order_on' and 'order_ascending', taking into account things like '-datepublish'.
        list($this->order_on, $this->order_ascending) = $this->app['storage']->getSortOrder($on);

        // Set the secondary order, if any..
        if (!empty($on_secondary)) {
            list($this->order_on_secondary, $this->order_ascending_secondary) = $this->app['storage']->getSortOrder($on_secondary);
        } else {
            $this->order_on_secondary = false;
            $this->order_ascending_secondary = false;
        }

        uasort($array, array($this, "orderHelper"));

        return $array;

    }

    /**
     * Helper function for sorting an array of \Bolt\Content
     *
     * @param  \Bolt\Content|array $a
     * @param  \Bolt\Content|array $b
     * @return bool
     */
    private function orderHelper($a, $b)
    {
        $a_val = $a[$this->order_on];
        $b_val = $b[$this->order_on];

        // Check the primary sorting criterium..
        if ($a_val < $b_val) {
            return !$this->order_ascending;
        } else if ($a_val > $b_val) {
            return $this->order_ascending;
        } else {
            // Primary criterium is the same. Use the secondary criterium, if it is set. Otherwise return 0.
            if (empty($this->order_on_secondary)) {
                return 0;
            }

            $a_val = $a[$this->order_on_secondary];
            $b_val = $b[$this->order_on_secondary];

            if ($a_val < $b_val) {
                return !$this->order_ascending_secondary;
            } else if ($a_val > $b_val) {
                return $this->order_ascending_secondary;
            } else {
                // both criteria are the same. Whatever!
                return 0;
            }

        }

    }

    /**
     * Returns the first item of an array
     *
     * @param  array $array
     * @return mixed
     */
    public function first($array)
    {
        if (!is_array($array)) {
            return false;
        } else {
            return reset($array);
        }
    }

    /**
     * Returns the last item of an array
     *
     * @param  array $array
     * @return mixed
     */
    public function last($array)
    {
        if (!is_array($array)) {
            return false;
        } else {
            return end($array);
        }
    }

    /**
     * Returns true, if the given content is the current content.
     *
     * If we're on page/foo, and content is that page, you can use
     * {% is page|current %}class='active'{% endif %}
     *
     * @param  \Bolt\Content|array $content
     * @return bool                True if the given content is on the curent page.
     */
    public function current($content)
    {

        $route_params = $this->app['request']->get('_route_params');

        // If passed a string, and it is in the route..
        if (is_string($content) && in_array($content, $route_params)) {
            return true;
        }
        // special case for "home"
        if (empty($content) && empty($route_params)) {
            return true;
        }

        $linkToCheck  = false;

        if (is_array($content) && isset($content['link'])) {

            $linkToCheck = $content['link'];
        } elseif ($content instanceof \Bolt\Content) {

            $linkToCheck = $content->link();
        }

        $requestedUri    = explode('?', $this->app['request']->getRequestUri());

        $entrancePageUrl = $this->app['config']->get('general/homepage');
        $entrancePageUrl = (substr($entrancePageUrl, 0, 1) !== '/')
                            ? '/' . $entrancePageUrl
                            : $entrancePageUrl;

        // check against Request Uri
        if ($requestedUri[0] == $linkToCheck) {
            return true;
        }

        // check against entrance page url from general configuration
        if ('/' == $requestedUri[0] && $linkToCheck == $entrancePageUrl) {
            return true;
        }

        // No contenttypeslug or slug -> not 'current'
        if (empty($route_params['contenttypeslug']) || empty($route_params['slug'])) {
            return false;
        }

        // check against simple content.link
        if ("/" . $route_params['contenttypeslug'] . "/" . $route_params['slug'] == $linkToCheck) {
            return true;
        }

        // if the current requested page is for the same slug or singularslug..
        if (isset($content['contenttype']) &&
            ($route_params['contenttypeslug'] == $content['contenttype']['slug'] ||
                $route_params['contenttypeslug'] == $content['contenttype']['singular_slug'])
        ) {

            // .. and the slugs should match..
            if ($route_params['slug'] == $content['slug']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a simple Anti-CSRF-like token.
     *
     * @see \Bolt\Users::getAntiCSRFToken()
     * @return string
     */
    public function token()
    {
        return $this->app['users']->getAntiCSRFToken();

    }


    /**
     * lists templates, optionally filtered by $filter.
     *
     * @param  string $filter
     * @return array  Sorted and possibly filtered templates
     */
    public function listtemplates($filter = "")
    {

        $files = array();

        $foldername = $this->app['paths']['themepath'];


        $d = dir($foldername);

        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

        while (false !== ($file = $d->read())) {

            if (in_array($file, $ignored) || substr($file, 0, 2) == "._") {
                continue;
            }

            if (is_file($foldername . "/" . $file) && is_readable($foldername . "/" . $file)) {

                if (!empty($filter) && !fnmatch($filter, $file)) {
                    continue;
                }

                // Skip filenames that start with _
                if ($file[0] == "_") {
                    continue;
                }

                $files[$file] = $file;
            }
        }

        $d->close();
        // Make sure the files are sorted properly.
        ksort($files);

        return $files;
    }


    /**
     * Lists content of a specific contenttype, specifically for editing
     * relations in the backend.
     *
     * @param  string $contenttype
     * @param  array $relationoptions
     * @param  \Bolt\Content $content
     * @return string
     */
    public function listcontent($contenttype, $relationoptions, $content)
    {
        // Just the relations for the current record, and just the current $contenttype.
        $current = isset($content->relation[$contenttype]) ? $content->relation[$contenttype] : null;

        // We actually only need the 'order' in options.
        $options = array();
        if (!empty($relationoptions['order'])) {
            $options['order'] = $relationoptions['order'];
            $options['limit'] = 10000;
            $options['hydrate'] = false;
        }

        // @todo Perhaps make something more lightweight for this?
        $results = $this->app['storage']->getContent($contenttype, $options);

        // Loop the array, set records in 'current' to have a 'selected' flag.
        if (!empty($current)) {
            foreach ($results as $key => $result) {
                if (in_array($result->id, $current)) {
                    $results[$key]['selected'] = true;
                } else {
                    $results[$key]['selected'] = false;
                }
            }
        }

        return $results;
    }

    /**
     * Output a simple pager, for paged pages.
     *
     * @param  \Twig_Environment $env
     * @param  string $pagerName
     * @param  int $surr
     * @param  string $template  The template to apply
     * @param  string $class
     * @return string            The rendered pager HTML
     */
    public function pager(\Twig_Environment $env, $pagerName = '', $surr = 4, $template = '_sub_pager.twig', $class = '')
    {
        // @todo Yuck, $GLOBALS.. figure out a better way to do this.
        if (!isset($GLOBALS['pager']) || !is_array($GLOBALS['pager'])) {
            // nothing to page..
            return "";
        }

        $pager = $GLOBALS['pager'];

        if (!empty($pagerName)) {
            $thisPager = $pager[$pagerName];
        } else {
            $thisPager = array_pop($pager);
        }

        $context = array(
            'pager' => $thisPager,
            'surr' => $surr,
            'class' => $class
        );

        if (isset($thisPager['link'])) {
            $context['link'] = $thisPager['link'];
        }

        return new \Twig_Markup($env->render($template, $context), 'utf-8');
    }


    /**
     * return the 'max' of two values..
     *
     * @param  mixed $a
     * @param  mixed $b
     * @return mixed
     */
    public function max($a, $b)
    {
        return max($a, $b);
    }


    /**
     * return the 'min' of two values..
     *
     * @param  mixed $a
     * @param  mixed $b
     * @return mixed
     */
    public function min($a, $b)
    {
        return min($a, $b);
    }


    /**
     * return the 'round' of a value..
     *
     * @param  float|int $a
     * @return int
     */
    public function round($a)
    {
        return round($a);
    }



    /**
     * return the 'floor' of a value..
     *
     * @param  float|int $a
     * @return int
     */
    public function floor($a)
    {
        return floor($a);
    }



    /**
     * return the 'ceil' of a value..
     *
     * @param  float|int $a
     * @return int
     */
    public function ceil($a)
    {
        return ceil($a);
    }



    /**
     * Return the requested parameter from $_REQUEST, $_GET or $_POST..
     *
     * @param  string $parameter    The parameter to get
     * @param  string $from         "GET", "POST", all the other falls back to REQUEST.
     * @param  bool $stripslashes Apply stripslashes. Defaults to false.
     * @return mixed
     */
    public function request($parameter, $from = "", $stripslashes = false)
    {
        $from = strtoupper($from);

        if ($from == "GET") {
            $res = $this->app['request']->query->get($parameter, false);
        } elseif ($from == "POST") {
            $res = $this->app['request']->request->get($parameter, false);
        } else {
            $res = $this->app['request']->get($parameter, false);
        }

        if ($stripslashes) {
            $res = stripslashes($res);
        }

        return $res;
    }


    /**
     *  Switch the debugbar 'on' or 'off'. Note: this has no influence on the
     * 'debug' setting itself. When 'debug' is off, setting this to 'on', will
     * _not_ show the debugbar.
     *
     * @param boolean $value
     */
    public function debugbar($value)
    {
        // @todo Should we enforce boolean values by using a === comparator?
        // Make sure it's actually true or false;
        $value = ($value) ? true : false;

        $this->app['debugbar'] = $value;
    }

    /**
     * Helper function to make a path to an image thumbnail.
     *
     * @param  string $filename Target filename
     * @param  string|int $width    Target width
     * @param  string|int $height   Target height
     * @param  string $crop     String identifier for cropped images. You can use next option fit, borders, resize or crop(dy default)
     * @return string     Thumbnail path
     */
    public function thumbnail($filename, $width = '', $height = '', $crop = "")
    {
        $thumbconf = $this->app['config']->get('general/thumbnails');

        if (empty($width)) {
            $width = !empty($thumbconf['default_thumbnail'][0]) ? $thumbconf['default_thumbnail'][0] : 100;
        } else {
            $width = (int) $width;
        }

        if (empty($height)) {
            $height = !empty($thumbconf['default_thumbnail'][1]) ? $thumbconf['default_thumbnail'][1] : 100;
        } else {
            $height = (int) $height;
        }

        if (empty($crop)) {
            $crop = !empty($thumbconf['cropping']) ? $thumbconf['cropping'] : 'c';
        } else {
            $crop = substr($crop, 0, 1);
        }

        // After v1.5.1 we store image data as an array
        if (is_array($filename)) {
            $filename = $filename['file'];
        }

        $thumbnail = sprintf(
            "%sthumbs/%sx%s%s/%s",
            $this->app['paths']['root'],
            $width,
            $height,
            $crop,
            safeFilename($filename)
        );

        return $thumbnail;
    }

    /**
     * Helper function to show an image on a rendered page.
     *
     * example: {{ content.image|showimage(320, 240) }}
     * example: {{ showimage(content.image, 320, 240) }}
     *
     * @param  string $filename Image filename
     * @param  int $width    Image width
     * @param  int $height   Image height
     * @param  string $crop     Crop image string identifier
     * @return string HTML output
     */
    public function showimage($filename = "", $width = 100, $height = 100, $crop = "")
    {

        if (!empty($filename)) {

            $image = $this->thumbnail($filename, $width, $height, $crop);

            $output = sprintf('<img src="%s" width="%s" height="%s">', $image, $width, $height);

        } else {
            $output = "&nbsp;";
        }

        return $output;

    }

    /**
     * Helper function to wrap an image in a fancybox HTML tag, with thumbnail
     *
     * example: {{ content.image|fancybox(320, 240) }}
     * example: {{ fancybox(content.image, 320, 240) }}
     * example: {{ content.image|fancybox(width=320, height=240, title="My Image") }}
     *
     * @param  string $filename Image filename
     * @param  int $width Image width
     * @param  int $height Image height
     * @param  string $crop Crop image string identifier
     * @param  string $title Display title for image
     * @return string HTML output
     */
    public function fancybox($filename = "", $width = 100, $height = 100, $crop = "", $title = "")
    {

        if (!empty($filename)) {

            $thumbconf = $this->app['config']->get('general/thumbnails');

            $fullwidth = !empty($thumbconf['default_image'][0]) ? $thumbconf['default_image'][0] : 1000;
            $fullheight = !empty($thumbconf['default_image'][1]) ? $thumbconf['default_image'][1] : 800;

            $thumbnail = $this->thumbnail($filename, $width, $height, $crop);
            $large = $this->thumbnail($filename, $fullwidth, $fullheight, 'r');

            if (empty($title)) {
                $title = sprintf('%s: %s', __("Image"), $filename);
            }

            $output = sprintf(
                '<a href="%s" class="fancybox" rel="fancybox" title="%s"><img src="%s" width="%s" height="%s"></a>',
                $large, $title, $thumbnail, $width, $height
            );

        } else {
            $output = "&nbsp;";
        }

        return $output;

    }

    /**
     * Helper function to make a path to an image.
     *
     * @param  string $filename Target filename
     * @param  string|int $width    Target width
     * @param  string|int $height   Target height
     * @param  string $crop     String identifier for cropped images
     * @return string     Image path
     */
    public function image($filename, $width = "", $height = "", $crop = "")
    {

        if ($width != "" || $height != "") {
            // You don't want the image, you just want a thumbnail.
            return $this->thumbnail($filename, $width, $height, $crop);
        }

        // After v1.5.1 we store image data as an array
        if (is_array($filename)) {
            $filename = $filename['file'];
        }

        $image = sprintf(
            "%sfiles/%s",
            $this->app['paths']['root'],
            safeFilename($filename)
        );

        return $image;

    }

    /**
     * Makes a piece of HTML editable
     *
     * @param string $html The HTML to be editable
     * @param \Bolt\Content The actual content
     * @param  string $field
     * @return string
     */
    public function editable($html, $content, $field)
    {
        $contenttype = $content->contenttype['slug'];

        $output = sprintf(
            "<div class='Bolt-editable' data-id='%s' data-contenttype='%s' data-field='%s'>%s</div>",
            $content->id,
            $contenttype,
            $field,
            $html
        );

        return $output;
    }

    /**
     * Check if the page is viewed on a mobile device.
     *
     * @return boolean
     */
    public function isMobileClient()
    {
        if (preg_match(
            '/(android|blackberry|htc|iemobile|iphone|ipad|ipaq|ipod|nokia|playbook|smartphone)/i',
            $_SERVER['HTTP_USER_AGENT']
        )) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Output a menu.
     *
     * @param  \Twig_Environment $env
     * @param  string $identifier Identifier for a particular menu
     * @param  string $template   The template to use.
     * @param  array $params     Extra parameters to pass on to the menu template.
     * @return null
     */
    public function menu(\Twig_Environment $env, $identifier = '', $template = '_sub_menu.twig', $params = array())
    {

        $menus = $this->app['config']->get('menu');

        if (!empty($identifier) && isset($menus[$identifier])) {
            $name = strtolower($identifier);
            $menu = $menus[$identifier];
        } else {
            $name = strtolower(util::array_first_key($menus));
            $menu = util::array_first($menus);
        }

        foreach ($menu as $key => $item) {
            $menu[$key] = $this->menuHelper($item);
            if (isset($item['submenu'])) {
                foreach ($item['submenu'] as $subkey => $subitem) {
                    $menu[$key]['submenu'][$subkey] = $this->menuHelper($subitem);
                }
            }

        }

        $twigvars = array(
            'name' => $name,
            'menu' => $menu
        );

        // If $params is not empty, merge it with twigvars.
        if (!empty($params) && is_array($params)) {
            $twigvars = $twigvars + $params;
        }

        return $env->render($template, $twigvars);

    }

    /**
     * Updates a menu item to have at least a 'link' key.
     *
     * @param  array $item
     * @return array Keys 'link' and possibly 'label', 'title' and 'path'
     */
    private function menuHelper($item)
    {
        if (isset($item['submenu']) && is_array($item['submenu'])) {
            $item['submenu'] = $this->menuHelper($item['submenu']);
        }

        if (isset($item['path']) && $item['path'] == "homepage") {
            $item['link'] = $this->app['paths']['root'];
        } elseif (isset($item['path'])) {
            // if the item is like 'content/1', get that content.
            if (preg_match('#^([a-z0-9_-]+)/([a-z0-9_-]+)$#i', $item['path'])) {
                $content = $this->app['storage']->getContent($item['path']);
            }

            if (!empty($content) && is_object($content) && get_class($content) == 'Bolt\Content') {
                // We have content.
                if (empty($item['label'])) {
                    $item['label'] = !empty($content->values['title']) ? $content->values['title'] : "";
                }
                if (empty($item['title'])) {
                    $item['title'] = !empty($content->values['subtitle']) ? $content->values['subtitle'] : "";
                }
                if (is_object($content)) {
                    $item['link'] = $content->link();
                }

                $item['record'] = $content;

            } else {
                // we assume the user links to this on purpose.
                $item['link'] = fixPath($this->app['paths']['root'] . $item['path']);
            }

        }

        return $item;
    }


    /**
     * Returns a random quote. Just for fun.
     *
     * @return string
     */
    public function randomquote()
    {
        $quotes = array(
            "Complexity is your enemy. Any fool can make something complicated. It is hard to make something simple.#Richard Branson",
            "It takes a lot of effort to make things look effortless.#Mark Pilgrim",
            "Perfection is achieved, not when there is nothing more to add, but when there is nothing left to take away.#Antoine de Saint-Exupéry",
            "Everything should be made as simple as possible, but not simpler.#Albert Einstein",
            "Three Rules of Work: Out of clutter find simplicity; From discord find harmony; In the middle of difficulty lies opportunity.#Albert Einstein",
            "There is no greatness where there is not simplicity, goodness, and truth.#Leo Tolstoy",
            "Think simple as my old master used to say - meaning reduce the whole of its parts into the simplest terms, getting back to first principles.#Frank Lloyd Wright",
            "Simplicity is indeed often the sign of truth and a criterion of beauty.#Mahlon Hoagland",
            "Simplicity and repose are the qualities that measure the true value of any work of art.#Frank Lloyd Wright",
            "Nothing is true, but that which is simple.#Johann Wolfgang von Goethe",
            "There is a simplicity that exists on the far side of complexity, and there is a communication of sentiment and attitude not to be discovered by careful exegesis of a text.#Patrick Buchanan",
            "The simplest things are often the truest.#Richard Bach",
            "If you can't explain it to a six year old, you don't understand it yourself.#Albert Einstein",
            "One day I will find the right words, and they will be simple.#Jack Kerouac",
            "Simplicity is the ultimate sophistication.#Leonardo da Vinci",
            "Our life is frittered away by detail. Simplify, simplify.#Henry David Thoreau",
            "The simplest explanation is always the most likely.#Agatha Christie",
            "Truth is ever to be found in the simplicity, and not in the multiplicity and confusion of things.#Isaac Newton",
            "Simplicity is a great virtue but it requires hard work to achieve it and education to appreciate it. And to make matters worse: complexity sells better.#Edsger Wybe Dijkstra",
            "Focus and simplicity. Simple can be harder than complex: You have to work hard to get your thinking clean to make it simple. But it's worth it in the end because once you get there, you can move mountains.#Steve Jobs",
            "The ability to simplify means to eliminate the unnecessary so that the necessary may speak.#Hans Hofmann",
            "I've learned to keep things simple. Look at your choices, pick the best one, then go to work with all your heart.#Pat Riley",
            "A little simplification would be the first step toward rational living, I think.#Eleanor Roosevelt",
            "Making the simple complicated is commonplace; making the complicated simple, awesomely simple, that's creativity.#Charles Mingus",
            "Keep it simple, stupid.#Kelly Johnson"
        );

        $randomquote = explode("#", $quotes[array_rand($quotes, 1)]);

        $quote = sprintf("“%s”\n<cite>— %s</cite>", $randomquote[0], $randomquote[1]);

        return $quote;
    }

    /**
     * Renders a particular widget type on the given location.
     *
     *
     * @param  string $type     Widget type (e.g. 'dashboard')
     * @param  string $location CSS location (e.g. 'right_first')
     * @return null
     */
    public function widget($type = '', $location = '')
    {
        $this->app['extensions']->renderWidgetHolder($type, $location);

        return null;
    }

    /**
     * Check if a certain action is allowed for the current user (and possibly
     * content item).
     *
     * @param string $what Operation
     * @param mixed $content If specified, a Content item.
     * @return bool   True if allowed
     */
    public function isAllowed($what, $content = null)
    {
        if ($content) {
            $what = "contenttype:{$content->contenttype['slug']}:$what:{$content['id']}";
        }
        return $this->app['users']->isAllowed($what);
    }

    /**
     * Translate using our __()
     *
     * @internal param string $content
     *
     * @return string translated content
     */
    public function trans()
    {
        $args = func_get_args();
        $num_args = func_num_args();
        switch ($num_args) {
            case 5:
                return __($args[0], $args[1], $args[2], $args[3], $args[4]);
            case 4:
                return __($args[0], $args[1], $args[2], $args[3]);
            case 3:
                return __($args[0], $args[1], $args[2]);
            case 2:
                return __($args[0], $args[1]);
            case 1:
                return __($args[0]);
        }

        return null;
    }

    /**
     * Return a 'safe string' version of a given string.
     *
     * @see function safeString() in app/classes/lib.php.
     *
     * @param $str
     * @param bool $strict
     * @param string $extrachars
     * @return string
     */
    public function safestring($str, $strict = false, $extrachars = "")
    {

        return safeString($str, $strict, $extrachars);

    }


    /**
     * Redirect the browser to another page.
     */
    public function redirect($path)
    {

        simpleredirect($path);

        $result = $this->app->redirect($path);

        return $result;

    }


    /**
     * Return an array with the items on the stack
     *
     * @param int $amount
     * @param string $type type
     * @return
     */
    public function stackitems($amount = 20, $type = "")
    {

        $items = $this->app['stack']->listitems($amount, $type);

        return $items;

    }


    /**
     * Return whether or not an item is on the stack, and is stackable in the first place.
     *
     * @param $filename string filename
     */
    public function stacked($filename)
    {

        $stacked = ( $this->app['stack']->isOnStack($filename) || !$this->app['stack']->isStackable($filename) );

        return $stacked;

    }


    /**
     * Return a selected field from a contentset
     *
     * @param array $content A Bolt record array
     * @param mixed fieldname Name of field (string), or array of names of
     *                        fields, to return from each record
     */
    public function selectfield($content, $fieldname)
    {
        $retval = array('');
        foreach($content as $c) {
            if (is_array($fieldname)) {
                $row = array();
                foreach ($fieldname as $fn) {
                    if(isset($c->values[$fn])) {
                        $row[] = $c->values[$fn];
                    }
                    else {
                        $row[] = null;
                    }
                }
                $retval[] = $row;
            }
            else {
                if(isset($c->values[$fieldname])) {
                    $retval[] = $c->values[$fieldname];
                }
            }
        }

        return $retval;

    }
}
