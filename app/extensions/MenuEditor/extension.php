<?php

namespace MenuEditor;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Translation\Loader as TranslationLoader;
use Symfony\Component\Yaml\Dumper as YamlDumper,
    Symfony\Component\Yaml\Parser as YamlParser,
    Symfony\Component\Yaml\Exception\ParseException;

class MenuEditorException extends \Exception {};
class Extension extends \Bolt\AbstractExtension
{
    private $authorized = false;
    private $backupDir;
    private $translationDir;
    public  $config;

    /**
     * @return array
     */
    public function info()
    {

        return array(
            'name'          => "MenuEditor",
            'description'   => "a visual menu editor",
            'tags'          => array('menu', 'editor', 'admin', 'tool'),
            'type'          => "Administrative Tool",
            'author'        => "Steven Wüthrich / bacbos lab",
            'link'          => "http://www.bacbos.ch",
            'email'         => 'steven.wuethrich@me.com',
            'version'       => "1.7.0",

            'required_bolt_version' => "1.4",
            'highest_bolt_version'  => "1.7",
            'first_releasedate'     => "2013-12-22",
            'latest_releasedate'    => "2014-07-11"
        );

    }

    /**
     * Initialize extension
     */
    public function initialize()
    {
        $this->config = $this->getConfig();
        $this->backupDir = __DIR__ .'/backups';

        /**
         * ensure proper config
         */
        if (!isset($this->config['permissions']) || !is_array($this->config['permissions'])) {
            $this->config['permissions'] = array('root', 'admin', 'developer');
        } else {
            $this->config['permissions'][] = 'root';
        }
        if (!isset($this->config['enableBackups']) || !is_bool($this->config['enableBackups'])) {
            $this->config['enableBackups'] = false;
        }
        if (!isset($this->config['keepBackups']) || !is_int($this->config['keepBackups'])) {
            $this->config['keepBackups'] = 10;
        }

        // check if user has allowed role(s)
        $currentUser    = $this->app['users']->getCurrentUser();
        $currentUserId  = $currentUser['id'];

        foreach ($this->config['permissions'] as $role) {
            if ($this->app['users']->hasRole($currentUserId, $role)) {
                $this->authorized = true;
                break;
            }
        }

        if ($this->authorized)
        {

            $this->path = $this->app['config']->get('general/branding/path') . '/extensions/menu-editor';
            $this->app->match($this->path, array($this, 'loadMenuEditor'));

            $this->translationDir = __DIR__.'/translations/' . $this->app['locale'];
            if (is_dir($this->translationDir))
            {
                $iterator = new \DirectoryIterator($this->translationDir);
                foreach ($iterator as $fileInfo)
                {
                    if ($fileInfo->isFile())
                    {
                        $this->app['translator']->addLoader('yml', new TranslationLoader\YamlFileLoader());
                        $this->app['translator']->addResource('yml', $fileInfo->getRealPath(), $this->app['locale']);
                    }
                }
            }

            $this->addMenuOption(__('Menu editor'), $this->app['paths']['bolt'] . 'extensions/menu-editor', "icon-list");

        }
    }

