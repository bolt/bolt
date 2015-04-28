<?php
namespace Bolt\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Implements a lazy UrlGenerator.
 * Similar concept with {@see \Silex\LazyUrlMatcher LazyUrlMatcher} and
 * {@see \Symfony\Component\HttpKernel\EventListener\RouterListener RouterListener}
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class LazyUrlGenerator implements UrlGeneratorInterface
{
    private $factory;

    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    public function setContext(RequestContext $context)
    {
        $this->getUrlGenerator()->setContext($context);
    }

    /**
     * @return UrlGeneratorInterface
     */
    public function getUrlGenerator()
    {
        $urlGenerator = call_user_func($this->factory);
        if (!$urlGenerator instanceof UrlGeneratorInterface) {
            throw new \LogicException('Factory supplied to LazyUrlGenerator must return implementation of UrlGeneratorInterface.');
        }

        return $urlGenerator;
    }

    public function getContext()
    {
        return $this->getUrlGenerator()->getContext();
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getUrlGenerator()->generate($name, $parameters, $referenceType);
    }
}
