<?php
namespace Bolt\Controller\Async;

use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Async controller for system testing async routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SystemTests extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/email/{type}', 'emailNotification')
            ->assert('type', '.*')
            ->bind('emailNotification');
    }

    /**
     * Send an e-mail ping test.
     *
     * @param Request $request
     * @param string  $type
     *
     * @return Response
     */
    public function emailNotification(Request $request, $type)
    {
        if ($type !== 'test') {
            return new JsonResponse(['Invalid notification type.'], Response::HTTP_NO_CONTENT);
        }

        $user = $this->getUsers()->getCurrentUser();

        // Create an email
        $mailhtml = $this->render(
            'email/pingtest.twig',
            array(
                'sitename' => $this->getOption('general/sitename'),
                'user'     => $user['displayname'],
                'ip'       => $request->getClientIp()
            )
        )->getContent();

        $senderMail = $this->getOption('general/mailoptions/senderMail', 'bolt@' . $request->getHost());
        $senderName = $this->getOption('general/mailoptions/senderName', $this->getOption('general/sitename'));

        $message = $this->app['mailer']
            ->createMessage('message')
            ->setSubject('Test email from ' . $this->getOption('general/sitename'))
            ->setFrom(array($senderMail  => $senderName))
            ->setTo(array($user['email'] => $user['displayname']))
            ->setBody(strip_tags($mailhtml))
            ->addPart($mailhtml, 'text/html');

        $this->app['mailer']->send($message);

        return new JsonResponse(['Done'], Response::HTTP_OK);
    }
}
