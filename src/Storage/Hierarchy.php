<?php

namespace Bolt\Storage;

use Bolt\Legacy\Content as LegacyContent;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Mapping\ClassMetadata;

/**
 * Hierarchy provider class to handle the traversing and retrieving of hierarchical entities.
 *
 * @author Robert Hunt <robertgahunt@gmail.com>
 */
class Hierarchy
{

    protected $parent = null;
    protected $children = null;
    private $app;
    private $isLegacy = true;
    private $pathPieces = [];
    private $parentTree = [];
    private $lastResult;
    private $routePrefix = '/';

    public function __construct($app)
    {

        $this->app = $app;

        $this->isLegacy = $this->app['config']->get('general/compatibility/setcontent_legacy', true);
    }

    /**
     * Get an array of URL path pieces that make up the hierarchical URL.
     *
     * @param $slug
     *
     * @return array
     */
    private function getPathArray($slug)
    {

        $slug             = trim($slug, '/');
        $this->pathPieces = explode('/', $slug);

        return $this->pathPieces;
    }

    /**
     * Get content wrapper function to handle the Legacy Content class
     *
     * @param       $contentType
     * @param array $params
     * @param array $additionalParams
     *
     * @return array
     */
    private function getContentWrapper($contentType, $params = [], $additionalParams = [])
    {

        if ($this->isLegacy) {
            $result = $this->app['storage']->getContent($contentType, $params, $this->app['pager'], $additionalParams);
        } else {
            $result = $this->app['query']->getContent($contentType, array_merge($params, $additionalParams));
        }

        if (isset($params['returnsingle']) && $params['returnsingle'] !== true && !is_array($result)) {
            $result = [$result];
        }

        return $result;
    }

    /**
     * Content query function to retrieve a piece of content by its contenttype, ID and Parent ID
     *
     * @param      $contentType
     * @param      $id
     * @param int  $parentId
     * @param bool $hydrate
     *
     * @return array
     */
    private function getContentByIdAndParent($contentType, $id, $parentId = 0, $hydrate = false)
    {

        $id = $this->app['slugify']->slugify($id);

        if (is_null($parentId) || $parentId === '') {
            $parentId = 0;
        }

        return $this->getContentWrapper($contentType, [
            'id'           => $id,
            'returnsingle' => true,
            'hydrate'      => $hydrate
        ], ['parentid' => $parentId]);
    }

    /**
     * Content query function to retrieve a piece of content by its contenttype, ID and slug.
     *
     * @param      $contentType
     * @param      $slug
     * @param int  $parentId
     * @param bool $hydrate
     *
     * @return array
     */
    private function getContentBySlugAndParent($contentType, $slug, $parentId = 0, $hydrate = false)
    {

        $slug = $this->app['slugify']->slugify($slug);

        if (is_null($parentId) || $parentId === '') {
            $parentId = 0;
        }

        return $this->getContentWrapper($contentType, [
            'slug'         => $slug,
            'returnsingle' => true,
            'hydrate'      => $hydrate
        ], ['parentid' => $parentId]);
    }

    /**
     * Content query function to retrieve a piece of content by its contenttype and ID.
     *
     * @param      $contentType
     * @param      $id
     * @param bool $hydrate
     *
     * @return array
     */
    public function getContentById($contentType, $id, $hydrate = false)
    {

        $id = $this->app['slugify']->slugify($id);

        return $this->getContentWrapper($contentType, [
            'id'           => $id,
            'returnsingle' => true,
            'hydrate'      => $hydrate
        ]);
    }

    /**
     * Content query function to retrieve a piece of content by its contenttype and slug.
     *
     * @param      $contentType
     * @param      $slug
     * @param bool $hydrate
     *
     * @return array
     */
    public function getContentBySlug($contentType, $slug, $hydrate = false)
    {

        $slug = $this->app['slugify']->slugify($slug);

        return $this->getContentWrapper($contentType, [
            'slug'         => $slug,
            'returnsingle' => true,
            'hydrate'      => $hydrate
        ]);
    }

    /**
     * Get all child Content records of the given $parentId
     *
     * @param        $contentType
     * @param        $parentId
     * @param bool   $hydrate
     * @param string $order
     *
     * @return array Array or Content records
     */
    public function getChildContent($contentType, $parentId, $hydrate = false, $order = 'datepublish')
    {

        return $this->getContentWrapper($contentType, [
            'parentid'     => $parentId,
            'order'        => $order,
            'returnsingle' => false,
            'hydrate'      => $hydrate
        ]);
    }

