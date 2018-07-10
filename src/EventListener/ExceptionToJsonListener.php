<?php

namespace Bolt\EventListener;

use Bolt\Common\Str;
use Bolt\Configuration\PathResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\PathUtil\Path;

/**
 * Converts HTTP exceptions to JSON responses.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ExceptionToJsonListener implements EventSubscriberInterface
{
    /** @var PathResolver */
    private $pathResolver;

    /**
     * Constructor.
     *
     * @param PathResolver $pathResolver
     */
    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    /**
     * Listen for exceptions and convert them to JSON responses if necessary.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onException(GetResponseForExceptionEvent $event)
    {
        if (!$this->isApplicable($event->getRequest())) {
            return;
        }

        $response = $this->convert($event->getException());

        $event->setResponse($response);
    }

    /**
     * Determine if the request is applicable to receive a JSON response.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isApplicable(Request $request)
    {
        return $request->isXmlHttpRequest() || strpos($request->headers->get('Content-Type'), 'application/json') === 0;
    }

    /**
     * Convert the exception to a response.
     *
     * @param \Exception $exception
     *
     * @return JsonResponse
     */
    protected function convert(\Exception $exception)
    {
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        $file = Path::makeRelative($exception->getFile(), $this->pathResolver->resolve('root'));

        $response = new JsonResponse([
            'success'   => false,
            'code'      => $statusCode,
            'error'     => [
                'type'    => $this->getErrorType($exception),
                'file'    => $file,
                'line'    => $exception->getLine(),
                'message' => $exception->getMessage(),
            ],
        ]);
        $response->setStatusCode($statusCode);

        return $response;
    }

    /**
     * Convert the exception to an error type.
     *
     * @param \Exception $exception
     *
     * @return string
     */
    protected function getErrorType(\Exception $exception)
    {
        $errorType = get_class($exception);

        $pos = strrpos($errorType, '\\');
        if ($pos !== false) {
            $errorType = substr($errorType, $pos);
        }

        $errorType = Str::replaceLast($errorType, 'Exception', '');

        if (!$errorType) {
            $errorType = 'Unknown';
        }

        return $errorType;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onException', -5],
        ];
    }
}
