<?php

namespace Bolt\EventListener;

use Bolt\Controller\Zone;
use Bolt\Legacy\Content;
use Bolt\Legacy\Storage;
use Bolt\TemplateChooser;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;

/**
 * Renders the not found page in the event of an HTTP exception.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class NotFoundListener implements EventSubscriberInterface
{
    /** @var string */
    protected $notFoundPage;
    /** @var Storage */
    protected $storage;
    /** @var TemplateChooser */
    protected $templateChooser;
    /** @var Environment */
    private $twig;

    /**
     * Constructor.
     *
     * @param string|array    $notFoundPage
     * @param Storage         $storage
     * @param TemplateChooser $templateChooser
     * @param Environment     $twig
     */
    public function __construct($notFoundPage, Storage $storage, TemplateChooser $templateChooser, Environment $twig)
    {
        $this->notFoundPage = (array) $notFoundPage;
        $this->storage = $storage;
        $this->templateChooser = $templateChooser;
        $this->twig = $twig;
    }

    /**
     * Render the not found page if on frontend and http exception.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $exception = $event->getException();
        if (!$exception instanceof HttpExceptionInterface || Zone::isBackend($request)) {
            return;
        }
        if ($exception->getStatusCode() !== Response::HTTP_NOT_FOUND) {
            return;
        }
        // If no zone is set, assume front-end as SELECT queries can leak unpublished records
        if (Zone::get($request) === null) {
            Zone::set($request, Zone::FRONTEND);
        }

        foreach ($this->notFoundPage as $item) {
            try {
                $this->renderNotFound($event, $item);

                return;
            } catch (LoaderError $e) {
                // Template not found, fall though to see if we can render a
                // record, failing that let the exception handler take over
            }

            $content = $this->storage->getContent($item, ['returnsingle' => true]);
            if ($content instanceof Content && !empty($content->id)) {
                $template = $this->templateChooser->record($content);
                $this->renderNotFound($event, $template, $content->getTemplateContext());

                return;
            }
        }

        $msg = sprintf(
            'No page could be shown, because the "notfound" setting "%s" in config.yml or theme.yml is not valid.',
            implode(', ', $this->notFoundPage)
        );
        $event->setException(new NotFoundHttpException($msg, $e));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // After loggers at -4, but before default at -8
            KernelEvents::EXCEPTION => ['onKernelException', -6],
        ];
    }

    /**
     * Render a not found template.
     *
     * @param GetResponseForExceptionEvent $event
     * @param string|string[]              $template
     * @param array                        $context
     *
     * @throws RuntimeException
     */
    private function renderNotFound(GetResponseForExceptionEvent $event, $template, array $context = [])
    {
        try {
            $html = $this->twig->resolveTemplate($template)->render($context);
            $event->setResponse(new Response($html, Response::HTTP_NOT_FOUND));
        } catch (RuntimeError $e) {
            throw new RuntimeException('Unable to render 404 page!', null, $e);
        }
    }
}
