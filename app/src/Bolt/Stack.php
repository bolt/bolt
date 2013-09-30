<?php

namespace Bolt;

use Silex;

/**
 * Simple stack implementation for remebering "6 last items"
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 */
class Stack
{

    const MAX_ITEMS = 10;

    private $items;
    private $imagetypes = array('jpg', 'jpeg', 'png', 'gif');
    private $documenttypes = array('doc', 'docx', 'txt', 'md', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx');
    private $app;


    public function __construct(Silex\Application $app)
    {
        $this->app = $app;

        $currentuser = $this->app['users']->getCurrentUser();

        if (isset($_SESSION['stack']) && is_array(unserialize($_SESSION['stack']))) {
            $this->items = unserialize($_SESSION['stack']);
        } elseif (isset($currentuser['stack']) && is_array(unserialize($currentuser['stack']))) {
            $this->items = unserialize($currentuser['stack']);
        } else {
            $this->items = array();
        }

    }

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

    public function delete($filename)
    {
        foreach($this->items as $key => $item) {
            if ($item == $filename) {
                unset($this->items[$key]);
                $this->persist();
            }
        }
    }


    /**
     * Return a list with the current stacked items. Add some relevant info to each item,
     * and also check if the item is present and readable.
     *
     * @param int $count
     * @param string $typefilter
     * @return array
     */
    public function listitems($count = 100, $typefilter = "")
    {
        // Make sure typefiltet is an array, if passed something like "image, document"
        if (!empty($typefilter)) {
            $typefilter = array_map("trim", explode(",", $typefilter));
        }

        // Our basepath for all uploaded files.
        $filespath = $this->app['paths']['filespath'];

        $items = $this->items;
        $list = array();

        foreach ($items as $item) {
            $extension = getExtension($item);
            if (in_array($extension, $this->imagetypes)) {
                $type = "image";
            } else if (in_array($extension, $this->documenttypes)) {
                $type = "document";
            } else {
                $type = "other";
            }

            // Skip this one, if it doesn't match the type.
            if ( !empty($typefilter) && (!in_array($type, $typefilter)) ) {
                continue;
            }

            // Skip it, if it isn't readable or doesn't exist.
            $fullpath = str_replace("files/files/", "files/", $filespath . "/" . $item);
            if (!is_readable($fullpath)) {
                continue;
            }

            $thisitem = array(
                'basename' => basename($item),
                'extension' => $extension,
                'filepath' => $item,
                'type' => $type,
                'writable' => is_writable($fullpath),
                'readable' => is_readable($fullpath),
                'filesize' => formatFilesize(filesize($fullpath)),
                'modified' => date("Y/m/d H:i:s", filemtime($fullpath)),
                'permissions' => \util::full_permissions($fullpath)
            );

            $thisitem['info'] = sprintf("%s: <code>%s</code><br>%s: %s<br>%s: %s<br>%s: <code>%s</code>",
                __('Path'),
                $thisitem['filepath'],
                __('Filesize'),
                $thisitem['filesize'],
                __('Modified'),
                $thisitem['modified'],
                __('Permissions'),
                $thisitem['permissions']
            );


            if ($type == "image") {
                $size = getimagesize($fullpath);
                $thisitem['imagesize'] = sprintf("%s × %s", $size[0], $size[1]);
                $thisitem['info'] .= sprintf("<br>%s: %s × %s px", __("Size"), $size[0], $size[1]);
            }


            //add it to our list..
            $list[] = $thisitem;
        }

        $list = array_slice($list, 0, $count);

        return $list;

    }

    /**
     * Persist the contents of the current stack to the session, as well as the database.
     *
     */
    public function persist()
    {

        $this->items = array_slice($this->items, 0, self::MAX_ITEMS);
        $ser = serialize($this->items);

        $_SESSION['items'] = $ser;

        $currentuser = $this->app['users']->getCurrentUser();
        $currentuser['stack'] = $ser;
        $this->app['users']->saveUser($currentuser);

    }

}
