<?php

namespace Bolt;

use Bolt\Exception\FileNotStackableException;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\FileInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Stack for remembering the most recently used files for the current user.
 *
 * @author Bob den Otter, bob@twokings.nl
 * @author Carson Full <carsonfull@gmail.com>
 */
class Stack implements \Countable, \IteratorAggregate
{
    const MAX_ITEMS = 7;

    /** @var Filesystem\Matcher */
    private $matcher;
    /** @var Users */
    private $users;
    /** @var SessionInterface */
    private $session;
    /** @var string[] */
    private $acceptedFileTypes;

    /** @var FileInterface[] */
    private $files;
    /** @var bool */
    private $initialized;

    /**
     * Constructor.
     *
     * @param Filesystem\Matcher $matcher
     * @param Users              $users
     * @param SessionInterface   $session
     * @param string[]           $acceptedFileTypes
     */
    public function __construct(Filesystem\Matcher $matcher, Users $users, SessionInterface $session, $acceptedFileTypes)
    {
        $this->matcher = $matcher;
        $this->users = $users;
        $this->session = $session;
        $this->acceptedFileTypes = $acceptedFileTypes;
    }

    /**
     * Add a file to the stack.
     *
     * @param FileInterface|string $filename the file to add
     * @param FileInterface|null   $removed  returns the removed file, if one was removed
     *
     * @throws FileNotStackableException if file is not stackable
     *
     * @return FileInterface if filename cannot be matched to filesystem
     */
    public function add($filename, FileInterface &$removed = null)
    {
        $this->initialize();

        $file = $this->matcher->getFile($filename);

        if (!$this->isStackable($file)) {
            throw new FileNotStackableException($file);
        }

        array_unshift($this->files, $file);

        if (count($this) > static::MAX_ITEMS) {
            $removed = array_pop($this->files);
        }

        $this->persist();

        return $file;
    }

    /**
     * Delete a file from the stack.
     *
     * @param FileInterface|string $filename
     */
    public function delete($filename)
    {
        $this->initialize();

        try {
            $file = $this->matcher->getFile($filename);
        } catch (FileNotFoundException $e) {
            return;
        }

        foreach ($this->files as $key => $item) {
            if ($item->getFullPath() === $file->getFullPath()) {
                unset($this->files[$key]);
                $this->files = array_values($this->files); // normalize indexes
                $this->persist();
                break;
            }
        }
    }

    /**
     * Check if a given file is present on the stack.
     *
     * @param FileInterface|string $filename
     *
     * @return bool
     */
    public function contains($filename)
    {
        $this->initialize();

        try {
            $file = $this->matcher->getFile($filename);
        } catch (FileNotFoundException $e) {
            return false;
        }

        foreach ($this->files as $item) {
            if ($item->getFullPath() === $file->getFullPath()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a given file can be added to the stack.
     *
     * Requirements:
     * - File's extension is accepted
     * - File can be matched to filesystem
     * - File is not currently on the stack
     *
     * @param FileInterface|string $filename
     *
     * @return bool
     */
    public function isStackable($filename)
    {
        try {
            $file = $this->matcher->getFile($filename);
        } catch (FileNotFoundException $e) {
            return false;
        }

        if (!in_array($file->getExtension(), $this->acceptedFileTypes)) {
            return false;
        }

        return !$this->contains($file);
    }

    /**
     * Returns the list of files in the stack, filtered by type (if given).
     *
     * @param string[] $types Filter files by type. Valid types: "image", "document", "other"
     *
     * @return FileInterface[]
     */
    public function getList(array $types = [])
    {
        $this->initialize();

        if (empty($types)) {
            return $this->files;
        }

        $images = in_array('image', $types);
        $docs = in_array('document', $types);
        $other = in_array('other', $types);
        $files = array_filter($this->files, function (FileInterface $file) use ($images, $docs, $other) {
            switch ($file->getType()) {
                case 'image':
                    return $images;
                case 'document':
                    return $docs;
                default:
                    return $other;
            }
        });
        $files = array_values($files); // normalize indexes

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getList());
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->initialize();

        return count($this->files);
    }

    /**
     * Initialize file list for current user, either from session or database.
     */
    private function initialize()
    {
        if ($this->initialized) {
            return;
        }

        if ($this->session->isStarted() && $this->session->get('stack') !== null) {
            $paths = $this->session->get('stack');
            $this->files = $this->hydrateList($paths);
        } else {
            $paths = $this->users->getCurrentUser()['stack'];
            $this->files = $this->hydrateList($paths);
            $this->session->set('stack', $this->persistableList());
        }

        $this->initialized = true;
    }

    /**
     * Persist the contents of the current stack to the session, as well as the database.
     */
    private function persist()
    {
        $items = $this->persistableList();

        $this->session->set('stack', $items);

        $user = $this->users->getCurrentUser();
        $user['stack'] = $items;
        $this->users->saveUser($user);
    }

    /**
     * Converts a list of paths to file objects.
     *
     * @param string[] $paths
     *
     * @return FileInterface[]
     */
    private function hydrateList($paths)
    {
        $files = array_filter(array_map(function ($path) {
            try {
                return $this->matcher->getFile($path);
            } catch (FileNotFoundException $e) {
                // Guess it doesn't exist anymore or we can't find it, remove from list.
                return null;
            }
        }, (array) $paths));

        $files = array_slice($files, 0, self::MAX_ITEMS);

        return $files;
    }

    /**
     * Returns the list of files as full paths.
     *
     * @return string[]
     */
    private function persistableList()
    {
        return array_map(function (FileInterface $file) {
            return $file->getFullPath();
        }, $this->files);
    }
}
