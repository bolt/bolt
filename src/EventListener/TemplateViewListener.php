<?php

namespace Bolt\EventListener;

use Bolt\Response\TemplateResponse;
use Bolt\Response\TemplateView;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Converts controller results that are TemplateView's to TemplateResponse's.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class TemplateViewListener implements EventSubscriberInterface
{
    /** @var Environment */
    protected $twig;

    /**
     * Constructor.
     *
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * If controller result is a TemplateView, convert it to a TemplateResponse.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if (!$result instanceof TemplateView) {
            return;
        }

        $response = $this->render($result);

        $event->setResponse($response);
    }

    /**
     * Render TemplateView to a TemplateResponse.
     *
     * @param TemplateView $view
     *
     * @return TemplateResponse
     */
    public function render(TemplateView $view)
    {
        $content = $this->twig->render($view->getTemplate(), $view->getContext()->toArray());

        $response = new TemplateResponse($view->getTemplate(), $view->getContext(), $content);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onView',
        ];
    }
}
