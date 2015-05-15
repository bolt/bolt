<?php
namespace Bolt\Routing\Listener;

use Bolt\Content;
use Bolt\Controller\Zone;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * HTTP kernel exception routing listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class KernelExceptionListener implements EventSubscriberInterface
{
    /** @var \Silex\Application $app */
    protected $app;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle errors thrown in the application. Set up Whoops!, if set in the
     * config.yml file.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        // Log the error message
        $message = $exception->getMessage();
        $this->app['logger.system']->critical($message, array('event' => 'exception', 'exception' => $exception));

        $trace = $exception->getTrace();
        foreach ($trace as $key => $value) {
            if (!empty($value['file']) && strpos($value['file'], '/vendor/') > 0) {
                unset($trace[$key]['args']);
            }

            // Don't display the full path.
            if (isset($trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace($this->app['resources']->getPath('root'), '[root]', $trace[$key]['file']);
            }
        }

        if (($exception instanceof HttpException) && Zone::isFrontend($request)) {
            $content = $this->app['storage']->getContent($this->app['config']->get('general/notfound'), array('returnsingle' => true));

            // Then, select which template to use, based on our 'cascading templates rules'
            if ($content instanceof Content && !empty($content->id)) {
                $template = $this->app['templatechooser']->record($content);

                $response = $this->app['render']->render($template, $content->getTemplateContext());
                $event->setResponse($response);
            }

            $message = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

        $context = array(
            'class'   => get_class($exception),
            'message' => $message,
            'code'    => $exception->getCode(),
            'trace'   => $trace,
        );

        // Note: This uses the template from app/theme_defaults. Not app/view/twig.
        $response = $this->app['render']->render('error.twig', array('context' => $context));
        $event->setResponse($response);
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', -8),
        );
    }
}
