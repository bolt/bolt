<?php

namespace Bolt;

use Bolt\Exception\FileNotStackableException;
use Bolt\Filesystem\AggregateFilesystemInterface;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\FilesystemInterface;
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

    /** @var FilesystemInterface */
    private $filesystem;
    /** @var Users */
    private $users;
    /** @var SessionInterface */
    private $session;
    /** @var string[] */
    private $acceptedFileTypes;

    /** @var FileInterface[] */
    private $files;
    /** @var boolean */
    private $initialized;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param Users               $users
     * @param SessionInterface    $session
     * @param string[]            $acceptedFileTypes
     */
    public function __construct(FilesystemInterface $filesystem, Users $users, SessionInterface $session, $acceptedFileTypes)
    {
        $this->filesystem = $filesystem;
        $this->users = $users;
        $this->session = $session;
        $this->acceptedFileTypes = $acceptedFileTypes;
    }

    /**
     * Add a file to the stack.
     *
     * @param FileInterface|string $filename
     *
     * @throws FileNotFoundException If filename cannot be matched to filesystem.
     * @throws FileNotStackableException If file is not stackable.
     *
     * @return FileInterface Returns the file added.
     */
    public function add($filename)
    {
        $this->initialize();

        $file = $this->getFile($filename);

        if (!$this->isStackable($file)) {
            throw new FileNotStackableException($file);
        }

        array_unshift($this->files, $file);

        $this->files = array_slice($this->files, 0, self::MAX_ITEMS);

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
            $file = $this->getFile($filename);
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
            $file = $this->getFile($filename);
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
     * @return boolean
     */
    public function isStackable($filename)
    {
        try {
            $file = $this->getFile($filename);
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
     * Returns whether the stack is full, meaning files
     * will be shifted off when new ones are added.
     *
     * @return bool
     */
    public function isAtCapacity()
    {
        return count($this) >= static::MAX_ITEMS;
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
        $files = array_filter(array_map(function($path) {
            try {
                return $this->getFile($path);
            } catch (FileNotFoundException $e) {
                // Guess it doesn't exist anymore or we can't find it, remove from list.
                return null;
            }
        }, $paths));

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

    /**
     * Gets the file object for the path given. Paths with the mount point included are
     * preferred, but are not required for BC. If the mount point is not included a list
     * of filesystems are checked and chosen if the file exists in that filesystem.
     *
     * @param FileInterface|string $path
     *
     * @return FileInterface
     */
    private function getFile($path)
    {
        if ($path instanceof FileInterface) {
            return $path;
        }

        if (!$this->filesystem instanceof AggregateFilesystemInterface || $this->containsMountPoint($path)) {
            $file = $this->filesystem->getFile($path);
            if (!$file->exists()) {
                throw new FileNotFoundException($path);
            }

            return $file;
        }

        // Trim "files/" from front of path for BC.
        if (strpos($path, 'files/') === 0) {
            $path = substr($path, 6);
        }

        foreach (['files', 'themes', 'theme'] as $mountPoint) {
            if (!$this->filesystem->hasFilesystem($mountPoint)) {
                continue;
            }

            $file = $this->filesystem->getFile("$mountPoint://$path");
            if ($file->exists()) {
                return $file;
            }
        }

        throw new FileNotFoundException($path);
    }

    /**
     * Change if a path contains a mount point.
     *
     * Ex: files://foo.jpg
     *
     * @param string $path
     *
     * @return bool
     */
    private function containsMountPoint($path)
    {
        return (bool) preg_match('#^.+\:\/\/.*#', $path);
    }
}
