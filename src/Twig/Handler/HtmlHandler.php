<?php

namespace Bolt\Twig\Handler;

use Bolt\Helpers\Html;
use Bolt\Helpers\Str;
use Bolt\Legacy\Content;
use Maid\Maid;
use Silex;
use Symfony\Component\HttpFoundation\Request;

/**
 * Bolt specific Twig functions and filters for HTML
 *
 * @internal
 */
class HtmlHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Transforms plain text to HTML
     *
     * @see \Bolt\Helpers\Html::decorateTT()
     *
     * @param string $str
     *
     * @return string
     */
    public function decorateTT($str)
    {
        return Html::decorateTT($str);
    }

    /**
     * Makes a piece of HTML editable.
     *
     * @param string               $html    The HTML to be editable
     * @param \Bolt\Legacy\Content $content The actual content
     * @param string               $field
     *
     * @return string
     */
    public function editable($html, Content $content, $field)
    {
        $contentTypeName = $content->contenttype['slug'];

        $output = sprintf(
            '<div class="Bolt-editable" data-id="%s" data-contenttype="%s" data-field="%s">%s</div>',
            $content->id,
            $contentTypeName,
            $field,
            $html
        );

        return $output;
    }

    /**
     * Returns the language value for in tags where the language attribute is
     * required. The underscore '_' in the locale will be replaced with a
     * hyphen '-'.
     *
     * @return string
     */
    public function htmlLang()
    {
        return str_replace('_', '-', $this->app['locale']);
    }

    /**
     * Check if the page is viewed on a mobile device.
     *
     * @return boolean
     */
    public function isMobileClient()
    {
        if (($request = $this->app['request_stack']->getCurrentRequest()) === null) {
            return false;
        }

        return preg_match(
            '/(android|blackberry|htc|iemobile|iphone|ipad|ipaq|ipod|nokia|playbook|smartphone)/i',
            $request->headers->get('User-Agent')
        );
    }

    /**
     * Formats the given string as Markdown in HTML.
     *
     * @param string $content
     *
     * @return string Markdown output
     */
    public function markdown($content)
    {
        // Parse the field as Markdown, return HTML
        $output = $this->app['markdown']->text($content);

        $config = $this->app['config']->get('general/htmlcleaner');
        $allowed_tags = !empty($config['allowed_tags']) ? $config['allowed_tags'] :
            ['div', 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'blockquote', 'pre', 'code', 'tt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dh', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img'];
        $allowed_attributes = !empty($config['allowed_attributes']) ? $config['allowed_attributes'] :
            ['id', 'class', 'name', 'value', 'href', 'src'];

        // Sanitize/clean the HTML.
        $maid = new Maid(
            [
                'output-format'   => 'html',
                'allowed-tags'    => $allowed_tags,
                'allowed-attribs' => $allowed_attributes,
            ]
        );
        $output = $maid->clean($output);

        return $output;
    }

    /**
     * Create an HTML link to a given URL or contenttype/slug pair.
     *
     * @param string $location
     * @param string $label
     *
     * @return string
     */
    public function link($location, $label = '[link]')
    {
        if ((string) $location === '') {
            return '';
        }

        if (Html::isURL($location)) {
            $location = Html::addScheme($location);
        } elseif ($record = $this->app['storage']->getContent($location)) {
            $location = $record->link();
        }

        return sprintf('<a href="%s">%s</a>', $location, $label);
    }

    /**
     * Output a menu.
     *
     * @param \Twig_Environment $env
     * @param string            $identifier Identifier for a particular menu
     * @param string            $template   The template to use.
     * @param array             $params     Extra parameters to pass on to the menu template.
     *
     * @return string|null
     */
    public function menu(\Twig_Environment $env, $identifier = '', $template = '_sub_menu.twig', $params = [])
    {
        /** @var \Bolt\Menu\Menu $menu */
        $menu = $this->app['menu']->menu($identifier);

        $twigvars = [
            'name' => $menu->getName(),
            'menu' => $menu->getItems(),
        ];
        $twigvars += (array) $params;

        return $env->render($template, $twigvars);
    }

    /**
     * Add 'soft hyphens' &shy; to a string, so that it won't break layout in HTML when
     * using strings without spaces or dashes.
     *
     * @param string $str
     *
     * @return string
     */
    public function shy($str)
    {
        if (is_string($str)) {
            $str = Str::shyphenate($str);
        }

        return $str;
    }

    /**
     * @deprecated since 3.3. To be removed in 4.0.
     *
     * Formats the given string as Twig in HTML.
     *
     * Use template_from_string instead:
     * http://twig.sensiolabs.org/doc/functions/template_from_string.html
     *
     * @param string $snippet
     * @param array  $context
     *
     * @return string Twig output
     */
    public function twig($snippet, $context = [])
    {
        $template = $this->app['twig']->createTemplate((string) $snippet);

        return twig_include($this->app['twig'], $context, $template, [], true, false, true);
    }
}
