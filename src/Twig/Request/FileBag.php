<?php

namespace Bolt\Twig\Request;

/**
 * ParameterBag is a container for key/value pairs.
 * Overridden in order to disable certain filters.
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FileBag extends \Symfony\Component\HttpFoundation\FileBag implements \IteratorAggregate, \Countable
{
    use RestrictedFilterTrait;
}
