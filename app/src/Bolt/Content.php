<?php

Namespace Bolt;

Use Silex;

class Content /* implements \ArrayAccess -- Temporily commented out, see https://github.com/bobdenotter/bolt/issues/76 */
{
    private $app;
    public $id;
    public $values;
    public $taxonomy;
    public $relation;
    public $contenttype;

    public function __construct(Silex\Application $app, $contenttype = "", $values = "")
    {

        $this->app = $app;

        if (!empty($values)) {
            $this->setValues($values);
        } else {
            // Return an '(undefined contenttype)'..
            if (is_array($contenttype)) {
                $contenttype = $contenttype['name'];
            }
            $values = array(
                'name' => "(undefined $contenttype)",
                'title' => "(undefined $contenttype)"
            );
            $this->setValues($values);
        }

        if (!empty($contenttype)) {
            // Set the contenttype
            $this->setContenttype($contenttype);

            // If this contenttype has a taxonomy with 'grouping', initialize the group.
            foreach ($contenttype['taxonomy'] as $taxonomytype) {
                if ($this->app['config']['taxonomy'][$taxonomytype]['behaves_like'] == "grouping") {
                    $this->setGroup("");
                }
            }

        }
    }

    public function setValues($values)
    {

        if (!empty($values['id'])) {
            $this->id = $values['id'];
        }

        $this->values = $values;

        if (!isset($this->values['datecreated']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datecreated'])) {
            $this->values['datecreated'] = "1970-01-01 00:00:00";
        }

        if (!isset($this->values['datepublish']) || ($this->values['datepublish'] < "1971-01-01 01:01:01") ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datepublish'])) {
            $this->values['datepublish'] = date("Y-m-d H:i:s");
        }

        if (!isset($this->values['datechanged']) || ($this->values['datepublish'] < "1971-01-01 01:01:01") ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datechanged'])) {
            $this->values['datechanged'] = date("Y-m-d H:i:s");
        }

        if (!empty($values['username'])) {
            $this->user = $this->app['users']->getUser($values['username']);
        }

        // Check if the values need to be unserialized..
        foreach ($this->values as $key => $value) {
            if (!empty($value) && is_string($value) && substr($value, 0, 2)=="a:") {
                $unserdata = @unserialize($value);
                if ($unserdata !== false) {
                    $this->values[$key] = $unserdata;
                }
            }
        }

    }

    public function setValue($key, $value)
    {

        // Check if the value need to be unserialized..
        if (substr($value, 0, 2)=="a:") {
            $unserdata = @unserialize($value);
            if ($unserdata !== false) {
                $value = $unserdata;
            }
        }

        if ($key == 'id') {
            $this->id = $value;
        }

        if ($key == 'datecreated' || $key == 'datechanged' || $key == 'datepublish') {
            if ( !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $value) ) {
                // TODO Try better date-parsing, instead of just setting it to 'now'..
                $value = date("Y-m-d H:i:s");
            }
        }

        if (!isset($this->values['datechanged']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datechanged'])) {
            $this->values['datechanged'] = date("Y-m-d H:i:s");
        }

        $this->values[$key] = $value;

    }

    public function setFromPost($values, $contenttype)
    {

        $values = cleanPostedData($values);

        // Some field types need to do things to the POST-ed value.
        foreach ($contenttype['fields'] as $fieldname => $field) {

            if ($field['type'] == "video" && isset($values[ $fieldname ])) {
                $video = $values[ $fieldname ];
                // update the HTML, according to given width and height
                if (!empty($video['width']) && !empty($video['height'])) {
                    $video['html'] = preg_replace("/width=(['\"])([0-9]+)(['\"])/i", 'width=${1}'.$video['width'].'${3}', $video['html']);
                    $video['html'] = preg_replace("/height=(['\"])([0-9]+)(['\"])/i", 'height=${1}'.$video['height'].'${3}', $video['html']);
                }

                $responsiveclass = "responsive-video";

                // See if it's widescreen or not..
                if (!empty($video['height']) && ( ($video['width'] / $video['height']) > 1.76) ) {
                    $responsiveclass .= " widescreen";
                }

                if (strpos($video['url'], "vimeo") !== false) {
                    $responsiveclass .= " vimeo";
                }

                $video['responsive'] = sprintf('<div class="%s">%s</div>', $responsiveclass, $video['html']);

                $values[ $fieldname ] = $video;
            }

        }

        // Get the taxonomies from the POST-ed values.
        // TODO: use $this->setTaxonomy() for this
        if (!empty($values['taxonomy'])) {
            foreach ($values['taxonomy'] as $taxonomytype => $value) {
                if (!is_array($value)) {
                    $value = explode(",", $value);
                }
                $this->taxonomy[$taxonomytype] = $value;
            }
            unset($values['taxonomy']);
        }


        // Get the relations from the POST-ed values.
        // TODO: use $this->setRelation() for this
        if (!empty($values['relation'])) {
            $this->relation = $values['relation'];
            unset($values['relation']);
        }

        // TODO: check for allowed file types..

        // Handle file-uploads.
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $file) {

                $filename = sprintf("%s/files/%s/%s",
                    $this->app['paths']['rootpath'], date("Y-m"), safeString($file['name'][0], false, "[]{}()"));
                $basename = sprintf("/%s/%s", date("Y-m"), safeString($file['name'][0], false, "[]{}()"));

                if ($file['error'][0] != UPLOAD_ERR_OK) {
                    $this->app['log']->add("Upload: Error occured during upload: " . $file['error'][0], 2);
                    continue;
                }

                if (substr($key, 0, 11)!="fileupload-") {
                    $this->app['log']->add("Upload: skipped an upload that wasn't for Content.", 2);
                    continue;
                }

                $fieldname = substr($key, 11);

                // Make sure the folder exists.
                makeDir(dirname($filename));

                // Check if we don't have doubles.
                if (is_file($filename)) {
                    while (is_file($filename)) {
                        $filename = $this->upcountName($filename);
                        $basename = $this->upcountName($basename);
                    }
                }

                if (is_writable(dirname($filename))) {
                    // Yes, we can create the file!
                    move_uploaded_file($file['tmp_name'][0], $filename);
                    $this->app['log']->add("Upload: uploaded file '$basename'.", 2);
                    $values[$fieldname] = $basename;
                } else {
                    $this->app['log']->add("Upload: couldn't write upload '$basename'.", 2);
                }

            }
        }

