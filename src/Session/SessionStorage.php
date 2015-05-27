<?php
namespace Bolt\Session;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class SessionStorage implements SessionStorageInterface, CookieGeneratableInterface
{
    /** @var string */
    protected $id = '';

    /** @var string */
    protected $name;

    /** @var boolean */
    protected $started = false;

    /** @var boolean */
    protected $closed = false;

    /** @var SessionBagInterface[] */
    protected $bags;

    /** @var MetadataBag */
    protected $metadataBag;

    /** @var ? */
    protected $saveHandler;

    /** @var ArrayCollection */
    protected $options;

    /**
     * Constructor.
     *
     * @param             $handler
     * @param MetadataBag $metadataBag
     */
    public function __construct($handler, MetadataBag $metadataBag = null)
    {
        $this->setSaveHandler($handler);
        $this->setMetadataBag($metadataBag);
        //TODO set defaults from ini
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        if ($this->started) {
            return true;
        }

        if (empty($this->id)) {
            $this->id = $this->generateId();
        }

        $this->loadSession();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->started) {
            $this->start();
        }

        $this->metadataBag->stampNew($lifetime);
        $this->id = $this->generateId();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        if ($this->started) {
            throw new \LogicException('Cannot set session ID after the session has started.');
        }

        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if (!$this->started || $this->closed) {
            throw new \RuntimeException('Trying to save a session that was not started yet or was already closed');
        }

        // TODO invoke handler

        $this->closed = true;
        $this->started = false;
    }

    /**
     * @inheritdoc
     */
    public function generateCookie()
    {
        $lifetime = 0 === $this->options['lifetime'] ? 0 : time() + $this->options['lifetime'];
        return new Cookie(
            $this->name,
            $this->id,
            $lifetime,
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'],
            $this->options['httponly']
        );
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        foreach ($this->bags as $bag) {
            $bag->clear();
        }

        //TODO invoke handler

        // reconnect the bags to the session
        $this->loadSession();
    }

    /**
     * @inheritdoc
     */
    public function registerBag(SessionBagInterface $bag)
    {
        $this->bags[$bag->getName()] = $bag;
    }

    /**
     * @inheritdoc
     */
    public function getBag($name)
    {
        if (!isset($this->bags[$name])) {
            throw new \InvalidArgumentException(sprintf('The SessionBagInterface %s is not registered.', $name));
        }

        if ($this->saveHandler->isActive() && !$this->started) {
            $this->loadSession();
        } elseif (!$this->started) {
            $this->start();
        }

        return $this->bags[$name];
    }

    /**
     * Sets the MetdataBag.
     *
     * @param MetadataBag $bag
     */
    public function setMetadataBag(MetadataBag $bag = null)
    {
        $this->metadataBag = $bag ?: new MetadataBag();
    }

    /**
     * @inheritdoc
     */
    public function getMetadataBag()
    {
        return $this->metadataBag;
    }

    public function setOptions(array $options)
    {
        $this->options = new ArrayCollection($options);
    }

    public function setSaveHandler($handler)
    {
        $this->saveHandler = $handler;
    }

    /**
     * @inheritdoc
     */
    public function isStarted()
    {
        return $this->started;
    }

    protected function generateId()
    {
        return ''; //TODO
    }

    protected function loadSession()
    {
        /** @var SessionBagInterface[] $bags */
        $bags = array_merge($this->bags, array($this->metadataBag));

        foreach ($bags as $bag) {
            //TODO invoke handler
        }

        $this->started = true;
        $this->closed = false;
    }
}
