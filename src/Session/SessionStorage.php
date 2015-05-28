<?php
namespace Bolt\Session;

use Bolt\Session\Serializer\SerializerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * TODO:
 *  - Do return values from handler need to be checked? Throw exceptions? Log?
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
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

    /**
     * Actual session data.
     * bags <-> data <-> handler
     * @var array
     */
    protected $data = [];

    /** @var SessionHandlerInterface */
    protected $handler;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var ArrayCollection */
    protected $options;

    /**
     * Constructor.
     *
     * @param SessionHandlerInterface $handler
     * @param SerializerInterface     $serializer
     * @param MetadataBag             $metadataBag
     */
    public function __construct(SessionHandlerInterface $handler, SerializerInterface $serializer, MetadataBag $metadataBag = null)
    {
        $this->setHandler($handler);
        $this->serializer = $serializer;
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

        //TODO try to get ID from cookie

        if (empty($this->id)) {
            $this->id = $this->generateId();
        }

        $this->initializeSession();

        /**
         * TODO: Determine if garbage should be collected
         * @see http://php.net/manual/en/sessionhandlerinterface.gc.php
         */
        $gc = false;
        $maxLifeTime = 0;
        if ($gc) {
            $this->handler->gc($maxLifeTime);
        }

        $this->initializeBags();

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

        if ($lifetime !== null) {
            $this->options['lifetime'] = $lifetime;
        }

        if ($destroy) {
            $this->metadataBag->stampNew($lifetime);
            $this->handler->destroy($this->id);
        }

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

        $data = $this->serializer->serialize($this->data);
        $this->handler->write($this->id, $data);
        $this->handler->close();

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

        $this->data = [];

        // reconnect the bags to the session
        $this->initializeBags();
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

        if (!$this->started) {
            $this->initializeBags();
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

    public function setHandler($handler)
    {
        $this->handler = $handler;
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

    protected function initializeSession()
    {
        $this->handler->open(null, $this->id);

        $data = $this->handler->read($this->id);
        try {
            $this->data = $this->serializer->unserialize($data);
        } catch (\Exception $e) {
            // Destroy data upon unserialization error
            $this->handler->destroy($this->id);
        }
    }

    protected function initializeBags()
    {
        /** @var SessionBagInterface[] $bags */
        $bags = array_merge($this->bags, [$this->metadataBag]);
        foreach ($bags as $bag) {
            $key = $bag->getStorageKey();
            $this->data[$key] = isset($this->data[$key]) ? $this->data[$key] : [];
            $bag->initialize($this->data[$key]);
        }

        $this->started = true;
        $this->closed = false;
    }
}
