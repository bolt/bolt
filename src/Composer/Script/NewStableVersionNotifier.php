<?php

namespace Bolt\Composer\Script;

use Bolt\Nut\Output\NutStyleInterface;
use Bolt\Nut\Style\NutStyle;
use Composer\Script\Event;

class NewStableVersionNotifier
{
    /** @var NutStyleInterface */
    private $io;

    /**
     * Constructor.
     *
     * @param NutStyleInterface $io
     */
    public function __construct(NutStyleInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Create from a Composer event object.
     *
     * @param Event $event
     *
     * @return NewStableVersionNotifier
     */
    public static function fromEvent(Event $event)
    {
        $io = NutStyle::fromComposer($event->getIO());

        return new static($io);
    }


    /**
     * Go!
     */
    public function run()
    {
        $message = 'You are using Bolt v3. There is a new major Bolt release available.';
        $message .= "\nTo learn more go to https://docs.bolt.cm/";

        $this->io->warning($message);
    }
}
