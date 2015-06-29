<?php

namespace Bolt\Logger;

use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Repository\UsersRepository;

/**
 * Class that represents a single change log entry.
 */
class ChangeLogItem implements \ArrayAccess
{
    /** @var \Bolt\Storage\Mapping\ClassMetadata */
    private $contentTypeMeta;
    /** @var \Bolt\Storage\Repository\UsersRepository */
    private $repository;

    private $id;
    private $date;
    private $title;
    private $ownerid;
    private $username;
    private $contenttype;
    private $contentid;
    private $mutation_type;
    private $mutation;
    private $diff_raw;
    private $diff;
    private $comment;
    private $changedfields;

    public function __construct(UsersRepository $repository, ClassMetadata $contentTypeMeta, $values = [])
    {
        $this->contentTypeMeta = $contentTypeMeta;
        $this->repository = $repository;
        $this->setParameters($values);
    }

    /**
     * Magic parameter test.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function __isset($key)
    {
        if (in_array($key, ['mutation_type', 'changedfields'])) {
            return true;
        }

        return false;
    }

    /**
     * Magic getter.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $valid = ['id', 'date', 'title', 'username', 'contentid', 'comment', 'diff_raw'];
        if (in_array($key, $valid)) {
            return $this->{$key};
        } elseif ($key == 'mutation_type') {
            return $this->getEffectiveMutationType();
        } elseif ($key == 'changedfields') {
            $this->changedfields = $this->getChangedFields();

            return $this->changedfields;
        }

        throw new \InvalidArgumentException("$key is not a valid parameter.");
    }

    /**
     * ArrayAccess support.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * ArrayAccess support.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * ArrayAccess support.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * ArrayAccess support.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * Return a human valid mutation type.
     *
     * @return array|string
     */
    private function getEffectiveMutationType()
    {
        if ($this->mutation !== 'UPDATE') {
            return $this->mutation;
        }

        $hash = [
            'published' => 'PUBLISH',
            'draft'     => 'DRAFT',
            'held'      => 'HOLD',
        ];

        $diff = $this->getParsedDiff();
        if (isset($diff['status'])) {
            return $hash[$diff['status']];
        }

        return 'UPDATE';
    }

    /**
     * Decode JSON and return an array.
     *
     * @return array
     */
    private function getParsedDiff()
    {
        $pdiff = json_decode($this->diff_raw, true);
        $fields = $this->contentTypeMeta['fields'];

        foreach (array_keys($pdiff) as $key) {
            if (!isset($fields[$key])) {
                continue;
            }
        }

        return $pdiff;
    }

    /**
     * Set class parameters.
     *
     * @param array $values
     */
    private function setParameters(array $values)
    {
        $values = $this->getFullParameters($values);

        $this->id = $values['id'];
        $this->date = $values['date'];
        $this->title = $values['title'];
        $this->contenttype = $values['contenttype'];
        $this->contentid = $values['contentid'];
        $this->mutation_type = $values['mutation_type'];
        $this->mutation = $values['mutation'];
        $this->comment = $values['comment'];
        $this->diff_raw = $values['diff'];
        $this->diff = json_decode($values['diff'], true);
        $this->ownerid = $values['ownerid'];

        $user = $this->repository->getUser($values['ownerid']);
        if (isset($user['displayname'])) {
            $this->username = $user['displayname'];
        } elseif (isset($user['username'])) {
            $this->username = $user['username'];
        } else {
            $this->username = "(deleted user #" . $values['ownerid'] . ")";
        }
    }

    /**
     * Get a fully pre-populated array, for values.
     *
     * @param array $values
     *
     * @return array
     */
    private function getFullParameters($values)
    {
        $default = [
            'id', 'date', 'title', 'ownerid', 'contenttype', 'contentid',
            'mutation_type', 'mutation', 'diff', 'comment'
        ];

        return array_merge(array_fill_keys($default, null), $values);
    }

    /**
     * Get changed fields.
     *
     * @return array
     */
    private function getChangedFields()
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
            'type' => $fields[$key]['type'],
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
