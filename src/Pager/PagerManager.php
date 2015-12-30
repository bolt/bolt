<?php

namespace Bolt\Pager;

use Silex\Application;

/**
 * Class PagerManager
 * -------------------
 *  is a centralized service that would be instantiated lazily by PagerServiceProvider.
 *  It's changes Pager:: based, global smelling static calls that would be served by the manager to
 *  fit TDD principle.
 *  The service will be accessible via ``$app['pager']`` then.
 *
 * Role of the manager
 * -------------------
 * - manages Pager elements which are atomic objects of a paging reference and roughly corresponding current Bolt\Pager
 *   objects
 * - responsible for decoding/encoding pager objects from/to query parameters
 * - centralizing all pager related operations like
 *      ``$page = ($request->query) ? $request->query->get($param, $request->query->get('page', 1)) : 1;``
 *      that occurs redundantly in code atm
 * - a pager element would be reached as ``$app['pager']['search']``
 * - ``(string) $app['pager']`` for encoding html query rather than ``Pager::makeLink()``
 * - no more ``Bolt\Legacy\Storage::GetContent()`` (and others) has to receive &$pager as argument, which is not so clear
 *   enough
 *
 * Conventions:
 * ------------
 *  - *Context Id* : Textual id of a pager element. It is hints the context or content type where the pager is refers to
 *  - *Pager Id* / *Parameter Id* : Full text id of a pager object in placeholder array. It is a key with
 * ``<PAGE>_<context_id>``. Query parameters can contain current page state under this parameter id.
 *
 * Practical:
 * ----------
 *  - Variable ``pager`` injected into templates contains a member ``manager`` furthermore, so PagerManager API can be
 *  accessed via
 *  - Instantiating PagerManager - like reaching ``$app['pager']`` - decodes http page parameters and can be addressed
 *  by their context id. So ``$app['pager']['entities']`` returns a Pager object decoded from query parameters was
 *  ``page_entities=N`` originally
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerManager implements \ArrayAccess
{
    const PAGE = 'page';

    protected $app;
    protected $link;
    protected $pagers = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->pagers = $this->decodeHttpQuery();
    }

    /**
     * Use for calling from template to build up paginated URL link.
     * It preserves each http query parameters except which is requested and returns a http GET query string.
     * Last parameter appended to the end of the string so its value can be just concatenating.
     * Against deprecated Pager::makelink() this is will build link based on initialized pagers.
     *
     * @param string $linkFor [optional] Id of pager the link should be built for. With empty argument passing
     *          the link will be built for the first initialized pager object found.
     * @return string GET query string
     */
    public function makelink($linkFor = '')
    {
        /*
         * If link set directly that forces using it rather than build
         */
        if ($this->link) {
            return $this->link;
        }

        $pagerid = $this->findPagerId($linkFor);
        $saved = $this->pagers[$pagerid];
        unset($this->pagers[$pagerid]);

        $qparams = $this->app['request']->query->all();
        unset($qparams[$pagerid]);

        $chunks = [];
        $chunks[] = $this->encodeHttpQuery($qparams);
        if ($pagerid) {
            $chunks[] = "{$pagerid}=";
        }
        $link = '?'.implode('&', $chunks);

        $this->pagers[$pagerid] = $saved;

        return $link;
    }

    /**
     * Parameter Id builder.
     * Http query parameter names of pager objects are built of PAGE constant and a name of pager that is refers
     * to its context.
     *
     * @param $contextId
     * @return string
     */
    public function makeParameterId($contextId = '')
    {
        $contextId = ($contextId !== '') ? '_'.$contextId : '';

        return self::PAGE.$contextId;
    }

    /**
     * Decodes HTTP query url and stores in addressable format.
     */
    public function decodeHttpQuery()
    {
        $values = [];
        foreach ($this->app['request']->query->all() as $key => $parameter) {
            if (strpos($key, self::PAGE) === 0) {
                $chunks = explode('_', $key);
                $contextId = end($chunks);
                $values[$key] = new Pager(
                    ['current' => $parameter, 'for' => $contextId, 'manager' => $this],
                    \ArrayObject::ARRAY_AS_PROPS
                );
            }
        }

        return $values;
    }

    /**
     * Encodes Http GET query string from actual parts of query parameters and from 'current' values of pager objects
     *
     * @param array|null $qparams [optional] Optional parameters where actual values to be merged into
     * @return string Encoded query string
     */
    public function encodeHttpQuery($qparams = null)
    {
        $qparams = ($qparams === null) ? $this->app['request']->query->all() : $qparams;

        return http_build_query(array_merge($qparams, $this->remapPagers()));
    }

    /**
     * Object string encoder
     *
     * @return string Encoded query string
     */
    public function __toString()
    {
        return '?'.$this->encodeHttpQuery();
    }

    /**
     * Pager element object existence check at a specific context id
     *
     * @param mixed $contextId Context Id of pager object
     * @return bool
     */
    public function offsetExists($contextId)
    {
        return array_key_exists($this->makeParameterId($contextId), $this->pagers);
    }

    /**
     * Set the Pager object
     *
     * @param mixed $contextId
     * @param mixed $pager
     */
    public function offsetSet($contextId, $pager)
    {
        $pager = ($pager instanceof Pager) ?: new Pager($pager, \ArrayObject::ARRAY_AS_PROPS);
        $pager['manager'] = $this;
        $this->pagers[$this->makeParameterId($contextId)] = $pager;
    }

    /**
     * Unset a Pager object
     *
     * @param mixed $contextId
     */
    public function offsetUnset($contextId)
    {
        unset($this->pagers[$this->makeParameterId($contextId)]);
    }

    /**
     * @param mixed $contextId
     * @return Pager
     */
    public function offsetGet($contextId)
    {
        $ctxkey = $this->makeParameterId($contextId);
        if (array_key_exists($ctxkey, $this->pagers)) {
            return $this->pagers[$ctxkey];
        }

        return false;
    }

    /**
     * Returns context ids array
     *
     * @return array
     */
    public function keys()
    {
        return array_map(
            function ($key) {
                $chunks = explode('_', $key);

                return array_pop($chunks);
            },
            array_keys($this->pagers)
        );
    }

    /**
     * Returns empty status of Pager object array
     *
     * @return bool
     */
    public function isEmptyPager()
    {
        return empty($this->pagers);
    }

    /**
     * Set pager manager link value directly forcing link build.
     *
     * @param string $link Link to force
     * @return $this
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Gets the explicitly indexed pager or finds a completely initialized one.
     * Pager is initialized if its _$totalpages_ attribute set.
     *
     * @param string $contextId [optional]
     * @return mixed
     */
    public function getPager($contextId = '')
    {
        return ($contextId) ? $this->pagers[$this->makeParameterId($contextId)] : $this->pagers[$this->findInitializedPagerId()];
    }

    /**
     * @param string $contextId
     * @return Pager|int
     */
    public function getCurrentPage($contextId = '')
    {
        $pager = $this->offsetGet($contextId) ?: $this->offsetGet('');

        return ($pager) ? $pager['current'] : 1;
    }

    /**
     * @return array
     */
    public function getPagers()
    {
        return $this->pagers;
    }

    /**
     * Gets a parameter id of an explicit context id or gets a valid one
     *
     * @param string $contextId [optional]
     * @return int|string
     */
    protected function findPagerId($contextId = '')
    {
        return ($contextId) ? $this->makeParameterId($contextId) : $this->findInitializedPagerId();
    }

    /**
     * Builds an array of Pagers with current page values
     *
     * @return array
     */
    protected function remapPagers()
    {
        return array_map(
            function ($pageEl) {
                return $pageEl['current'];
            },
            $this->pagers
        );
    }

    /**
     * Finds any initialized pager and gets its pager id
     *
     * @return int|string
     */
    protected function findInitializedPagerId()
    {
        foreach ($this->pagers as $key => $pager) {
            if (array_key_exists('totalpages', $pager)) {
                return $key;
            }
        }

        return '';
    }
}
