<?php

namespace Bolt\Twig\Runtime;

use Bolt\Common\Deprecated;
use Bolt\Config;
use Bolt\Helpers\Html;
use Bolt\Helpers\Str;
use Bolt\Legacy\Content;
use Bolt\Menu\MenuBuilder;
use Bolt\Storage\EntityManager;
use Maid\Maid;
use Twig\Environment;

/**
 * Bolt specific Twig functions and filters for HTML.
 *
 * @internal
 */
class HtmlRuntime
{
    /** @var Config */
    private $config;
    /** @var \Parsedown */
    private $markdown;
    /** @var MenuBuilder */
    private $menu;
    /** @var EntityManager */
    private $em;

    /**
     * Constructor.
     *
     * @param Config        $config
     * @param \Parsedown    $markdown
     * @param MenuBuilder   $menu
     * @param EntityManager $em
     */
    public function __construct(
        Config $config,
        \Parsedown $markdown,
        MenuBuilder $menu,
        EntityManager $em
    ) {
        $this->config = $config;
        $this->markdown = $markdown;
        $this->menu = $menu;
        $this->em = $em;
    }

    /**
     * Transforms plain text to HTML.
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
     * Formats the given string as Markdown in HTML.
     *
     * @param string $content
     *
     * @return string Markdown output
     */
    public function markdown($content)
    {
        // Parse the field as Markdown, return HTML
        $output = $this->markdown->text($content);

        $config = $this->config->get('general/htmlcleaner');
        $allowedTags = !empty($config['allowed_tags']) ? $config['allowed_tags'] :
            ['div', 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'blockquote', 'pre', 'code', 'tt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dh', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img'];
        $allowedAttributes = !empty($config['allowed_attributes']) ? $config['allowed_attributes'] :
            ['id', 'class', 'name', 'value', 'href', 'src'];

        // Sanitize/clean the HTML.
        $maid = new Maid(
            [
                'output-format'   => 'html',
                'allowed-tags'    => $allowedTags,
                'allowed-attribs' => $allowedAttributes,
            ]
        );
        $output = $maid->clean($output);

        return $output;
    }

    /**
     * Create an HTML link to a given URL or ContentType/slug pair.
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
        } elseif ($record = $this->em->getContent($location)) {
            $location = $record->link();
        }

        return sprintf('<a href="%s">%s</a>', $location, $label);
    }

    /**
     * Output a menu.
     *
     * @param Environment $env
     * @param string      $identifier Identifier for a particular menu
     * @param string      $template   the template to use
     * @param array       $params     extra parameters to pass on to the menu template
     *
     * @return string|null
     */
    public function menu(Environment $env, $identifier = '', $template = '_sub_menu.twig', $params = [])
    {
        $menu = $this->menu->menu($identifier);

        $context = [
            'name' => $menu->getName(),
            'menu' => $menu->getItems(),
        ];
        $context += (array) $params;

        return $env->render($template, $context);
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
     * @param Environment $env
     * @param string      $snippet
     * @param array       $context
     *
     * @return string Twig output
     */
    public function twig(Environment $env, $snippet, $context = [])
    {
        Deprecated::warn('Using the Twig filter |twig', 3.3, 'Use import(template_from_string()) instead');

        $template = $env->createTemplate((string) $snippet);

        return twig_include($env, $context, $template, [], true, false, true);
    }
}
