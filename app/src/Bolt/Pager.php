<?php
namespace Finder;

use Bolt\Application;
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

    public function __construct($array, Application $app)
    {
        parent::__construct($array);
        $this->app = $app;
    }

    public function makelink()
    {
        $pageid = $this->for . '_page';
        $parameters = $this->ext->app['request']->query->all();
        if (array_key_exists($pageid, $parameters)) {
            unset($parameters[$pageid]);
        }
        array_walk($parameters, function(&$item, $key) {
            $item = "$key=$item";
        });
        $link = '?' . implode('&', $parameters) . '&' . $pageid . '=';
        return $link;
    }
}