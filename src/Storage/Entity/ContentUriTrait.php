<?php

namespace Bolt\Storage\Entity;

use Bolt\Helpers\Html;

trait ContentUriTrait
{
    /**
     * Get a unique URL for a record
     *
     * @param integer $id
     * @param boolean $fulluri
     * @param string  $slugfield
     *
     * @return string
     */
    protected function getUri($id = 0, $fulluri = true, $slugfield = 'slug')
    {
        $title = $this->getTitle();
        $contenttype = $this->getContenttype();
        $repo = $this->app['storage']->getRepository($contenttype);
        $tablename = $repo->getTableName();
        $id = intval($id);
        $slug = $this->app['slugify']->slugify($title);

        // Don't allow strictly numeric slugs, unless allow_numeric_slugs options is set
        if (is_numeric($slug) && $contenttype['allow_numeric_slugs'] !== true) {
            $slug = $contenttype['singular_slug'] . "-" . $slug;
        }

        // Only add '{contenttype}/' if $full is requested.
        if ($fulluri) {
            $prefix = '/' . $contenttype['singular_slug'] . '/';
        } else {
            $prefix = '';
        }

        //check if the fieldname exists, otherwise use 'slug' as fallback
        if (!in_array($slugfield, array_keys($contenttype['fields']))) {
            $slugfield = 'slug';
        }

        $query = sprintf(
            "SELECT id from %s WHERE %s=? and id!=?",
            $tablename,
            $slugfield
        );

        $res = $this->app['db']->executeQuery(
            $query,
            [$slug, $id],
            [\PDO::PARAM_STR, \PDO::PARAM_INT]
        )->fetch();

        if (!$res) {
            $uri = $prefix . $slug;
        } else {
            for ($i = 1; $i <= 10; $i++) {
                $newslug = Html::trimText($slug, 127 - strlen($i), false) . '-' . $i;
                $res = $this->app['db']->executeQuery(
                    $query,
                    [$newslug, $id],
                    [\PDO::PARAM_STR, \PDO::PARAM_INT]
                )->fetch();
                if (!$res) {
                    $uri = $prefix . $newslug;
                    break;
                }
            }

            // otherwise, just get a random slug.
            if (empty($uri)) {
                $suffix = '-' . $this->app['randomgenerator']->generateString(6, 'abcdefghijklmnopqrstuvwxyz01234567890');
                $slug = Html::trimText($slug, 128 - strlen($suffix), false) . $suffix;
                $uri = $prefix . $slug;
            }
        }

        return $uri;
    }
}
