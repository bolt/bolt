<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for change logs.
 *
 * @method integer   getId()
 * @method \DateTime getDate()
 * @method integer   getOwnerid()
 * @method string    getTitle()
 * @method string    getContenttype()
 * @method integer   getContentid()
 * @method string    getMutationType()
 * @method array     getDiff()
 * @method string    getComment()
 * @method setId($id)
 * @method setDate(\DateTime $date)
 * @method setOwnerid($ownerid)
 * @method setTitle($title)
 * @method setContenttype($contenttype)
 * @method setContentid($contentid)
 * @method setMutationType($mutationType)
 * @method setDiff($diff)
 * @method setComment($comment)
 */
class LogChange extends Entity
{
    protected $id;
    protected $date;
    protected $ownerid;
    protected $title;
    protected $contenttype;
    protected $contentid;
    protected $mutationType;
    protected $diff;
    protected $comment;

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
                    'render' => $value[0]
                ],
                'after'  => [
                    'raw'    => $value[1],
                    'render' => $value[1]
                ]
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
                ]
            ],
            'after'  => [
                'render' => [
                    'address'           => $after['address'],
                    'latitude'          => $after['latitude'],
                    'longitude'         => $after['longitude'],
                    'formatted_address' => $after['formatted_address'],
                ]
            ]
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
                ]
            ]
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
                ]
            ],
            'after'  => [
                'render' => [
                    'url'       => $after['url'],
                    'title'     => $after['title'],
                    'width'     => $after['width'],
                    'height'    => $after['height'],
                    'html'      => $after['html'],
                    'thumbnail' => $after['thumbnail'],
                ]
            ],
        ];
    }
}