        $this->setValues($values);

    }

    /**
     * "upcount" a filename: Add (1), (2), etc. for filenames that already exist.
     * Taken from jQuery file upload..
     *
     * @param  string $name
     * @return string
     */
    protected function upcountName($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcountNameCallback'),
            $name,
            1
        );
    }


    /**
     * "upcount" callback helper function
     * Taken from jQuery file upload..
     *
     * @see upcountName()
     * @param array $matches
     * @internal param string $name
     * @return string
     */
    protected function upcountNameCallback($matches)
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';

        return ' ('.$index.')'.$ext;
    }



    public function setContenttype($contenttype)
    {

        if (is_string($contenttype)) {
            $contenttype = $this->app['storage']->getContenttype($contenttype);
        }

        $this->contenttype = $contenttype;

    }

    public function setTaxonomy($taxonomytype, $value)
    {

        $this->taxonomy[$taxonomytype][] = $value;

        // If it's a "grouping" type, set $this->group.
        if ($this->app['config']['taxonomy'][$taxonomytype]['behaves_like'] == "grouping") {
            $this->setGroup($value);
        }

    }

    public function setRelation($contenttype, $id)
    {

        if (!empty($this->relation[$contenttype])) {
            $ids = $this->relation[$contenttype];
        } else {
            $ids = array();
        }

        $ids[] = $id;
        sort($ids);

        $this->relation[$contenttype] = array_unique($ids);

    }


    public function getTaxonomyType($type)
    {
        if (isset($this->config['taxonomy'][$type])) {
            return $this->config['taxonomy'][$type];
        } else {
            return false;
        }

    }

    public function setGroup($value)
    {
        $this->group = $value;
    }

    /**
     * magic __call function, used for when templates use {{ content.title }},
     * so we can map it to $this->values['title']
     */
    public function __call($name, $arguments)
    {
        if (isset($this->values[$name])) {

            // This is too invasive. for now, only add editable when needed
            /*
            $fieldtype = $this->contenttype['fields'][$name]['type'];

            if (in_array($fieldtype, array('html', 'text', 'textarea'))) {
                $output = sprintf("<div class='bolt-editable'>%s</div>", $this->values[$name]);
            } else {
                $output = $this->values[$name];
            }

            return $output;
            */

            $fieldtype = $this->fieldtype($name);

            // Parse the field as Markdown, return HTML
            if ($fieldtype == "markdown") {
                include_once __DIR__. "/../../classes/markdown.php";
                $html = Markdown($this->values[$name]);
                return $html;
            }

            // Parse the field as JSON, return the array
            if ($fieldtype == "imagelist") {
                $list = json_decode($this->values[$name]);
                return $list;
            }

            return $this->values[$name];

        } else {
            return false;
        }
    }

    /**
     * pseudo-magic function, used for when templates use {{ content.get(title) }},
     * so we can map it to $this->values['title']
     */
    public function get($name)
    {

        // For fields that are stored as arrays, like 'video'
        if (strpos($name, ".") > 0) {
            list ($name, $attr) = explode(".", $name);
            if (!empty($attr) && isset($this->values[$name][$attr])) {
                return $this->values[$name][$attr];
            }
        }

        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else {
            return false;
        }
    }

    /**
     * Get the title, name, caption or subject..
     */
    public function getTitle()
    {

        if (isset($this->values['title'])) {
            return $this->values['title'];
        } elseif (isset($this->values['name'])) {
            return $this->values['name'];
        } elseif (isset($this->values['caption'])) {
            return $this->values['caption'];
        } elseif (isset($this->values['subject'])) {
            return $this->values['subject'];
        } else {

            // Grab the first field of type 'text', and assume that's the title.
            foreach($this->contenttype['fields'] as $key => $field) {
                if ($field['type']=='text') {
                    return $this->values[ $key ];
                }
            }

            // nope, no title was found..
            return "(untitled)";
        }

    }


    /**
     * Get the first image in the content ..
     */
    public function getImage()
    {

        // Grab the first field of type 'image', and return that.
        foreach($this->contenttype['fields'] as $key => $field) {
            if ($field['type']=='image') {
                return $this->values[ $key ];
            }
        }

        // otherwise, no image.
        return "";

    }


    /**
     * Creates a link to the content record
     */
    public function link($param = "")
    {

        // TODO: use Silex' UrlGeneratorServiceProvider instead.

        // If there's no valid content, return no link.
        if (empty($this->id)) {
            return "";
        }

        $link = sprintf("%s%s/%s",
            $this->app['paths']['root'],
            $this->contenttype['singular_slug'],
            $this->values['slug'] );

        return $link;

    }

    /**
     * Gets one or more related records.
     *
     */
    public function related($filtercontenttype="", $filterid="")
    {

        if (empty($this->relation)) {
            // nothing to do here.
            return false;
        }

        $records = array();

        foreach($this->relation as $contenttype => $ids) {

            if (!empty($filtercontenttype) && ($contenttype!=$filtercontenttype) ) {
                continue; // Skip other contenttypes, if we requested a specific type.
            }

            foreach($ids as $id) {

                if (!empty($filterid) && ($id!=$filterid) ) {
                    continue; // Skip other ids, if we requested a specific id.
                }

                $records[] = $this->app['storage']->getContent($contenttype."/".$id);
            }
        }

        return $records;

    }


    /**
     * Gets the correct template to use, based on our cascading template rules.
     *
     */
    public function template()
    {

        $template = $this->app['config']['general']['record_template'];
        $chosen = 'config';

        if (isset($this->contenttype['record_template'])) {
            $template = $this->contenttype['record_template'];
            $chosen = 'contenttype';
        }

        foreach ($this->contenttype['fields'] as $name => $field) {
            if ($field['type']=="templateselect" && !empty($this->values[$name]) ) {
                $template = $this->values[$name];
                $chosen = 'record';
            }
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']['general']['theme'] . "/$template ($chosen)");

        return $template;

    }

    /**
     * Get the fieldtype for a given fieldname.
     * @param $key
     * @return string
     */
    public function fieldtype($key)
    {

        foreach ($this->contenttype['fields'] as $name => $field) {
            if ($name == $key) {
                return $field['type'];
            }
        }

        return "";

    }

    /**
     *
     * Create an excerpt for the content.
     *
     * @param  int    $length
     * @return string
     */
    public function excerpt($length = 200)
    {
        $excerpt = array();

        foreach ($this->contenttype['fields'] as $key => $field) {
            if (in_array($field['type'], array('text', 'html', 'textarea', 'markdown'))
                && isset($this->values[$key])
                && !in_array($key, array("title", "name")) ) {
                $excerpt[] = $this->values[$key];
            }
        }

        $excerpt = implode(" ", $excerpt);

        $excerpt = trimText(strip_tags($excerpt), $length) ;

        return $excerpt;

    }

    /**
     * Creates RSS safe content. Wraps it in CDATA tags, strips style and
     * scripts out. Can optionally also return a (cleaned) excerpt.
     *
     * @param string $field The field to clean up
     * @param int $excerptLength Number of chars of the excerpt
     * @return string RSS safe string
     */
    public function rss_safe($field = '', $excerptLength = 0)
    {
        if (array_key_exists($field, $this->values)){
            if ($this->fieldtype($field) == 'html'){
                $value = $this->values[$field];
                // Completely remove style and script blocks
                // Remove script tags
                $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $value);
                // Remove style tags
                $value = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/i', '', $value);
                // Strip other tags
                // How about 'blockquote'?
                $allowedTags = array('a', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'p', 'strong', 'em', 'u', 'strike');
                $value = strip_tags($value, '<' . implode('><', $allowedTags) . '>');

                $result = htmlspecialchars($value, ENT_COMPAT | ENT_XML1, 'UTF-8', false);
                if ($excerptLength > 0){
                    $result = trimText($result, $excerptLength);
                }
                return '<![CDATA[ ' . $result . ' ]]>';
            }
            else {
                return $this->values[$field];
            }
        }
        return "";
    }

    /* Temporarily commented out.. See https://github.com/bobdenotter/bolt/issues/76
    /**
     * ArrayAccess support
     * /
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    /**
     * ArrayAccess support
     * /
    public function offsetGet($offset)
    {
        if (isset($this->values[$offset])) {
            return $this->values[$offset];
        }
        return null;
    }

    /**
     * ArrayAccess support
     * /
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * ArrayAccess support
     * /
    public function offsetUnset($offset)
    {
        if (isset($this->values[$offset])) {
            unset($this->values[$offset]);
        }
    }
    --- */
}
