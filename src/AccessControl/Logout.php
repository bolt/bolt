<?php
namespace Bolt\AccessControl;

use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Repository\AuthtokenRepository;
use Bolt\Translation\Translator as Trans;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Logout authentication handling.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Logout
{
    /** @var \Bolt\Storage\Repository\AuthtokenRepository */
    protected $repositoryAuthtoken;
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    protected $session;
    /** @var \Bolt\Logger\FlashLoggerInterface */
    protected $flashLogger;

    /**
     * Constructor.
     *
     * @param AuthtokenRepository  $repositoryAuthtoken
     * @param SessionInterface     $session
     * @param FlashLoggerInterface $flashLogger
     */
    public function __construct(AuthtokenRepository $repositoryAuthtoken, SessionInterface $session, FlashLoggerInterface $flashLogger)
    {
        $this->repositoryAuthtoken = $repositoryAuthtoken;
        $this->session = $session;
        $this->flashLogger = $flashLogger;
    }

    /**
     * Log out the currently logged in user.
     *
     * @return boolean
     */
    public function logout()
    {
        $this->flashLogger->info(Trans::__('You have been logged out.'));

        // Remove all auth tokens when logging off a user
        if ($sessionAuth = $this->session->get('authentication')) {
            $this->repositoryAuthtoken->deleteTokens($sessionAuth->getUser()->getUsername());
        }

        $this->session->remove('authentication');
        $this->session->migrate(true);

        return false;
    }
}
