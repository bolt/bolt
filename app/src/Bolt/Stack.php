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

    const MAX_ITEMS = 6;

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

        if (in_array($filename, $this->items)) {
            return false;
        } else {
            array_unshift($this->items, $filename);
            $this->persist();
            return true;
        }

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

    public function listitems()
    {

        $items = $this->items;

        foreach ($items as $item) {
            $extension = getExtension($item);
            if (in_array($extension, $this->imagetypes)) {
                $type = "image";
            } else if (in_array($extension, $this->imagetypes)) {
                $type = "document";
            } else {
                $type = "other";
            }

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
        \util::var_dump($this->items);
        $_SESSION['items'] = serialize($this->items);
    }

}
