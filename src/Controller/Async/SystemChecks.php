<?php
namespace Bolt\Controller\Async;

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
        $c->get('/check/directories', 'directoriesCheck')
            ->bind('directories');
        $c->get('/check/email', 'emailCheck')
            ->bind('email');
        $c->get('/check/extensions', 'extensionsCheck')
            ->bind('extensions');
    }

    /**
     * Check the needed directories are writeable.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function directoriesCheck(Request $request)
    {
        $options = [];

        $results = $this->getCheck('DirectoryAccess')
            ->setOptions($options)
            ->runCheck()
        ;

        return $this->json($results);
    }

    /**
     * Send an e-mail ping test.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function emailCheck(Request $request)
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

        return $this->json($results);
    }

    /**
     * Check the installation of PHP extensions.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function extensionsCheck(Request $request)
    {
        $options = [];

        $results = $this->getCheck('PhpExtensions')
            ->setOptions($options)
            ->runCheck()
        ;

        return $this->json($results);
    }

    /**
     * Getter for the check class.
     *
     * @param string $check
     *
     * @throws \InvalidArgumentException
     *
     * @return \Bolt\Configuration\Check\ConfigurationCheckInterface
     */
    protected function getCheck($check)
    {
        $class = "Bolt\\Configuration\\Check\\$check";

        if (!class_exists($class)) {
            throw new \RuntimeException("Requested check class '$class' doesn't exists");
        }

        return new $class($this->app);
    }
}
