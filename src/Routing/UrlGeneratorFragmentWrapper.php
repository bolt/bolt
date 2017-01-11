<?php

namespace Bolt\Routing;

use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Wraps a UrlGenerator to allow urls to be generated with a fragment.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class UrlGeneratorFragmentWrapper implements UrlGeneratorInterface, ConfigurableRequirementsInterface
{
    /** @var UrlGeneratorInterface */
    protected $wrapped;

    /**
     * UrlGeneratorFragmentWrapper constructor.
     *
     * @param UrlGeneratorInterface $wrapped
     */
    public function __construct(UrlGeneratorInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritdoc}
     *
     * A key, "fragment", can be passed into $parameters whose value will appended to generated url after a "#"
     */
    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $fragment = isset($parameters['_fragment']) ? $parameters['_fragment'] : null;
        unset($parameters['_fragment']);
        if ($fragment === null && isset($parameters['#'])) {
            $fragment = $parameters['#'];
        }
        unset($parameters['#']);

        $url = $this->wrapped->generate($name, $parameters, $referenceType);

        if (!empty($fragment)) {
            $url .= '#' . strtr(rawurlencode($fragment), ['%2F' => '/', '%3F' => '?']);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->wrapped->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->wrapped->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function setStrictRequirements($enabled)
    {
        if ($this->wrapped instanceof ConfigurableRequirementsInterface) {
            $this->wrapped->setStrictRequirements($enabled);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isStrictRequirements()
    {
        if ($this->wrapped instanceof ConfigurableRequirementsInterface) {
            return $this->wrapped->isStrictRequirements();
        }

        return null; // requirements check is deactivated completely
    }
}
