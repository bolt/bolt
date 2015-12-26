<?php
/**
 * Created by PhpStorm.
 * User: rix
 * Date: 2015.12.21.
 * Time: 10:32
 */

namespace Bolt\Pager;


use Silex\Application;

/**
 * Class PagerManager
 *  is going to be a centralized service that would be instantiated lazily by PagerServiceProvider.
 *  Furthermore each currently applied Pager:: based, global smelling static call would be served by the manager to
 *  fit TDD principle.
 *  The service will be accessible via $app['pager'] then.
 *
 * Role of the manager
 * - manages Pager elements which are atomic objects of a paging reference and roughly corresponding current Bolt\Pager
 *   objects
 * - responsible for decoding/encoding pager objects from/to query parameters
 * - centralizing all pager related operations like
 *      $page = ($request->query) ? $request->query->get($param, $request->query->get('page', 1)) : 1;
 *      that occures redundantly in code atm
 * - a pager element would be reached as $app['pager']['search']
 * - (string) $app['pager'] for encoding html query rather than Pager::makeLink()
 * - no more Bolt\Legacy\Storage::GetContent() (and others) has to receive &$pager as argument, which is not so clear
 *   enough
 *
 * @package Bolt\Pager
 */
class PagerManager implements \ArrayAccess
{
    const PAGE = 'page';

    protected $app;
    protected $link;
    protected $values;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->decodeHttpQuery();
    }

    /**
     * Used for calling from template to build up right paginated URL link.
     *
     * @param string $linkFor
     * @param string $current
     * @return string
     */
    public function makelink($linkFor = '', $current = '')
    {
        /*
         * If link set directly that forces using it rather than build
         */
        if ($this->link) {
            return $this->link;
        }

        $pageid = $this->makeParameterId($linkFor);
        $parameters = $this->app['request']->query->all();
        if (array_key_exists($pageid, $this->values)) {
            unset($this->values[$pageid]);
        } else {
            unset($this->values[self::PAGE]);
        }

        $this->values[$pageid] = $current;

        return (string) $this;
    }

    /**
     * @param $suffix
     * @return string
     */
    public function makeParameterId($suffix = '')
    {
        $suffix = ($suffix !== '') ? '_'.$suffix : '';

        return self::PAGE.$suffix;
    }

    /**
     * Decodes HTTP query url into manager
     */
    public function decodeHttpQuery()
    {
        foreach ($this->app['request']->query->all() as $key => $parameter) {
            if (strpos($key, self::PAGE) === 0) {
                $this->values[$key] = new Pager(['current' => $parameter]);
            }
        }
    }

    /**
     * @return array
     */
    public function encodeHttpQuery()
    {
        return array_merge($this->app['request']->query->all(), $this->remapPagers());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '?'.$this->encodeHttpQuery();
    }

    public function offsetExists($context)
    {
        return array_key_exists($this->makeParameterId($context), $this->values);
    }

    public function offsetSet($context, $value)
    {
        $this->values[$this->makeParameterId($context)] = ($value instanceof Pager) ? $value : new Pager($value);
    }

    public function offsetUnset($context)
    {
        unset($this->values[$this->makeParameterId($context)]);
    }

    public function offsetGet($context)
    {
        $ctxkey = $this->makeParameterId($context);
        if (array_key_exists($ctxkey, $this->values)) {
            return $this->values[$ctxkey];
        }

        $key = $this->makeParameterId();
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        return $this->values[$ctxkey] = new Pager(['current' => 1]);
    }

    public function keys()
    {
        return array_map(
            function ($key) {
                $chunks = explode('_', $key);

                return array_pop($chunks);
            },
            array_keys($this->values)
        );
    }

    public function isEmptyPager()
    {
        return (count($this->values) === 0);
    }

    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    public function getPager($id = '')
    {
        return ($id) ? $this->values[$id] : end($this->values);
    }

    /**
     * @return array
     */
    protected function remapPagers()
    {
        return array_map(
            function ($pageEl) {
                return $pageEl['current'];
            },
            $this->values
        );
    }
}
