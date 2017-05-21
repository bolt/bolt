<?php

namespace Bolt\EventListener;

use Swift_Events_SendEvent as SendEvent;
use Swift_Events_SendListener as SendListener;
use Swift_Message as Message;
use Swift_Signers_SMimeSigner as SMimeSigner;

/**
 * Swiftmailer event listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SwiftmailerListener implements SendListener
{
    /** @var SMimeSigner */
    private $signer;

    /**
     * Constructor.
     *
     * @param SMimeSigner $signer
     */
    public function __construct(SMimeSigner $signer)
    {
        $this->signer = $signer;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSendPerformed(SendEvent $evt)
    {
        /** @var Message $message */
        $message = $evt->getMessage();
        if ($this->signer->getSignPrivateKey()) {
            $message->attachSigner($this->signer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendPerformed(SendEvent $evt)
    {
    }
}
