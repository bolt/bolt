<?php

namespace Bolt;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * This class has two purposes.
 * - Provide a getter to get the canonical url for the current request.
 * - Update the RequestContext with the scheme/host override from the config.
 *
 * Note: Updating the RequestContext also applies to the UrlGenerator.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Canonical implements EventSubscriberInterface
{
    /** @var RequestStack */
    protected $requestStack;
    /** @var RequestContext */
    protected $requestContext;
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;
    /** @var string|null An optional scheme/host override. */
    private $override;

    /**
     * Constructor.
     *
     * @param RequestStack          $requestStack
     * @param RequestContext        $requestContext
     * @param UrlGeneratorInterface $urlGenerator
     * @param string|null           $override       An optional scheme/host override.
     */
    public function __construct(
        RequestStack $requestStack,
        RequestContext $requestContext,
        UrlGeneratorInterface $urlGenerator,
        $override = null
    ) {
        $this->requestStack = $requestStack;
        $this->requestContext = $requestContext;
        $this->urlGenerator = $urlGenerator;
        $this->override = $override;
    }

    /**
     * Returns the canonical url for the current request,
     * or null if called outside of the request cycle.
     *
     * @return string|null
     */
    public function getUrl()
    {
        if (($request = $this->requestStack->getCurrentRequest()) === null) {
            return null;
        }

        return $this->urlGenerator->generate(
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params'),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Sets the scheme and host overrides (if any) on the RequestContext.
     *
     * This needs to happen after RouterListener as that sets the scheme
     * and host from the request. To override we need to be after that.
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$this->override) {
            return;
        }

        $override = $this->override;

        // Prepend scheme if not included so parse_url doesn't choke.
        if (strpos($override, 'http') !== 0) {
            $override = 'http://' . $override;
        }
        $parts = parse_url($override);

        // Only override scheme if it's an upgrade to https.
        // i.e Don't do: https -> http
        if (isset($parts['scheme']) && $parts['scheme'] === 'https') {
            $this->requestContext->setScheme($parts['scheme']);
        }
        if (isset($parts['host'])) {
            $this->requestContext->setHost($parts['host']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 31], // Right after RouterListener
        ];
    }
}
