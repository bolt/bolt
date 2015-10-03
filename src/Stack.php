<?php

namespace Bolt;

use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex;
use utilphp\util;

/**
 * Simple stack implementation for remembering "10 last items".
 *
 * Each user (by design) has their own stack. No sharesies!
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Stack
{
    const MAX_ITEMS = 10;

    /** @var boolean */
    protected $initalized;
    /** @var array */
    private $items;
    /** @var array */
    private $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
    /** @var array */
    private $documentTypes = ['doc', 'docx', 'txt', 'md', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'];
    /** @var \Silex\Application */
    private $app;

    /**
     * Constructor.
     *
     * @param Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    public function initialize()
    {
        if ($this->initalized) {
            return;
        }

        if ($this->app['session']->isStarted() && $this->app['session']->get('stack') !== null) {
            $this->items = $this->app['session']->get('stack');
        } else {
            $currentuser = $this->app['users']->getCurrentUser();
            $this->items = $currentuser['stack'];
            $this->app['session']->set('stack', $currentuser['stack']);
        }

        // intersect the allowed types with the types set
        $confTypes = $this->app['config']->get('general/accept_file_types', []);
        $this->imageTypes = array_intersect($this->imageTypes, $confTypes);
        $this->documentTypes = array_intersect($this->documentTypes, $confTypes);

        $this->initalized = true;
    }

    /**
     * Add a certain item to the stack.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function add($filename)
    {
        $this->initialize();

        // If the item is already on the stack, delete it, so it can be added to the front.
        if (in_array($filename, $this->items)) {
            $this->delete($filename);
        }

        array_unshift($this->items, $filename);
        $this->persist();

        return true;
    }

    /**
     * Delete an item from the stack.
     *
     * @param string $filename
     */
    public function delete($filename)
    {
        $this->initialize();

        foreach ($this->items as $key => $item) {
            if ($item == $filename) {
                unset($this->items[$key]);
                $this->persist();
            }
        }
    }

    /**
     * Check if a given filename is present on the stack.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function isOnStack($filename)
    {
        $this->initialize();

        // We don't always need the "files/" part in the filename.
        $shortname = str_replace('files/', '', $filename);

        foreach ($this->items as $item) {
            if ($item == $filename || $item == $shortname) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a given filename is stackable.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function isStackable($filename)
    {
        $ext = Lib::getExtension($filename);

        return in_array($ext, $this->getFileTypes());
    }

    /**
     * Return a list with the current stacked items. Add some relevant info to each item,
     * and also check if the item is present and readable.
     *
     * @param integer $count
     * @param string  $typefilter
     *
     * @return array
     */
    public function listitems($count = 100, $typefilter = '')
    {
        $this->initialize();

        // Make sure typefilter is an array, if passed something like "image, document"
        if (!empty($typefilter)) {
            $typefilter = array_map('trim', explode(',', $typefilter));
        }

        // Our basepaths for all files that can be on the stack: 'files' and 'theme'.
        $filespath = $this->app['resources']->getPath('filespath');
        $themepath = $this->app['resources']->getPath('themebasepath');

        $items = $this->items;
        $list = [];

        foreach ($items as $item) {
            $extension = strtolower(Lib::getExtension($item));
            if (in_array($extension, $this->imageTypes)) {
                $type = 'image';
            } elseif (in_array($extension, $this->documentTypes)) {
                $type = 'document';
            } else {
                $type = 'other';
            }

            // Skip this one, if it doesn't match the type.
            if (!empty($typefilter) && (!in_array($type, $typefilter))) {
                continue;
            }

            // Figure out the full path, based on the two possible locations.
            $fullpath = '';
            if (is_readable(str_replace('files/files/', 'files/', $filespath . '/' . $item))) {
                $fullpath = str_replace('files/files/', 'files/', $filespath . '/' . $item);
            } elseif (is_readable($themepath . '/' . $item)) {
                $fullpath = $themepath . '/' . $item;
            }

            // No dice! skip this one.
            if (empty($fullpath)) {
                continue;
            }

            $thisitem = [
                'basename'    => basename($item),
                'extension'   => $extension,
                'filepath'    => str_replace('files/', '', $item),
                'type'        => $type,
                'writable'    => is_writable($fullpath),
                'readable'    => is_readable($fullpath),
                'filesize'    => Lib::formatFilesize(filesize($fullpath)),
                'modified'    => date('Y/m/d H:i:s', filemtime($fullpath)),
                'permissions' => util::full_permissions($fullpath)
            ];

            $thisitem['info'] = sprintf(
                '%s: <code>%s</code><br>%s: %s<br>%s: %s<br>%s: <code>%s</code>',
                Trans::__('Path'),
                $thisitem['filepath'],
                Trans::__('Filesize'),
                $thisitem['filesize'],
                Trans::__('Modified'),
                $thisitem['modified'],
                Trans::__('Permissions'),
                $thisitem['permissions']
            );

            if ($type == 'image') {
                $size = getimagesize($fullpath);
                $thisitem['imagesize'] = sprintf('%s × %s', $size[0], $size[1]);
                $thisitem['info'] .= sprintf('<br>%s: %s × %s px', Trans::__('Size'), $size[0], $size[1]);
            }

            //add it to our list.
            $list[] = $thisitem;
        }

        $list = array_slice($list, 0, $count);

        return $list;
    }

    /**
     * Persist the contents of the current stack to the session, as well as the database.
     */
    public function persist()
    {
        $this->initialize();

        $this->items = array_slice($this->items, 0, self::MAX_ITEMS);

        $this->app['session']->set('stack', $this->items);

        $currentuser = $this->app['users']->getCurrentUser();
        $currentuser['stack'] = $this->items;

        $this->app['users']->saveUser($currentuser);
    }

    /**
     * Get the allowed filetypes.
     *
     * @return array
     */
    public function getFileTypes()
    {
        return array_merge($this->imageTypes, $this->documentTypes);
    }
}
