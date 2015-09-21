<?php
namespace Bolt;

use Silex;

class Pager extends \ArrayObject
{
    public $for;

    public $count;

    public $totalpages;

    public $current;

    public $showing_from;

    public $showing_to;

    public $link;

    protected $app;

    public function __construct($array, Silex\Application $app)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = new static($value, $app);
            }
            $this->$key = $value;
        }

        $this->app = $app;
    }

    /**
     * Used for calling from template to build up right paginated URL link.
     *
     * @return string
     */
    public function makelink()
    {
        /*
         * If link set directly that forces using it rather than build
         */
        if ($this->link) {
            return $this->link;
        }

        $pageid = static::makeParameterId($this->for);
        $parameters = $this->app['request']->query->all();
        if (array_key_exists($pageid, $parameters)) {
            unset($parameters[$pageid]);
        } else {
            unset($parameters['page']);
        }

        $parameters[$pageid] = '';
        $link = '?' . http_build_query($parameters);

        return $link;
    }

    public static function makeParameterId($suffix)
    {
        $suffix = ($suffix !== '') ? '_' . $suffix : '';

        return 'page' . $suffix;
    }
}
