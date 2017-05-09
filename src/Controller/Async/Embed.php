<?php

namespace Bolt\Controller\Async;

use Bolt\Exception\EmbedResolverException;
use Embed\Exceptions\InvalidUrlException;
use GuzzleHttp\Psr7;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Async controller for embed routes.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Embed extends AsyncBase
{
    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->post('/embed', 'embed')
            ->bind('embedRequestEndpoint')
            ->before(function (Request $request) {
                if (!$this->isCsrfTokenValid($request->request->get('bolt_csrf_token'))) {
                    return new JsonResponse(['error' => ['message' => 'Invalid CSRF token']], Response::HTTP_FORBIDDEN);
                }
                if ($request->request->has('provider')) {
                    return null;
                }
                if ($request->request->has('url')) {
                    return null;
                }

                return new JsonResponse(['error' => ['message' => 'Invalid POST parameters']], Response::HTTP_FORBIDDEN);
            })
        ;
    }

    /**
     * @param Request $request
     *
     * @throws InvalidUrlException
     *
     * @return JsonResponse
     */
    public function embed(Request $request)
    {
        $provider = $request->request->get('provider');
        $url = $request->request->get('url');
        $url = new Psr7\Uri($url);
        /** @var \Bolt\Embed\Resolver $resolver */
        $resolver = $this->app['embed'];

        try {
            $data = $resolver->embed($url, $provider);
        } catch (EmbedResolverException $e) {
            $response = ['error' => ['message' => $e->getMessage()]];

            return new JsonResponse($response, Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse($data);
    }
}
