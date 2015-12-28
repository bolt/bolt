<?php
/**
 * Created by PhpStorm.
 * User: rix
 * Date: 2015.12.21.
 * Time: 12:44
 */

namespace Bolt\Pager;


class Pager extends \ArrayObject
{
    /*
     * Possible ArrayObject members
     *
     *  public $for;
     *  public $count;
     *  public $totalpages;
     *  public $current;
     *  public $showing_from;
     *  public $showing_to;
     *
     *  public $manager;
     */

    /**
     *
     * @param string $linkFor
     * @return mixed
     */
    public function makelink($linkFor = '')
    {
        return $this->manager->makelink($linkFor);
    }
}
