<?php

namespace Bolt\Twig\Runtime;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Helpers\Deprecated;
use Bolt\Helpers\Excerpt;
use Bolt\Helpers\Str;
use Bolt\Pager\PagerManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Bolt specific Twig functions and filters that provide \Bolt\Legacy\Content manipulation.
 *
 * @internal
 */
class RecordRuntime
{
    /** @var RequestStack */
    private $requestStack;
    /** @var PagerManager */
    private $pagerManager;
    /** @var DirectoryInterface */
    private $templatesDir;
    /** @var array */
    private $themeTemplateSelect;
    /** @var bool */
    private $useTwigGlobals;

    /**
     * Constructor.
     *
     * @param RequestStack       $requestStack
     * @param PagerManager       $pagerManager
     * @param DirectoryInterface $templatesDir
     * @param array              $themeTemplateSelect
     * @param mixed              $useTwigGlobals
     */
    public function __construct(
        RequestStack $requestStack,
        PagerManager $pagerManager,
        DirectoryInterface $templatesDir,
        array $themeTemplateSelect,
        $useTwigGlobals
    ) {
        $this->requestStack = $requestStack;
        $this->pagerManager = $pagerManager;
        $this->templatesDir = $templatesDir;
        $this->themeTemplateSelect = $themeTemplateSelect;
        $this->useTwigGlobals = $useTwigGlobals;
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
        $request = $this->requestStack->getCurrentRequest();
        $requestUri = $request->getRequestUri();
        $routeParams = $request->get('_route_params');

        // If passed a string, and it is in the route.
        if (is_string($content) && in_array($content, $routeParams)) {
            return true;
        }

        if (is_array($content)) {
            $linkToCheck = isset($content['link']) ? $content['link'] : null;
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

        if (!$content instanceof \Bolt\Legacy\Content || !property_exists($content, 'contenttype')) {
            return false;
        }

        // if the current requested page is for the same slug or singularslug.
        $ct = $content->contenttype;
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
     * Lists templates, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listTemplates($filter = null)
    {
        $files = [];

        $name = $filter ? Glob::toRegex($filter, false, false) : '/^[a-zA-Z0-9]\V+\.twig$/';

        /** @var Finder|FileInterface[] $finder */
        $finder = $this->templatesDir->find()
            ->files()
            ->notName('/^_/')
            ->exclude(['node_modules', 'bower_components', '.sass-cache'])
            ->depth('<4')
            ->path($name)
            ->sortByName()
        ;

        foreach ($finder as $file) {
            $name = Str::replaceFirst($this->templatesDir->getFullPath(), '', $file->getFullPath());
            $files[$name] = $name;
        }

        // Check: Have we defined names for any of the matched templates?
        foreach ((array) $this->themeTemplateSelect as $templateFile) {
            if (!empty($templateFile['name']) && !empty($templateFile['filename']) && in_array($templateFile['filename'], $files)) {
                $files[$templateFile['filename']] = $templateFile['name'];
            }
        }

        return $files;
    }

    /**
     * Output a simple pager, for paginated listing pages.
     *
     * @param Environment       $env
     * @param string            $pagerName
     * @param integer           $surround
     * @param string            $template  The template to apply
     * @param string            $class
     *
     * @return string The rendered pager HTML
     */
    public function pager(Environment $env, $pagerName = '', $surround = 4, $template = '_sub_pager.twig', $class = '')
    {
        if ($this->pagerManager->isEmptyPager()) {
            // nothing to page.
            return '';
        }

        $thisPager = $this->pagerManager->getPager($pagerName);

        $context = [
            'pager'    => $thisPager,
            'surround' => $surround,
            'class'    => $class,
        ];

        /* Little hack to avoid doubling this function and having context without breaking frontend */
        if ($template === 'backend') {
            $template = '@bolt/components/pager.twig';
        }

        return $env->render($template, $context);
    }

    /**
     * Return a selected field from a contentset.
     *
     * @param array        $content     A Bolt record array
     * @param array|string $fieldName   Name of a field, or array of field names to return from each record
     * @param boolean      $startempty  Whether or not the array should start with an empty element
     * @param string       $keyName     Name of the key in the array
     * @param string|null  $contentType ContentType string used by the select field
     *
     * @return array
     */
    public function selectField($content, $fieldName, $startempty = false, $keyName = 'id', $contentType = null)
    {
        $retval = $startempty ? [] : ['' => ''];

        if (empty($content)) {
            return $retval;
        }

        foreach ($content as $c) {
            if (is_string($contentType) && $contentType !== '') {
                $contentType = explode(',', $contentType);
            }

            if (is_array($contentType) && count($contentType) > 1) {
                $element = $c->contenttype['slug'] . '/' . $c->values[$keyName];
            } else {
                $element = $c->values[$keyName];
            }

            if (is_array($fieldName)) {
                $row = [];
                foreach ($fieldName as $fn) {
                    if ($fn === 'contenttype') {
                        $row[] = $c->contenttype['singular_name'];
                    } else {
                        $row[] = isset($c->values[$fn]) ? $c->values[$fn] : null;
                    }
                }
                $retval[$element] = $row;
            } else if ($fieldName === 'contenttype') {
                $retval[$element] = $c->contenttype['singular_name'];
            } elseif (isset($c->values[$fieldName])) {
                $retval[$element] = $c->values[$fieldName];
            }
        }
        return $retval;
    }
}
