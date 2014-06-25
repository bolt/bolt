<?php
namespace Editable;

use Silex\Application;

class EditableElement
{

    public $id;

    public $contenttypeslug;

    public $token;

    public $fieldname;

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function applyRecord($record, $fieldname)
    {
        $this->contenttypeslug = $record->contenttype['slug'];
        $perm = implode(':', array(
            'contenttype',
            $this->contenttypeslug,
            'edit',
            $record->id
        ));
        if ($this->app['users']->isAllowed($perm)) {
            $this->id = $record->id;
            $this->token = $this->app['users']->getAntiCSRFToken();
            $this->fieldname = $fieldname;
        }
    }

    public function save($value)
    {
        if (empty($this->id) || empty($this->contenttypeslug) || empty($this->fieldname)) {
            throw new EditableElementException();
        }

        $content = $this->app['storage']->getContent($this->contenttypeslug, array(
            'id' => $this->id
        ));

        $user = $this->app['users']->getCurrentUser();

        if ($content['ownerid'] != $user['id']) {
            if (! $this->app['users']->isAllowed("contenttype:$contenttype:change-ownership:{$this->id}")) {
                return false;
            }
        }
        $content['ownerid'] = $user['id'];

        $now = date("Y-m-d H:i:s");
        $content['datechanged'] = $now;

        // @todo filter fields must not be overvritten
        if (! $this->app['users']->checkAntiCSRFToken($this->token) || $this->id != $content['id'] || ! array_key_exists($this->fieldname, $content->values)) {
            return false;
        }

        $content->values[$this->fieldname] = $value;
        $id = $this->app['storage']->saveContent($content);
        return $id;
    }

    public function getElementContentId()
    {
        return "ext_edit_{$this->contenttypeslug}_{$this->id}";
    }
}

class EditableElementException extends \Exception
{
}