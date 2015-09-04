<?php
namespace Bolt\EventListener;

use Bolt\Controller\Zone;
use Bolt\Legacy\Content;
use Bolt\Render;
use Bolt\Legacy\Storage;
use Bolt\TemplateChooser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders the not found page in the event of an HTTP exception
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
    /** @var Render */
    protected $render;

    /**
     * NotFoundListener constructor.
     *
     * @param string          $notFoundPage
     * @param Storage         $storage
     * @param TemplateChooser $templateChooser
     * @param Render          $render
     */
    public function __construct($notFoundPage, Storage $storage, TemplateChooser $templateChooser, Render $render)
    {
        $this->notFoundPage = $notFoundPage;
        $this->storage = $storage;
        $this->templateChooser = $templateChooser;
        $this->render = $render;
    }

    /**
     * Render the not found page if on frontend and http exception
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof HttpExceptionInterface || Zone::isBackend($event->getRequest())) {
            return;
        }

        $content = $this->storage->getContent($this->notFoundPage, ['returnsingle' => true]);

        if (!$content instanceof Content || empty($content->id)) {
            return;
        }

        $template = $this->templateChooser->record($content);
        $response = $this->render->render($template, $content->getTemplateContext());
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 512],
        ];
    }
}
