<?php
namespace Bolt\Session;

use Bolt\Session\Generator\GeneratorInterface;
use Bolt\Session\Handler\LazyWriteHandlerInterface;
use Bolt\Session\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface as HandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * TODO:
 *  - Do return values from handler need to be checked? Throw exceptions? Log?
 *
 * Symfony has a great abstraction layer for Sessions...so why are we still restricting ourselves with core limitations.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionStorage implements SessionStorageInterface
{
    /** @var string */
    protected $id = '';

    /** @var string */
    protected $name = 'PHPSESSID';

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
     *
     * @var array
     */
    protected $data = [];

    /**
     * MD5 hash of initial data if the "lazy_write" option is enabled.
     *
     * @var string
     */
    protected $dataHash;

    /** @var HandlerInterface */
    protected $handler;

    /** @var GeneratorInterface */
    protected $generator;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var OptionsBag */
    protected $options;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * Constructor.
     *
     * @param OptionsBag          $options
     * @param HandlerInterface    $handler
     * @param GeneratorInterface  $generator
     * @param SerializerInterface $serializer
     * @param LoggerInterface     $logger
     * @param MetadataBag         $metadataBag
     */
    public function __construct(
        OptionsBag $options,
        HandlerInterface $handler,
        GeneratorInterface $generator,
        SerializerInterface $serializer,
        LoggerInterface $logger = null,
        MetadataBag $metadataBag = null
    ) {
        $this->options = $options;
        $this->name = $options->get('name');

        $this->setHandler($handler);
        $this->generator = $generator;
        $this->serializer = $serializer;
        $this->logger = $logger ?: new NullLogger();
        $this->setMetadataBag($metadataBag);
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if ($this->started) {
            return true;
        }

        $this->initializeSession();

        $this->initializeBags();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->started) {
            $this->start();
        }

        if ($lifetime !== null) {
            $this->options['cookie_lifetime'] = $lifetime;
        }

        if ($destroy) {
            $this->metadataBag->stampNew($lifetime);
            $this->handler->destroy($this->id);
        } else {
            $this->write();
        }
        $this->handler->close();

        $this->id = $this->generator->generateId();

        $this->handler->open(null, $this->name);
        // read is required to make new session data at this point
        $this->handler->read($this->id);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        if ($this->started) {
            throw new \LogicException('Cannot set session ID after the session has started.');
        }

        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->validateName($name);
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        if (!$this->started || $this->closed) {
            throw new \RuntimeException('Trying to save a session that was not started yet or was already closed');
        }

        $this->write();

        $this->handler->close();

        $this->closed = true;
        $this->started = false;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag)
    {
        $this->bags[$bag->getName()] = $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getBag($name)
    {
        if (!isset($this->bags[$name])) {
            throw new \InvalidArgumentException(sprintf('The SessionBagInterface %s is not registered.', $name));
        }

        if (!$this->started) {
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
     * {@inheritdoc}
     */
    public function getMetadataBag()
    {
        return $this->metadataBag;
    }

    /**
     * Add options
     *
     * @param OptionsBag $options
     */
    public function setOptions(OptionsBag $options)
    {
        $this->options->add($options->all());
    }

    /**
     * Return session options.
     *
     * @return OptionsBag
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the handler
     *
     * @param HandlerInterface $handler
     */
    public function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Get the handler
     *
     * @return HandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->started;
    }

    protected function initializeSession()
    {
        $this->data = [];
        $this->dataHash = null;

        $this->handler->open(null, $this->name);

        if (empty($this->id)) {
            $this->id = $this->generator->generateId();
        }

        $this->collectGarbage(); // Must be done before read

        $data = $this->handler->read($this->id);
        if (!$data) { // Intentionally catch falsely values
            return;
        }

        if ($this->options->getBoolean('lazy_write', false)) {
            $this->dataHash = md5($data);
        }

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

    protected function collectGarbage()
    {
        $probability = $this->options->getInt('gc_probability');
        if ($probability < 0) {
            return;
        }
        $divisor = $this->options->getInt('gc_divisor');

        $rand = mt_rand(0, $divisor);
        if ($rand < $probability) {
            $this->handler->gc($this->options->getInt('gc_maxlifetime'));
        }
    }

    /**
     * Validate session name which needs to be a valid cookie name.
     *
     * Regex pulled from {@see Symfony\Component\HttpFoundation\Cookie}
     *
     * @param string $name
     */
    protected function validateName($name)
    {
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new \InvalidArgumentException(sprintf('The session name "%s" contains invalid characters.', $name));
        }
    }

    protected function write()
    {
        $data = $this->serializer->serialize($this->data);

        if ($this->options->getBoolean('lazy_write', false) &&
            $this->handler instanceof LazyWriteHandlerInterface &&
            md5($data) === $this->dataHash
        ) {
            $this->handler->updateTimestamp($this->id, $data);
        } else {
            $this->handler->write($this->id, $data);
        }
    }
}
