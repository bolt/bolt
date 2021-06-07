<?php

namespace Bolt\Twig\Runtime;

use Bolt\Common\Deprecated;
use Bolt\Common\Str;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Helpers\Excerpt;
use Bolt\Legacy;
use Bolt\Pager\PagerManager;
use Bolt\Storage\Collection\Taxonomy;
use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\Query\Query;
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
    /** @var Query  */
    private $query;

    /**
     * Constructor.
     *
     * @param RequestStack       $requestStack
     * @param PagerManager       $pagerManager
     * @param DirectoryInterface $templatesDir
     * @param array              $themeTemplateSelect
     * @param mixed              $useTwigGlobals
     * @param Query              $query
     */
    public function __construct(
        RequestStack $requestStack,
        PagerManager $pagerManager,
        DirectoryInterface $templatesDir,
        array $themeTemplateSelect,
        $useTwigGlobals,
        Query $query
    ) {
        $this->requestStack = $requestStack;
        $this->pagerManager = $pagerManager;
        $this->templatesDir = $templatesDir;
        $this->themeTemplateSelect = $themeTemplateSelect;
        $this->useTwigGlobals = $useTwigGlobals;
        $this->query = $query;
    }

    /**
     * Returns true, if the given content is the current content.
     *
     * If we're on page/foo, and content is that page, you can use
     * {% is page|current %}class='active'{% endif %}
     *
     * @param \Bolt\Legacy\Content|array $content
     *
     * @return bool true if the given content is on the curent page
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
     * @param int                               $length  Defaults to 200 characters
     * @param array|string|null                 $focus
     * @param array                             $stripFields
     *
     * @return string Resulting excerpt
     */
    public function excerpt($content, $length = 200, $focus = null, $stripFields = [])
    {
        $excerpter = new Excerpt($content);

        return $excerpter->getExcerpt($length, false, $focus, $stripFields);
    }

    /**
     * Gets the first image from the given content record.
     *
     * This method is deprecated. In Bolt 3.5 it'll be replaced / removed to
     * fall in line with the work done for #6985
     *
     * @param \Bolt\Storage\Entity\Content $content
     *
     * @return array|null image
     */
    public function getFirstImage($content)
    {
        Deprecated::method('3.4');

        $contentType = $content->getContenttype();
        if (!$contentType instanceof ContentType) {
            return null;
        }
        $fields = $contentType->getFields();
        if (!is_array($fields)) {
            return null;
        }

        // Grab the first field of type 'image', and return that.
        foreach ($fields as $key => $field) {
            if ($field['type'] === 'image' && is_array($content->get($key))) {
                return $content->get($key);
            }
        }

        // otherwise, no image.
        return null;
    }

    /**
     * Output all (relevant) fields to the browser. Convenient for dumping the
     * content in order in, say, a `record.twig` template, without having to
     * iterate over them in the browser.
     *
     * @param Environment          $env
     * @param \Bolt\Legacy\Content $record
     * @param bool                 $common
     * @param bool                 $extended
     * @param bool                 $repeaters
     * @param bool                 $templateFields
     * @param string               $template
     * @param string|array         $exclude
     * @param bool                 $skip_uses
     *
     * @return string|null
     */
    public function fields(
        Environment $env,
        $record = null,
        $common = true,
        $extended = false,
        $repeaters = true,
        $templateFields = true,
        $template = '_sub_fields.twig',
        $exclude = null,
        $skip_uses = true
    ) {
        Deprecated::method('3.3');

        // If $record is empty, we must get it from the global scope in Twig.
        if (!$record instanceof \Bolt\Legacy\Content) {
            $globals = $env->getGlobals();
            $record = isset($globals['record']) ? $globals['record'] : [];
        }

        // Still no record? Nothing to do here, then.
        if (!$record instanceof \Bolt\Legacy\Content && !$record instanceof Entity\Content) {
            return null;
        }

        if (!is_array($exclude)) {
            $exclude = explode(',', $exclude) ?: [];
            $exclude = array_map('trim', $exclude);
        }

        $context = [
            'record'         => $record,
            'common'         => $common,
            'extended'       => $extended,
            'repeaters'      => $repeaters,
            'templatefields' => $templateFields,
            'exclude'        => $exclude,
            'skip_uses'      => $skip_uses,
        ];

        return $env->render($template, $context);
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
            $name = Str::replaceFirst($file->getFullPath(), $this->templatesDir->getFullPath(), '');
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
     * @param Entity\Content $entity
     * @param string         $field
     * @param array          $where
     *
     * @return Entity\Content|null
     */
    public function next($entity, $field = 'datepublish', $where = [])
    {
        if ($field[0] === '-') {
            $operator = '<';
            $field = substr($field, 1);
            $order = '-' . $field;
        } elseif ($field[0] === '+') {
            $operator = '>';
            $field = substr($field, 1);
            $order = $field;
        } else {
            $operator = '>';
            $order = $field;
        }
        $params = [
            $field         => $operator . $entity->get($field),
            'limit'        => 1,
            'order'        => $order,
            'returnsingle' => true,
        ];
        $params = array_merge($params, $where);

        return $this->query->getContent((string) $entity->getContenttype(), $params);
    }

    /**
     * @param Entity\Content $entity
     * @param string         $field
     * @param array          $where
     *
     * @return Entity\Content|null
     */
    public function previous($entity, $field = 'datepublish', array $where = [])
    {
        if ($field[0] === '-') {
            $operator = '>';
            $field = substr($field, 1);
            $order = '-' . $field;
        } elseif ($field[0] === '+') {
            $operator = '<';
            $field = substr($field, 1);
            $order = $field;
        } else {
            $operator = '<';
            $order = $field;
        }
        $params = [
            $field         => $operator . $entity->get($field),
            'limit'        => 1,
            'order'        => $order,
            'returnsingle' => true,
        ];
        $params = array_merge($params, $where);

        return $this->query->getContent((string) $entity->getContenttype(), $params);
    }

    /**
     * Output a simple pager, for paginated listing pages.
     *
     * @param Environment $env
     * @param string      $pagerName
     * @param int         $surr
     * @param string      $template  The template to apply
     * @param string      $class
     *
     * @return string The rendered pager HTML
     */
    public function pager(Environment $env, $pagerName = '', $surr = 4, $template = '_sub_pager.twig', $class = '')
    {
        if ($this->pagerManager->isEmptyPager()) {
            // nothing to page.
            return '';
        }

        $thisPager = $this->pagerManager->getPager($pagerName);

        $context = [
            'pager'    => $thisPager,
            'surr'     => $surr, // @deprecated
            'surround' => $surr,
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
     * @param bool         $startempty  Whether or not the array should start with an empty element
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
            } elseif ($fieldName === 'contenttype') {
                $retval[$element] = $c->contenttype['singular_name'];
            } elseif (isset($c->values[$fieldName])) {
                $retval[$element] = $c->values[$fieldName];
            }
        }

        return $retval;
    }

    /**
     * @param array|Taxonomy|Entity\Content|Legacy\Content $candidate
     *
     * @return array
     */
    public function taxonomy($candidate)
    {
        // If this is a legacy content object then we set the candidate to the taxonomy array
        if ($candidate instanceof Legacy\Content) {
            $candidate = $candidate->taxonomy;
        }

        // If it's a content entity then fetch the taxonomy field
        if ($candidate instanceof Entity\Content) {
            $candidate = $candidate->getTaxonomy();
        }

        // By this point we should have either an old-style array of taxonomies or a new-style
        // Taxonomy Collection if the former then we can return at this point
        if (is_array($candidate)) {
            return $candidate;
        }

        // Finally just as a safeguard, if for any reason we don't have at this point a
        // Taxonomy collection we return an empty array so we can guarantee the return type.
        if (!$candidate instanceof Taxonomy) {
            return [];
        }

        $compiled = [];
        foreach ($candidate as $el) {
            $type = $el->getTaxonomytype();
            $slug = $el->getSlug();
            $compiled[$type]["/$type/$slug"] = $slug;
        }

        return $compiled;
    }
}
