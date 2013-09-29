<?php

namespace Bolt;

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


    public function __construct()
    {
        if (isset($_SESSION['items']) && is_array(unserialize($_SESSION['items']))) {
            $this->items = unserialize($_SESSION['items']);
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

    public function listitems($count = 100, $typefilter = "")
    {

        $typefilter = explode(",", $typefilter);

        $items = $this->items;

        $items = array_slice($items, 0, $count);

        foreach ($items as $item) {
            $extension = getExtension($item);
            if (in_array($extension, $this->imagetypes)) {
                $type = "image";
            } else if (in_array($extension, $this->imagetypes)) {
                $type = "document";
            } else {
                $type = "other";
            }

            // Skip this one, if it doesn't match the type.
            if ( !empty($typefilter) && (!in_array($type, $typefilter)) ) {
                continue;
            }

            //add it to our list..
            $list[] = array(
                'basename' => basename($item),
                'extension' => $extension,
                'filepath' => $item,
                'type' => $type
            );
        }

        return $list;

    }

    public function persist()
    {

        $this->items = array_slice($this->items, 0, self::MAX_ITEMS);

        \util::var_dump($this->items);
        $_SESSION['items'] = serialize($this->items);
    }

}
