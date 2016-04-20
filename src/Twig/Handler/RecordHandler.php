<?php

namespace Bolt\Twig\Handler;

use Bolt\Helpers\Excerpt;
use Silex;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\HttpFoundation\Request;

/**
 * Bolt specific Twig functions and filters that provide \Bolt\Legacy\Content manipulation
 *
 * @internal
 */
class RecordHandler
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
     * Returns true, if the given content is the current content.
     *
     * If we're on page/foo, and content is that page, you can use
     * {% is page|current %}class='active'{% endif %}
     *
     * @param \Bolt\Legacy\Content|array $content
     *
     * @return boolean True if the given content is on the curent page.
     */
    public function current($content)
    {
        /** @var Request $request */
        $request = $this->app['request'];
        $requestUri = $request->getPathInfo();
        $routeParams = $request->get('_route_params');

        // If passed a string, and it is in the route.
        if (is_string($content) && in_array($content, $routeParams)) {
            return true;
        }

        if (is_array($content) && isset($content['link'])) {
            $linkToCheck = $content['link'];
        } elseif ($content instanceof \Bolt\Legacy\Content) {
            $linkToCheck = $content->link();
        } else {
            $linkToCheck = (string) $content;
        }

        // check against Request Uri
        if ($requestUri === $linkToCheck) {
            return true;
        }

        // No contenttypeslug or slug -> not 'current'
        if (empty($routeParams['contenttypeslug']) || empty($routeParams['slug'])) {
            return false;
        }

        // check against simple content.link
        if ('/' . $routeParams['contenttypeslug'] . '/' . $routeParams['slug'] === $linkToCheck) {
            return true;
        }

        if (!isset($content['contenttype'])) {
            return false;
        }

        // if the current requested page is for the same slug or singularslug.
        $ct = $content['contenttype'];
        if ($routeParams['contenttypeslug'] === $ct['slug'] || $routeParams['contenttypeslug'] === $ct['singular_slug']) {
            // …and the slugs should match.
            return $routeParams['slug'] === $content['slug'];
        }

        return false;
    }

    /**
     * Create an excerpt for the given content.
     *
     * @param \Bolt\Legacy\Content|array|string $content
     * @param integer                           $length  Defaults to 200 characters
     * @param array|string|null                 $focus
     *
     * @return string Resulting excerpt
     */
    public function excerpt($content, $length = 200, $focus = null)
    {
        $excerpter = new Excerpt($content);
        $excerpt = $excerpter->getExcerpt($length, false, $focus);

        return $excerpt;
    }

    /**
     * Output all (relevant) fields to the browser. Convenient for dumping the
     * content in order in, say, a `record.twig` template, without having to
     * iterate over them in the browser.
     *
     * @param \Twig_Environment    $env
     * @param \Bolt\Legacy\Content $record
     * @param bool                 $common
     * @param bool                 $extended
     * @param bool                 $repeaters
     * @param bool                 $templatefields
     * @param string               $template
     * @param string|array         $exclude
     *
     * @return string
     */
    public function fields(\Twig_Environment $env, $record = null, $common = true, $extended = false, $repeaters = true, $templatefields = true, $template = '_sub_fields.twig', $exclude = null)
    {
        // If $record is empty, we must get it from the global scope in Twig.
        if (!$record instanceof \Bolt\Legacy\Content) {
            $globals = $env->getGlobals();
            $record = isset($globals['record']) ? $globals['record'] : [];
        }

        // Still no record? Nothing to do here, then.
        if (!$record instanceof \Bolt\Legacy\Content) {
            return;
        }

        if (!is_array($exclude)) {
            $exclude = array_map('trim', explode(',', $exclude));
        }

        $context = [
            'record'         => $record,
            'common'         => $common,
            'extended'       => $extended,
            'repeaters'      => $repeaters,
            'templatefields' => $templatefields,
            'exclude'        => $exclude,
        ];

        return new \Twig_Markup($env->render($template, $context), 'utf-8');
    }

    /**
     * Trims the given string to a particular length. Deprecated, use excerpt
     * instead.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string  $content
     * @param integer $length  Defaults to 200
     *
     * @return string Trimmed output
     */
    public function trim($content, $length = 200)
    {
        return $this->excerpt($content, $length);
    }

    /**
     * Lists templates, optionally filtered by $filter.
     *
     * @param string  $filter
     * @param boolean $safe
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listTemplates($filter = null, $safe = false)
    {
        // No need to list templates in safe mode.
        if ($safe) {
            return null;
        }

        $files = [];

        $name = $filter ? Glob::toRegex($filter, false, false) : '/^[a-zA-Z0-9]\V+\.twig$/';
        $finder = new Finder();
        $finder->files()
            ->in($this->app['resources']->getPath('templatespath'))
            ->notname('/^_/')
            ->notPath('node_modules')
            ->notPath('bower_components')
            ->notPath('.sass-cache')
            ->depth('<4')
            ->path($name)
            ->sortByName()
        ;

        foreach ($finder as $file) {
            $name = $file->getRelativePathname();
            $files[$name] = $name;
        }

        // Get the active themeconfig
        $themeConfig = $this->app['config']->get('theme/templateselect/templates', false);

        // Check: Have we defined names for any of the matched templates?
        if ($themeConfig) {
            foreach ($themeConfig as $templateFile) {
                if (!empty($templateFile['name']) && !empty($templateFile['filename']) && in_array($templateFile['filename'], $files)) {
                    $files[$templateFile['filename']] = $templateFile['name'];
                }
            }
        }

        return $files;
    }

    /**
     * Output a simple pager, for paginated listing pages.
     *
     * @param \Twig_Environment $env
     * @param string            $pagerName
     * @param integer           $surr
     * @param string            $template  The template to apply
     * @param string            $class
     *
     * @return string The rendered pager HTML
     */
    public function pager(\Twig_Environment $env, $pagerName = '', $surr = 4, $template = '_sub_pager.twig', $class = '')
    {
        if ($this->app['pager']->isEmptyPager()) {
            // nothing to page.
            return '';
        }

        $thisPager = $this->app['pager']->getPager($pagerName);

        $context = [
            'pager' => $thisPager,
            'surr'  => $surr,
            'class' => $class,
        ];

        /* Little hack to avoid doubling this function and having context without breaking frontend */
        if ($template === 'backend') {
            $context = ['context' => $context];
            $template = '@bolt/components/pager.twig';
        }

        return new \Twig_Markup($env->render($template, $context), 'utf-8');
    }

    /**
     * Return a selected field from a contentset.
     *
     * @param array        $content    A Bolt record array
     * @param array|string $fieldName  Name of a field, or array of field names to return from each record
     * @param boolean      $startempty Whether or not the array should start with an empty element
     * @param string       $keyName    Name of the key in the array
     *
     * @return array
     */
    public function selectField($content, $fieldName, $startempty = false, $keyName = 'id')
    {
        $retval = $startempty ? [] : ['' => ''];

        if (empty($content)) {
            return $retval;
        }

        foreach ($content as $c) {
            $element = $c->values[$keyName];
            if (is_array($fieldName)) {
                $row = [];
                foreach ($fieldName as $fn) {
                    $row[] = isset($c->values[$fn]) ? $c->values[$fn] : null;
                }
                $retval[$element] = $row;
            } elseif (isset($c->values[$fieldName])) {
                $retval[$element] = $c->values[$fieldName];
            }
        }

        return $retval;
    }
}
