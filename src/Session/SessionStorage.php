<?php
namespace Bolt\Session;

use Bolt\Session\Generator\GeneratorInterface;
use Bolt\Session\Serializer\SerializerInterface;
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
     *
     * @var array
     */
    protected $data = [];

    /** @var HandlerInterface */
    protected $handler;

    /** @var GeneratorInterface */
    protected $generator;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var OptionsBag */
    protected $options;

    /**
     * Constructor.
     *
     * @param OptionsBag          $options
     * @param HandlerInterface    $handler
     * @param GeneratorInterface  $generator
     * @param SerializerInterface $serializer
     * @param MetadataBag         $metadataBag
     */
    public function __construct(
        OptionsBag $options,
        HandlerInterface $handler,
        GeneratorInterface $generator,
        SerializerInterface $serializer,
        MetadataBag $metadataBag = null
    ) {
        $this->options = $options;
        $this->name = $options->get('name');

        $this->setHandler($handler);
        $this->generator = $generator;
        $this->serializer = $serializer;
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

        if (empty($this->id)) {
            $this->id = $this->generator->generateId();
        }

        $this->initializeSession();

        $this->collectGarbage();

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
        }

        $this->id = $this->generator->generateId();

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

        $data = $this->serializer->serialize($this->data);
        $this->handler->write($this->id, $data);
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
}
