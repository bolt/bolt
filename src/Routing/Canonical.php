<?php

namespace Bolt\Routing;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

/**
 * This class has two purposes.
 * - Provide a getter (and override setter) to get the canonical url for the current request.
 * - Update the RequestContext with the scheme/host override from the config.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Canonical implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var UriInterface|null An optional scheme/host override. */
    private $globalOverride;
    /** @var bool */
    private $forceSsl;

    /** @var UriInterface|null An optional override for current request to use instead of the UrlGenerator */
    private $override;
    /** @var Request|null The current request. */
    private $request;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface    $urlGenerator   the url generator
     * @param bool                     $forceSsl       whether to force SSL on relative override urls (generated urls get this applied elsewhere)
     * @param UriInterface|string|null $globalOverride an optional scheme and/or host override to apply to all urls
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, $forceSsl = false, $globalOverride = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->forceSsl = $forceSsl;
        $this->setGlobalOverride($globalOverride);
    }

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * This overrides the scheme and host for all urls generated including the canonical one.
     *
     * @param UriInterface|string|null $uri
     */
    public function setGlobalOverride($uri)
    {
        if (is_string($uri)) {
            // Prepend scheme if not included so parse_url doesn't choke.
            if (strpos($uri, 'http') !== 0) {
                $uri = 'http://' . $uri;
            }
            $uri = new Uri($uri);
        }
        Assert::nullOrIsInstanceOf($uri, UriInterface::class, 'Expected a string, UriInterface, or null. Got: %s');

        $this->globalOverride = $uri;
    }

    /**
     * This overrides the the canonical url. It will be resolved against the current request.
     *
     * Note: This only applies to the current request, so it will need to be called again for the next one.
     *
     * @param UriInterface|string|null $uri
     */
    public function setOverride($uri)
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        Assert::nullOrIsInstanceOf($uri, UriInterface::class, 'Expected a string, UriInterface, or null. Got: %s');

        $this->override = $uri;
    }

    /**
     * Returns the canonical url for the current request,
     * or null if called outside of the request cycle.
     *
     * @return string|null
     */
    public function getUrl()
    {
        // Ensure in request cycle (even for override).
        if ($this->request === null) {
            return null;
        }

        // Ensure request has been matched
        if (!$this->request->attributes->get('_route')) {
            return null;
        }

        if ($this->override) {
            $this->resolveCurrentOverride();

            return (string) $this->override;
        }

        return $this->urlGenerator->generate(
            $this->request->attributes->get('_route'),
            $this->request->attributes->get('_route_params'),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Stores the current request and applies the global override.
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        $this->setRequest($event->getRequest());

        $this->applyGlobalOverride();
    }

    /**
     * Clear the current request and override.
     *
     * @param FinishRequestEvent $event
     */
    public function onFinishRequest(FinishRequestEvent $event)
    {
        $this->setRequest(null);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST        => ['onRequest', 31], // Right after RouterListener
            KernelEvents::FINISH_REQUEST => 'onFinishRequest',
        ];
    }

    /**
     * Set the current request and reset the current override.
     *
     * @param Request|null $request
     */
    private function setRequest(Request $request = null)
    {
        $this->request = $request;
        $this->override = null;
    }

    /**
     * Sets the scheme and host overrides (if any) on the UrlGenerator's RequestContext.
     *
     * This needs to happen after RouterListener as that sets the scheme
     * and host from the request. To override we need to be after that.
     */
    private function applyGlobalOverride()
    {
        if (!$this->globalOverride) {
            return;
        }

        $context = $this->urlGenerator->getContext();

        // Only override scheme if it's an upgrade to https.
        // i.e Don't do: https -> http
        if ($this->globalOverride->getScheme() === 'https') {
            $context->setScheme('https');
        }
        if ($host = $this->globalOverride->getHost()) {
            $context->setHost($host);
        }
    }

    /**
     * If there is a current override, resolve it to an absolute url based on current request.
     */
    private function resolveCurrentOverride()
    {
        // Absolute url or network path
        if ($this->override->getHost() !== '') {
            return;
        }

        $context = $this->urlGenerator->getContext();

        if (Uri::isRelativePathReference($this->override)) {
            $this->override = $this->override->withPath($context->getPathInfo() . '/' . $this->override->getPath());
        }

        $scheme = $this->forceSsl ? 'https' : $context->getScheme();
        $this->override = $this->override
            ->withScheme($scheme)
            ->withHost($context->getHost())
            ->withPort($scheme === 'http' ? $context->getHttpPort() : $context->getHttpsPort())
            ->withPath($context->getBaseUrl() . $this->override->getPath())
        ;
    }
}
