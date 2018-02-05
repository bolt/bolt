<?php

namespace Bolt\Storage\Entity;

use Bolt\Common\Json;
use Bolt\Storage\Mapping\ClassMetadata;

/**
 * Entity for change logs.
 */
class LogChange extends Entity
{
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
    protected $mutation_type;
    /** @var array */
    protected $diff;
    /** @var string */
    protected $comment;
    /** @var ClassMetadata */
    protected $contentTypeMeta;

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
    public function getOwnerId()
    {
        return $this->ownerid;
    }

    /**
     * @param int $ownerId
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerid = $ownerId;
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
    public function getContentType()
    {
        return $this->contenttype;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contenttype = $contentType;
    }

    /**
     * @return int
     */
    public function getContentId()
    {
        return $this->contentid;
    }

    /**
     * @param int $contentId
     */
    public function setContentId($contentId)
    {
        $this->contentid = $contentId;
    }

    /**
     * @return string
     */
    public function getMutationType()
    {
        return $this->mutation_type;
    }

    /**
     * @param string $mutationType
     */
    public function setMutationType($mutationType)
    {
        $this->mutation_type = $mutationType;
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
     * Allows injecting the metadata configuration into the record so output can be built based on variable types.
     *
     * @param ClassMetadata $config
     */
    public function setContentTypeMeta(ClassMetadata $config)
    {
        $this->contentTypeMeta = $config;
    }

    /**
     * Get changed fields.
     *
     * @param array $contentType
     *
     * @return array
     */
    public function getChangedFields(array $contentType)
    {
        $changedFields = [];

        if (empty($this->diff)) {
            return $changedFields;
        }

        // Get the ContentType that we're dealing with
        $fields = $contentType['fields'];

        $hash = [
            'html'        => 'fieldText',
            'markdown'    => 'fieldText',
            'textarea'    => 'fieldText',
            'filelist'    => 'fieldList',
            'imagelist'   => 'fieldList',
            'geolocation' => 'fieldGeolocation',
            'image'       => 'fieldImage',
            'embed'       => 'fieldVideo',
            'select'      => 'fieldSelect',
            'video'       => 'fieldVideo',
        ];

        foreach ($this->diff as $key => $value) {
            if (!isset($fields[$key])) {
                continue;
            }

            $type = $fields[$key]['type'];
            $changedFields[$key] = [
                'type'   => $type,
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

            /** @var string $type */
            $type = $fields[$key]['type'];
            if (isset($hash[$type])) {
                $func = $hash[$type];
                $changedFields[$key] = array_merge($changedFields[$key], $this->{$func}($key, $value, $fields));
            }
        }

        return $changedFields;
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
            'before' => ['render' => Json::parse($value[0])],
            'after'  => ['render' => Json::parse($value[1])],
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
        $before = Json::parse($value[0]);
        $after = Json::parse($value[1]);

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
        $before = Json::parse($value[0]);
        $after = Json::parse($value[1]);

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
     * Compile changes for embed field types.
     *
     * @param string $key
     * @param string $value
     * @param array  $fields
     *
     * @return array
     */
    private function fieldOembed($key, $value, array $fields)
    {
        $before = Json::parse($value[0]);
        $after = Json::parse($value[1]);

        return [
            'type'   => $fields[$key]['type'],
            'before' => [
                'render' => [
                    'url'           => $before['url'],
                    'type'          => $before['type'],
                    'author_name'   => $before['author_name'],
                    'author_url'    => $before['author_url'],
                    'provider_name' => $before['provider_name'],
                    'provider_url'  => $before['provider_url'],
                    'height'        => $before['height'],
                    'width'         => $before['width'],
                    'html'          => $before['html'],
                ],
            ],
            'after'  => [
                'render' => [
                    'url'           => $after['url'],
                    'type'          => $after['type'],
                    'author_name'   => $after['author_name'],
                    'author_url'    => $after['author_url'],
                    'provider_name' => $after['provider_name'],
                    'provider_url'  => $after['provider_url'],
                    'height'        => $after['height'],
                    'width'         => $after['width'],
                    'html'          => $after['html'],
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
            $before = $value[0];
            $after = $value[1];
        } else {
            $before = $value[0];
            $after = $value[1];
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
        $before = Json::parse($value[0]);
        $after = Json::parse($value[1]);

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
