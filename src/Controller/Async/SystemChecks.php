<?php
namespace Bolt\Controller\Async;

use Bolt\Configuration;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Async controller for system testing async routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SystemChecks extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/check/email', 'email')
            ->bind('email');
    }

    /**
     * Send an e-mail ping test.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function email(Request $request)
    {
        $options = [
            'user' => $this->app['users']->getCurrentUser(),
            'host' => $request->getHost(),
            'ip'   => $request->getClientIp(),
        ];

        $results = $this->getCheck('EmailSetup')
            ->setOptions($options)
            ->runCheck()
        ;

        foreach ($results as $result) {
            if (!$result->isPass()) {
                return $this->json($results, Response::HTTP_I_AM_A_TEAPOT);
            }
        }

        return $this->json($results);
    }

    /**
     * Getter for the check class.
     *
     * @return \Bolt\Configuration\Check\ConfigurationCheckInterface
     */
    protected function getCheck($check)
    {
        $class = "Bolt\\Configuration\\Check\\$check";

        return new $class($this->app);
    }
}
