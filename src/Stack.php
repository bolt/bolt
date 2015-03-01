<?php

namespace Bolt;

use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex;
use utilphp\util;

/**
 * Simple stack implementation for remembering "10 last items".
 * Each user (by design) has their own stack. No sharesies!
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Stack
{
    const MAX_ITEMS = 10;

    private $items;
    private $imagetypes = array('jpg', 'jpeg', 'png', 'gif');
    private $documenttypes = array('doc', 'docx', 'txt', 'md', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx', 'csv');
    private $app;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;

        $currentuser = $this->app['users']->getCurrentUser();

        $stackItems = false;
        if (isset($_SESSION['stack'])) {
            $stackItems = Lib::smartUnserialize($_SESSION['stack']);
        }
        if (!is_array($stackItems)) {
            $stackItems = Lib::smartUnserialize($currentuser['stack']);
        }
        if (!is_array($stackItems)) {
            $stackItems = array();
        }

        // intersect the allowed types with the types set
        $this->imagetypes = array_intersect($this->imagetypes, $app['config']->get('general/accept_file_types'));
        $this->documenttypes = array_intersect($this->documenttypes, $app['config']->get('general/accept_file_types'));

        $this->items = $stackItems;
    }

    /**
     * Add a certain item to the stack.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function add($filename)
    {
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
     * @return bool
     */
    public function isOnStack($filename)
    {
        // We don't always need the "files/" part in the filename.
        $shortname = str_replace("files/", "", $filename);

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
     * @return bool
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
     * @param int    $count
     * @param string $typefilter
     *
     * @return array
     */
    public function listitems($count = 100, $typefilter = "")
    {
        // Make sure typefilter is an array, if passed something like "image, document"
        if (!empty($typefilter)) {
            $typefilter = array_map("trim", explode(",", $typefilter));
        }

        // Our basepaths for all files that can be on the stack: 'files' and 'theme'.
        $filespath = $this->app['paths']['filespath'];
        $themepath = $this->app['paths']['themebasepath'];

        $items = $this->items;
        $list = array();

        foreach ($items as $item) {
            $extension = strtolower(Lib::getExtension($item));
            if (in_array($extension, $this->imagetypes)) {
                $type = "image";
            } elseif (in_array($extension, $this->documenttypes)) {
                $type = "document";
            } else {
                $type = "other";
            }

            // Skip this one, if it doesn't match the type.
            if (!empty($typefilter) && (!in_array($type, $typefilter))) {
                continue;
            }

            // Figure out the full path, based on the two possible locations.
            $fullpath = '';
            if (is_readable(str_replace("files/files/", "files/", $filespath . "/" . $item))) {
                $fullpath = str_replace("files/files/", "files/", $filespath . "/" . $item);
            } elseif (is_readable($themepath . "/" . $item)) {
                $fullpath = $themepath . "/" . $item;
            }

            // No dice! skip this one.
            if (empty($fullpath)) {
                continue;
            }

            $thisitem = array(
                'basename'    => basename($item),
                'extension'   => $extension,
                'filepath'    => str_replace("files/", "", $item),
                'type'        => $type,
                'writable'    => is_writable($fullpath),
                'readable'    => is_readable($fullpath),
                'filesize'    => Lib::formatFilesize(filesize($fullpath)),
                'modified'    => date("Y/m/d H:i:s", filemtime($fullpath)),
                'permissions' => util::full_permissions($fullpath)
            );

            $thisitem['info'] = sprintf(
                "%s: <code>%s</code><br>%s: %s<br>%s: %s<br>%s: <code>%s</code>",
                Trans::__('Path'),
                $thisitem['filepath'],
                Trans::__('Filesize'),
                $thisitem['filesize'],
                Trans::__('Modified'),
                $thisitem['modified'],
                Trans::__('Permissions'),
                $thisitem['permissions']
            );

            if ($type == "image") {
                $size = getimagesize($fullpath);
                $thisitem['imagesize'] = sprintf("%s × %s", $size[0], $size[1]);
                $thisitem['info'] .= sprintf("<br>%s: %s × %s px", Trans::__('Size'), $size[0], $size[1]);
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
        $this->items = array_slice($this->items, 0, self::MAX_ITEMS);
        $ser = json_encode($this->items);

        $_SESSION['items'] = $ser;

        $currentuser = $this->app['users']->getCurrentUser();
        $currentuser['stack'] = $ser;
        $this->app['users']->saveUser($currentuser);
    }

    /**
     * Get the allowed filetypes.
     */
    public function getFileTypes()
    {
        return array_merge($this->imagetypes, $this->documenttypes);
    }
}
