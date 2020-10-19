<?php

namespace Bolt\Twig\Request;

/**
 * ParameterBag is a container for key/value pairs.
 * Overridden in order to disable certain filters.
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ServerBag extends \Symfony\Component\HttpFoundation\ServerBag implements \IteratorAggregate, \Countable
{
    use RestrictedFilterTrait;
}