    /**
     * Get Content from the record's available Hierarchical information.
     *
     * @param      $contentType
     * @param      $slug
     * @param bool $hydrate
     *
     * @return null
     */
    public function getContentFromHierarchy($contentType, $slug, $hydrate = false)
    {

        $this->getPathArray($slug);

        $lastParentId = null;

        if (count($this->pathPieces)) {
            foreach ($this->pathPieces as $slug) {
                /**
                 * @var Content $result
                 */
                $result = $this->getContentBySlugAndParent($contentType, $slug, $lastParentId, $hydrate);

                if (!$result instanceof Content && !$result instanceof LegacyContent && is_numeric($slug)) {
                    /**
                     * @var Content $result
                     */
                    $result = $this->getContentByIdAndParent($contentType, $slug, $lastParentId, $hydrate);
                }

                if ($result instanceof Content || $result instanceof LegacyContent) {
                    $this->parentTree[] = [
                        'id'   => $result['id'],
                        'slug' => $slug
                    ];

                    $this->lastResult = $result;

                    $lastParentId = $result['id'];
                } else {
                    // Parent doesn't exist, the URL is invalid -> Clear parent_tree
                    $this->parentTree = [];
                    $this->lastResult = null;

                    // Don't carry on with the loop, the URL is invalid
                    break;
                }
            }
        }

        return $this->lastResult;
    }

    /**
     * Get an array of slugs for a contents hierarchy
     *
     * @param      $contentType
     * @param      $slug
     * @param bool $hydrate
     *
     * @return array|mixed
     */
    public function getHierarchicalPathArray($contentType, $slug, $hydrate = false)
    {

        if (is_object($slug) && ($slug instanceof Content || $slug instanceof LegacyContent)) {
            /**
             * @var Content $result
             */
            $result = $slug;
        } else {
            /**
             * @var Content $result
             */
            $result = $this->getContentBySlug($contentType, $slug, $hydrate);
        }

        if (!$result instanceof Content && !$result instanceof LegacyContent && is_numeric($slug)) {
            /**
             * @var Content $result
             */
            $result = $this->getContentById($contentType, $slug, $hydrate);
        }

        if ($result instanceof Content || $result instanceof LegacyContent) {
            $this->pathPieces[] = $result->offsetGet('slug');
            $parentId           = $result->offsetGet('parentid');

            if (!is_null($parentId) && $parentId !== 0) {
                $this->pathPieces = $this->getHierarchicalPathArray($contentType, $parentId, $hydrate);
            }
        }

        $pathPieces       = $this->pathPieces;
        $this->pathPieces = [];

        return $pathPieces;
    }

    /**
     * Get an array of IDs for a records hierarchy
     *
     * @param      $contentType
     * @param      $slug
     * @param bool $hydrate
     *
     * @return array|mixed
     */
    public function getHierarchicalIDArray($contentType, $slug, $hydrate = false)
    {

        if (is_object($slug) && ($slug instanceof Content || $slug instanceof LegacyContent)) {
            /**
             * @var Content $result
             */
            $result = $slug;
        } else {
            /**
             * @var Content $result
             */
            $result = $this->getContentBySlug($contentType, $slug, $hydrate);
        }

        if (!$result instanceof Content && !$result instanceof LegacyContent && is_numeric($slug)) {
            /**
             * @var Content $result
             */
            $result = $this->getContentById($contentType, $slug, $hydrate);
        }

        if ($result instanceof Content || $result instanceof LegacyContent) {
            $this->pathPieces[] = $result->offsetGet('id');
            $parentId           = $result->offsetGet('parentid');

            if (!is_null($parentId) && $parentId !== 0) {
                $this->pathPieces = $this->getHierarchicalIDArray($contentType, $parentId, $hydrate);
            }
        }

        $pathPieces       = $this->pathPieces;
        $this->pathPieces = [];

        return $pathPieces;
    }

    public function getContentHierarchy($contentType, $slug, $hydrate = false)
    {

        if (is_object($slug) && ($slug instanceof Content || $slug instanceof LegacyContent)) {
            /**
             * @var Content $result
             */
            $result = $slug;
        } else {
            /**
             * @var Content $result
             */
            $result = $this->getContentBySlug($contentType, $slug, $hydrate);
        }

        if (!$result instanceof Content && !$result instanceof LegacyContent && is_numeric($slug)) {
            /**
             * @var Content $result
             */
            $result = $this->getContentById($contentType, $slug, $hydrate);
        }

        if ($result instanceof Content || $result instanceof LegacyContent) {
            $this->pathPieces[] = $result;
            $parentId           = $result->offsetGet('parentid');

            if (!is_null($parentId) && $parentId !== 0) {
                $this->pathPieces = $this->getContentHierarchy($contentType, $parentId, $hydrate);
            }
        }

        $pathPieces       = $this->pathPieces;
        $this->pathPieces = [];

        return $pathPieces;
    }

