<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for change logs.
 */
class LogChange extends Entity
{
    /** @var int */
    protected $id;
    /** @var \DateTime */
    protected $date;
    /** @var int */
    protected $ownerid;
    /** @var string */
    protected $title;
    /** @var string */
    protected $contenttype;
    /** @var int */
    protected $contentid;
    /** @var string */
    protected $mutationType;
    /** @var array */
    protected $diff;
    /** @var string */
    protected $comment;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getOwnerid()
    {
        return $this->ownerid;
    }

    /**
     * @param int $ownerid
     */
    public function setOwnerid($ownerid)
    {
        $this->ownerid = $ownerid;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getContenttype()
    {
        return $this->contenttype;
    }

    /**
     * @param string $contenttype
     */
    public function setContenttype($contenttype)
    {
        $this->contenttype = $contenttype;
    }

    /**
     * @return int
     */
    public function getContentid()
    {
        return $this->contentid;
    }

    /**
     * @param int $contentid
     */
    public function setContentid($contentid)
    {
        $this->contentid = $contentid;
    }

    /**
     * @return string
     */
    public function getMutationType()
    {
        return $this->mutationType;
    }

    /**
     * @param string $mutationType
     */
    public function setMutationType($mutationType)
    {
        $this->mutationType = $mutationType;
    }

    /**
     * @return array
     */
    public function getDiff()
    {
        return $this->diff;
    }

    /**
     * @param array $diff
     */
    public function setDiff($diff)
    {
        $this->diff = $diff;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get changed fields.
     *
     * @return array
     */
    public function getChangedFields()
    {
        $changedfields = [];

        if (empty($this->diff)) {
            return $changedfields;
        }

        // Get the contenttype that we're dealing with
        $fields = $this->contentTypeMeta['fields'];
        $hash = [
            'html'        => 'fieldText',
            'markdown'    => 'fieldText',
            'textarea'    => 'fieldText',
            'filelist'    => 'fieldList',
            'imagelist'   => 'fieldList',
            'geolocation' => 'fieldGeolocation',
            'image'       => 'fieldImage',
            'select'      => 'fieldSelect',
            'video'       => 'fieldVideo',
        ];

        foreach ($this->diff as $key => $value) {
            $changedfields[$key] = [
                'type'   => 'normal',
                'label'  => empty($fields[$key]['label']) ? $key : $fields[$key]['label'],
                'before' => [
                    'raw'    => $value[0],
                    'render' => $value[0],
                ],
                'after'  => [
                    'raw'    => $value[1],
                    'render' => $value[1],
                ],
            ];

            if (isset($hash[$fields[$key]['type']])) {
                $func = $hash[$fields[$key]['type']];
                $changedfields[$key] = array_merge($changedfields[$key], $this->{$func}($key, $value, $fields));
            }
        }

        return $changedfields;
    }

    /**
     * Compile changes for text field types.
     *
     * @param string $key
     * @param string $value
     * @param array  $fields
     *
     * @return array
     */
    private function fieldText($key, $value, array $fields)
    {
        return ['type' => $fields[$key]['type']];
    }

    /**
     * Compile changes for list field types.
     *
     * @param string $key
     * @param string $value
     * @param array  $fields
     *
     * @return array
     */
    private function fieldList($key, $value, array $fields)
    {
        return [
            'type'   => $fields[$key]['type'],
            'before' => ['render' => json_decode($value[0], true)],
            'after'  => ['render' => json_decode($value[1], true)],
        ];
    }

    /**
     * Compile changes for geolocation field types.
     *
     * @param string $key
     * @param string $value
     * @param array  $fields
     *
     * @return array
     */
    private function fieldGeolocation($key, $value, array $fields)
    {
        $before = json_decode($value[0], true);
        $after  = json_decode($value[1], true);

        return [
            'type'   => $fields[$key]['type'],
            'before' => [
                'render' => [
                    'address'           => $before['address'],
                    'latitude'          => $before['latitude'],
                    'longitude'         => $before['longitude'],
                    'formatted_address' => $before['formatted_address'],
                ],
            ],
            'after'  => [
                'render' => [
                    'address'           => $after['address'],
                    'latitude'          => $after['latitude'],
                    'longitude'         => $after['longitude'],
                    'formatted_address' => $after['formatted_address'],
                ],
            ],
        ];
    }

    /**
     * Compile changes for image field types.
     *
     * @param string $key
     * @param string $value
     * @param array  $fields
     *
     * @return array
     */
    private function fieldImage($key, $value, array $fields)
    {
        $before = json_decode($value[0], true);
        $after  = json_decode($value[1], true);

        return [
            'type'   => $fields[$key]['type'],
            'before' => [
                'render' => [
                    'file'  => $before['file'],
                    'title' => $before['title'],
                ],
            ],
            'after'  => [
                'render' => [
                    'file'  => $after['file'],
                    'title' => $after['title'],
                ],
            ],
        ];
    }

    /**
     * Compile changes for select field types.
     *
     * @param string $key
     * @param string $value
     * @param array  $fields
     *
     * @return array
     */
    private function fieldSelect($key, $value, array $fields)
    {
        if (isset($fields[$key]['multiple']) && $fields[$key]['multiple']) {
            $before = json_decode($value[0], true);
            $after  = json_decode($value[1], true);
        } else {
            $before = $value[0];
            $after  = $value[1];
        }

        return [
            'type'   => $fields[$key]['type'],
            'before' => ['render' => $before],
            'after'  => ['render' => $after],
        ];
    }

    /**
     * Compile changes for video field types.
     *
     * @param string $key
     * @param string $value
     * @param array  $fields
     *
     * @return array
     */
    private function fieldVideo($key, $value, array $fields)
    {
        $before = json_decode($value[0], true);
        $after  = json_decode($value[1], true);

        return [
            'type'   => $fields[$key]['type'],
            'before' => [
                'render' => [
                    'url'       => $before['url'],
                    'title'     => $before['title'],
                    'width'     => $before['width'],
                    'height'    => $before['height'],
                    'html'      => $before['html'],
                    'thumbnail' => $before['thumbnail'],
                ],
            ],
            'after'  => [
                'render' => [
                    'url'       => $after['url'],
                    'title'     => $after['title'],
                    'width'     => $after['width'],
                    'height'    => $after['height'],
                    'html'      => $after['html'],
                    'thumbnail' => $after['thumbnail'],
                ],
            ],
        ];
    }
}
