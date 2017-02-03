<?php

namespace Bolt\EventListener;

use Bolt\Helpers\Str;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts HTTP exceptions to JSON responses.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ExceptionToJsonListener implements EventSubscriberInterface
{
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
        return strpos($request->headers->get('Content-Type'), 'application/json') === 0;
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
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $errorType = $this->getErrorType($exception);

        $response = new JsonResponse(
            [
                'success'   => false,
                'errorType' => $errorType,
                'code'      => $statusCode,
                'message'   => $exception->getMessage(),
            ]
        );
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

        $errorType = Str::replaceLast('Exception', '', $errorType);

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
            KernelEvents::EXCEPTION => ['onException', -7],
        ];
    }
}