    /**
     * Get a path string for a piece of hierarchical content e.g. /root/parent/child/hello-world
     *
     * @param      $contentType
     * @param      $content
     * @param bool $hydrate
     *
     * @return string
     */
    public function getHierarchicalPath($contentType, $content, $hydrate = false)
    {

        $path = $this->getHierarchicalPathArray($contentType, $content, $hydrate);

        if (is_array($path) && count($path)) {
            $path = trim(implode('/', array_reverse($path)), '/');

            if ($path !== '' || $path !== '/') {
                $path = '/' . $path;
            }

            if ($path !== '/') {
                $path .= '/';
            }

            return $path;
        }

        return '/';
    }

    /**
     * Get the slug of the root parent item.
     *
     * @param      $contentType
     * @param      $slug
     * @param bool $hydrate
     *
     * @return bool|string
     */
    public function getRootParentSlug($contentType, $slug, $hydrate = false)
    {

        $parent = false;
        $path   = $this->getHierarchicalPathArray($contentType, $slug, $hydrate);

        if (is_array($path) && count($path)) {
            $parent = array_pop($path);
        }

        return $parent;
    }

    /**
     * Get the ID of the root parent item.
     *
     * @param      $contentType
     * @param      $slug
     * @param bool $hydrate
     *
     * @return bool|mixed
     */
    public function getRootParentID($contentType, $slug, $hydrate = false)
    {

        $parent = false;
        $path   = $this->getHierarchicalIDArray($contentType, $slug, $hydrate);

        if (is_array($path) && count($path)) {
            $parent = array_pop($path);
        }

        return $parent;
    }

    public function getRoutePrefix($contentType, $parentId, $hydrate = false)
    {

        /**
         * @var Content $result
         */
        $result = $this->getContentById($contentType, $parentId, $hydrate);

        if ($result instanceof Content || $result instanceof LegacyContent) {
            $resultParentId = $result->offsetGet('parentid');
            $resultSlug     = $result->offsetGet('slug');

            $this->routePrefix = '/' . $resultSlug . $this->routePrefix;

            if (!is_null($resultParentId)) {
                return $this->getRoutePrefix($contentType, $resultParentId, $hydrate);
            } else {
                $routePrefix = $this->routePrefix;

                return $routePrefix;
            }
        }
    }

    /**
     * Get a full hierarchical array of all content of a particular content type. This is used to build the parent dropdown field.
     *
     * @param      $contentType
     * @param bool $bySlug
     * @param bool $hydrate
     *
     * @return array
     */
    public function getAllHierarchies($contentType, $bySlug = true, $hydrate = false)
    {

        $cacheKey = '_hierarchies_' . $contentType;

        if ($this->app['cache']->contains($cacheKey)) {
            $contents = json_decode($this->app['cache']->fetch($cacheKey), true);
        } else {
            $contents = $this->getContentWrapper($contentType, [
                'returnsingle' => false,
                'hydrate'      => $hydrate
            ]);
            $this->app['cache']->save($cacheKey, json_encode($contents));
        }

        $hierarchy = [];

        if (is_array($contents) && count($contents)) {
            foreach ($contents as $content) {
                if (is_array($content)) {
                    $content = $this->fillContent($contentType, $content);
                }

                $path = $this->getHierarchicalPath($contentType, $content, $hydrate);

                if ($bySlug) {
                    $key = $content->get('slug');
                } else {
                    $key = $content->get('id');
                }

                $hierarchy[$key] = [
                    'key'    => $key,
                    'path'   => $path,
                    'prefix' => $prefix = $this->app['config']->get('contenttypes/' . $contentType . '/fields/slug/route_prefix')
                ];
            }
        }

        return $this->sortByKeyAndParent($hierarchy);
    }

    /**
     * Hierarchical ordering function
     *
     * @param      $array
     * @param bool $asc
     *
     * @return array
     */
    private function sortByKeyAndParent($array, $asc = true)
    {

        usort($array, function ($a, $b) {

            return strnatcmp($a['path'], $b['path']);
        });

        if (!$asc) {
            return array_reverse($array);
        }

        return $array;
    }

    private function fillContent($contentType, $content)
    {

        $metadata = new ClassMetadata(Content::class);

        return $this->app['storage']->create($contentType, $content, $metadata);
    }
}