    /**
     * Add some awesomeness to Bolt
     *
     * @return Response|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function loadMenuEditor()
    {

        /**
         * check if menu.yml is writable
         */
        $file = BOLT_CONFIG_DIR . '/menu.yml';
        if (@!is_readable($file) || !@is_writable($file)) {
            throw new \Exception(
                __("The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                    array('%s' => $file)));
        }
        if (!$writeLock = @filemtime($file)) {
            $writeLock = 0;
        }

        /**
         * try to set symlink to localized readme
         */
        $lastLocale = $this->app['cache']->contains('extension_MenuEditor') ? $this->app['cache']->fetch('extension_MenuEditor') : 'unknown';
        $lastLocale != $this->app['locale'] && @is_writable(__DIR__) ? $this->localizeReadme() : null;

        /**
         * process xhr-post
         */
        if ('POST' == $this->app['request']->getMethod() &&
            true === $this->app['request']->isXmlHttpRequest())
        {

            /**
             * restore backup
             */
            try {
                if ($filetime = $this->app['request']->get('filetime')) {

                    if ($this->restoreBackup($filetime)) {
                        $this->app['session']->getFlashBag()->set('success', __('Backup successfully restored'));
                        return $this->app->json(array('status' => 0));
                    }

                    throw new MenuEditorException(__("Backup file could not be found"));

                }

            } catch (MenuEditorException $e) {
                return $this->app->json(array('status' => 1, 'error' => $e->getMessage()));
            }

            /**
             * save menu(s)
             */
            try {
                if ($menus          = $this->app['request']->get('menus') &&
                    $writeLockToken = $this->app['request']->get('writeLock'))
                {

                    // don't proceed if the file was edited in the meantime
                    if ($writeLock != $writeLockToken) {
                        throw new MenuEditorException($writeLock, 1);
                    } else {
                        $dumper = new YamlDumper();
                        $dumper->setIndentation(2);
                        $yaml = $dumper->dump($this->app['request']->get('menus'), 9999);

                        // clean up dump a little
                        $yaml = preg_replace("~(-)(\n\s+)~mi", "$1 ", $yaml);

                        try {
                            $parser = new YamlParser();
                            $parser->parse($yaml);
                        } catch (ParseException $e) {
                            throw new MenuEditorException($writeLock, 2);
                        }

                        // create backup
                        if (true === $this->config['enableBackups']) {
                            $this->backup($writeLock);
                        }

                        // save
                        if (!@file_put_contents($file, $yaml)) {
                            throw new MenuEditorException($writeLock, 3);
                        }

                        clearstatcache(true, $file);
                        $writeLock = filemtime($file);

                        if (count($this->app['request']->get('menus')) > 1) {
                            $message = __("Menus successfully saved");
                        } else {
                            $message = __("Menu successfully saved");
                        }
                        $this->app['session']->getFlashBag()->set('success', $message);

                        return $this->app->json(array('writeLock' => $writeLock, 'status' => 0));

                    }

                    // broken request
                    throw new MenuEditorException($writeLock, 4);

                }

            } catch (MenuEditorException $e) {
                return $this->app->json(array('writeLock' => $e->getMessage(), 'status' => $e->getCode()));
            }
            
            /**
             * search contenttype(s)
             */
            try {
            	if ($this->app['request']->get('action') == 'search-contenttypes') {
            		$ct = $this->app['request']->get('ct');
            		$q = $this->app['request']->get('q');
            		$retVal = Array();
            		if (empty($ct)) {
            		    $contenttypes = $this->app['config']->get('contenttypes');
						foreach ($contenttypes as $ck => $contenttype) {
							$retVal[] = $this->app['storage']->getContent($contenttype['name'], array('title'=> "%$q%", 'slug'=>"%$q%", 'limit'=>100, 'order'=>'title'));
						}
            		} else {
            			$retVal[] = $this->app['storage']->getContent($ct, array('title'=> "%$q%", 'limit'=>100, 'order'=>'title'));
            		}
            		
            		return $this->app->json(array('records' => $retVal));
            	}
            } catch (Exception $e) {
            
            }
        }

        // add eMenuEditor template namespace to twig
        $this->app['twig.loader.filesystem']->addPath(__DIR__.'/views/', 'MenuEditor');

        /**
         * load stuff
         */
        $menus          = $this->app['config']->get('menu');
        $contenttypes   = $this->app['config']->get('contenttypes');
        $taxonomys      = $this->app['config']->get('taxonomy');

        foreach ($contenttypes as $cK => $contenttype) {
            $contenttypes[$cK]['records'] = $this->app['storage']->getContent($contenttype['name'], array());
        }

        foreach ($taxonomys as $tK => $taxonomy)
        {

            $taxonomys[$tK]['me_options'] = array();

            // fetch slugs
            if (isset($taxonomy['behaves_like']) && 'tags' == $taxonomy['behaves_like'])
            {

                $prefix = $this->app['config']->get('general/database/prefix', "bolt_");

                $taxonomytype = $tK;
                $query = "select distinct `%staxonomy`.`slug` from `%staxonomy` where `taxonomytype` = ? order by `slug` asc;";
                $query = sprintf($query, $prefix, $prefix);
                $query = $this->app['db']->executeQuery($query, array($taxonomytype));

                if ($results = $query->fetchAll()) {
                    foreach ($results as $result) {
                        $taxonomys[$tK]['me_options'][$taxonomy['singular_slug'] .'/'. $result['slug']] = $result['slug'];
                    }
                }

            }

            if (isset($taxonomy['behaves_like']) && 'grouping' == $taxonomy['behaves_like']) {
                foreach ($taxonomy['options'] as $oK => $option) {
                    $taxonomys[$tK]['me_options'][$taxonomy['singular_slug'] .'/'. $oK] = $option;
                }
            }

            if (isset($taxonomy['behaves_like']) && 'categories' == $taxonomy['behaves_like']) {
                foreach ($taxonomy['options'] as $option) {
                    $taxonomys[$tK]['me_options'][$taxonomy['singular_slug'] .'/'. $option] = $option;
                }
            }

        }

        // fetch backups
        $backups = array();
        if (true === $this->config['enableBackups'])
        {
            try {
                $backups = $this->backup(0, true);

            } catch (MenuEditorException $e) {
                $this->app['session']->getFlashBag()->set('warning', $e->getMessage());
            }
        }

        $body = $this->app['render']->render('@MenuEditor/base.twig', array(
            'contenttypes'  => $contenttypes,
            'taxonomys'     => $taxonomys,
            'menus'         => $menus,
            'writeLock'     => $writeLock,
            'backups'       => $backups
        ));

        return new Response($this->injectAssets($body));

    }

    /**
     * @param $html
     * @return mixed
     */
    private function injectAssets($html)
    {

        $urlbase = $this->app['paths']['app'];

        $assets = '<script src="{urlbase}/extensions/MenuEditor/assets/jquery.nestable.min.js"></script>
<script src="{urlbase}/extensions/MenuEditor/assets/bootbox.min.js"></script>
<script src="{urlbase}/extensions/MenuEditor/assets/menueditor.js"></script>
<link rel="stylesheet" href="{urlbase}/extensions/MenuEditor/assets/menueditor.css">';

        $assets = preg_replace('~\{urlbase\}~', $urlbase, $assets);

        // Insert just before </head>
        preg_match("~^([ \t]*)</head~mi", $html, $matches);
        $replacement = sprintf("%s\t%s\n%s", $matches[1], $assets, $matches[0]);
        return str_replace_first($matches[0], $replacement, $html);

    }

    /**
     * Saves a backup of the current menu.yml
     *
     * @param $writeLock
     * @param bool $justFetchList
     * @return array
     * @throws MenuEditorException
     */
    private function backup($writeLock, $justFetchList = false)
    {

        if (!@is_dir($this->backupDir) && !@mkdir($this->backupDir)) {
            // dir doesn't exist and I can't create it
            throw new MenuEditorException($justFetchList ? __("Please make sure that there is a MenuEditor/backups folder or disable the backup-feature in config.yml") : $writeLock, 5);
        }

        // try to save a backup
        if (false === $justFetchList &&
            !@copy(BOLT_CONFIG_DIR . '/menu.yml', $this->backupDir . '/menu.'. time() . '.yml'))
        {
            throw new MenuEditorException($writeLock, 5);
        }

        // clean up
        $backupFiles = array();
        foreach (new \DirectoryIterator($this->backupDir) as $fileinfo) {
            if ($fileinfo->isFile() && preg_match("~^menu\.[0-9]{10}\.yml$~i", $fileinfo->getFilename())) {
                $backupFiles[$fileinfo->getMTime()] = $fileinfo->getFilename();
            }
        }

        if ($justFetchList)
        {
            // make sure there's at least one backup file (first use...)
            if (count($backupFiles) == 0)
            {
                if (!@copy(BOLT_CONFIG_DIR . '/menu.yml', $this->backupDir . '/menu.'. time() . '.yml')) {
                    throw new MenuEditorException(__("Please make sure that the MenuEditor/backups folder is writeable by your webserver or disable the backup-feature in config.yml"));
                }
                return $this->backup(0, true);
            }

            krsort($backupFiles);
            return $backupFiles;
        }

        ksort($backupFiles);
        foreach ($backupFiles as $timestamp=>$backupFile)
        {
            if (count($backupFiles) <= (int) $this->config['keepBackups']) {
                break;
            }

            @unlink($this->backupDir . '/' . $backupFile);
            unset($backupFiles[$timestamp]);
        }

    }

    /**
     * Restores a previously saved backup, identified by its timestamp
     *
     * @param $filetime
     * @return bool
     * @throws MenuEditorException
     */
    private function restoreBackup($filetime)
    {

        $backupFiles = $this->backup(0, true);

        foreach ($backupFiles as $backupFiletime=>$backupFile)
        {
            if ($backupFiletime == $filetime)
            {
                // try to overwrite menu.yml
                if (@copy($this->backupDir . '/' . $backupFile, BOLT_CONFIG_DIR . '/menu.yml')) {
                    return true;
                }

                throw new MenuEditorException(__("Unable to overwrite menu.yml"));
            }
        }

        // requested backup-file was not found
        return false;
    }

    /**
     * symlinks the localized readme file, if existant
     */
    private function localizeReadme()
    {

        $this->app['cache']->save('extension_MenuEditor', $this->app['locale'], 604800);

        if (@file_exists(__DIR__ . '/readme.md')) {
            if (@is_link(__DIR__ . '/readme.md')) {
                return;
            }

            if (@is_dir($this->translationDir))
            {

                // try to set symbolic link
                if (@file_exists(__DIR__.'/translations/readme_'. $this->app['locale'] .'.md'))
                {
                    @copy(__DIR__ . '/readme.md', __DIR__ . '/readme_en.md');
                    @unlink(__DIR__ . '/readme.md');
                    @symlink(__DIR__.'/translations/readme_'. $this->app['locale'] .'.md', __DIR__ . '/readme.md');
                }
            }
        }

    }
}