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
    /** @var \Closure $factory */
    private $factory;
    /** @var UrlGeneratorInterface $urlGenerator */
    private $urlGenerator;

    /**
     * LazyUrlGenerator constructor.
     *
     * @param \Closure $factory Should return UrlGeneratorInterface when invoked
     */
    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->getUrlGenerator()->setContext($context);
    }

    /**
     * @return UrlGeneratorInterface
     */
    public function getUrlGenerator()
    {
        if (!$this->urlGenerator) {
            $this->urlGenerator = call_user_func($this->factory);
            if (!$this->urlGenerator instanceof UrlGeneratorInterface) {
                throw new \LogicException('Factory supplied to LazyUrlGenerator must return implementation of UrlGeneratorInterface.');
            }
        }

        return $this->urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->getUrlGenerator()->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getUrlGenerator()->generate($name, $parameters, $referenceType);
    }
}
