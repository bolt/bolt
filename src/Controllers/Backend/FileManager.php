<?php
namespace Bolt\Controllers\Backend;

use Bolt\Helpers\Input;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use League\Flysystem\File;
use Silex\ControllerCollection;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Backend controller for file/directory management routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileManager extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/files/{namespace}/{path}', 'controller.backend.file_manager:actionManage')
            ->assert('namespace', '[^/]+')
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('files');

        $c->match('/file/edit/{namespace}/{file}', 'controller.backend.file_manager:actionEdit')
            ->assert('file', '.+')
            ->assert('namespace', '[^/]+')
            ->value('namespace', 'files')
            ->bind('fileedit')
            ->after(function(Request $request, Response $response) {
                if ($request->isMethod('POST')) {
                    $response->headers->set('X-XSS-Protection', '0');
                }
            });
    }

    /*
     * Routes
     */

    /**
     * File editor.
     *
     * @param Request $request   The Symfony Request
     * @param string  $namespace The filesystem namespace
     * @param string  $file      The file path
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionEdit(Request $request, $namespace, $file)
    {
        if ($namespace == 'app' && dirname($file) == 'config') {
            // Special case: If requesting one of the major config files, like contenttypes.yml, set the path to the
            // correct dir, which might be 'app/config', but it might be something else.
            $namespace = 'config';
        }

        /** @var \League\Flysystem\FilesystemInterface $filesystem */
        $filesystem = $this->app['filesystem']->getFilesystem($namespace);

        if (!$filesystem->authorized($file)) {
            $error = Trans::__("You don't have correct permissions to edit the file '%s'.", array('%s' => $file));
            $this->abort(Response::HTTP_FORBIDDEN, $error);
        }

        /** @var \League\Flysystem\File $file */
        $file = $filesystem->get($file);

        $type = Lib::getExtension($file->getPath());

        // Get the pathsegments, so we can show the path.
        $path = dirname($file->getPath());
        $pathsegments = array();
        $cumulative = '';
        if (!empty($path)) {
            foreach (explode('/', $path) as $segment) {
                $cumulative .= $segment . '/';
                $pathsegments[$cumulative] = $segment;
            }
        }

        $contents = null;
        if (!$file->exists() || !($contents = $file->read())) {
            $error = Trans::__("The file '%s' doesn't exist, or is not readable.", array('%s' => $file->getPath()));
            $this->abort(Response::HTTP_NOT_FOUND, $error);
        }

        if (!$file->update($contents)) {
            $this->addFlash(
                'info',
                Trans::__(
                    "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                    array('%s' => $file->getPath())
                )
            );
            $writeallowed = false;
        } else {
            $writeallowed = true;
        }

        // Gather the 'similar' files, if present.. i.e., if we're editing config.yml, we also want to check for
        // config.yml.dist and config_local.yml
        $basename = str_replace('.yml', '', str_replace('_local', '', $file->getPath()));
        $filegroup = array();
        if ($filesystem->has($basename . '.yml')) {
            $filegroup[] = basename($basename . '.yml');
        }
        if ($filesystem->has($basename . '_local.yml')) {
            $filegroup[] = basename($basename . '_local.yml');
        }

        $data = array('contents' => $contents);

        /** @var Form $form */
        $form = $this->createFormBuilder('form', $data)
            ->add('contents', 'textarea')
            ->getForm();

        // Handle the POST and check if it's valid.
        if ($request->isMethod('POST')) {
            $this->handleEdit($request, $form, $file, $type);
        }

        // For 'related' files we might need to keep track of the current dirname on top of the namespace.
        if (dirname($file->getPath()) !== '') {
            $additionalpath = dirname($file->getPath()) . '/';
        } else {
            $additionalpath = '';
        }

        $context = array(
            'form'           => $form->createView(),
            'filetype'       => $type,
            'file'           => $file->getPath(),
            'basename'       => basename($file->getPath()),
            'pathsegments'   => $pathsegments,
            'additionalpath' => $additionalpath,
            'namespace'      => $namespace,
            'write_allowed'  => $writeallowed,
            'filegroup'      => $filegroup
        );

        return $this->render('editfile/editfile.twig', $context);
    }

    /**
     * The file management browser.
     *
     * @param Request $request   The Symfony Request
     * @param string  $namespace The filesystem namespace
     * @param string  $path      The path prefix
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionManage(Request $request, $namespace, $path)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        // Defaults
        $files      = array();
        $folders    = array();
        $formview   = false;
        $uploadview = true;

        $filesystem = $this->app['filesystem']->getFilesystem($namespace);

        if (!$filesystem->authorized($path)) {
            $error = Trans::__("You don't have the correct permissions to display the file or directory '%s'.", array('%s' => $path));
            $this->abort(Response::HTTP_FORBIDDEN, $error);
        }

        if (!$this->isAllowed('files:uploads')) {
            $uploadview = false;
        }

        if ($filesystem->getVisibility($path) === 'public') {
            $validFolder = true;
        } elseif ($filesystem->getVisibility($path) === 'readonly') {
            $validFolder = true;
            $uploadview = false;
        } else {
            $this->addFlash('error', Trans::__("The folder '%s' could not be found, or is not readable.", array('%s' => $path)));
            $formview = false;
            $validFolder = false;
        }

        if ($validFolder) {
            // Define the "Upload here" form.
            $form = $this->createFormBuilder('form')
                ->add(
                    'FileUpload',
                    'file',
                    array(
                        'label'    => Trans::__('Upload a file to this folder'),
                        'multiple' => true,
                        'attr'     => array(
                            'data-filename-placement' => 'inside',
                            'title'                   => Trans::__('Select file …'))
                    )
                )
                ->getForm();

            // Handle the upload.
            if ($request->isMethod('POST')) {
                $this->handleUpload($request, $form, $namespace, $path);

                return $this->redirectToRoute('files', array('path' => $path, 'namespace' => $namespace));
            }

            if ($uploadview !== false) {
                $formview = $form->createView();
            }

            list($files, $folders) = $filesystem->browse($path, $this->app);
        }

        // Get the pathsegments, so we can show the path as breadcrumb navigation.
        $pathsegments = array();
        $cumulative = '';
        if (!empty($path)) {
            foreach (explode('/', $path) as $segment) {
                $cumulative .= $segment . '/';
                $pathsegments[$cumulative] = $segment;
            }
        }

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKeditor to insert a file.
        if (!$request->query->has('CKEditor')) {
            $twig = 'files/files.twig';
        } else {
            $this->app['debugbar'] = false;
            $twig = 'files_ck/files_ck.twig';
        }

        $context = array(
            'path'         => $path,
            'files'        => $files,
            'folders'      => $folders,
            'pathsegments' => $pathsegments,
            'form'         => $formview,
            'namespace'    => $namespace,
        );

        return $this->render($twig, $context);
    }

    /*
     * Helper functions
     */

    /**
     * Handle a file edit POST.
     *
     * @param Request $request
     * @param Form    $form
     * @param File    $file
     * @param string  $type
     */
    private function handleEdit(Request $request, Form $form, File $file, $type)
    {
        $form->submit($request->get($form->getName()));

        if ($form->isValid()) {
            $data = $form->getData();
            $contents = Input::cleanPostedData($data['contents']) . "\n";

            $validYaml = true;

            // Before trying to save a yaml file, check if it's valid.
            if ($type == 'yml') {
                $yamlparser = new Parser();
                try {
                    $validYaml = $yamlparser->parse($contents);
                } catch (ParseException $e) {
                    $validYaml = false;
                    $this->addFlash('error', Trans::__("File '%s' could not be saved:", array('%s' => $file->getPath())) . $e->getMessage());
                }
            }

            if ($validYaml) {
                if ($file->update($contents)) {
                    $this->addFlash('info', Trans::__("File '%s' has been saved.", array('%s' => $file->getPath())));
                    // If we've saved a translation, back to it
                    $m = array();
                    if (preg_match('#resources/translations/(..)/(.*)\.yml$#', $file->getPath(), $m)) {
                        return $this->redirectToRoute('translation', array('domain' => $m[2], 'tr_locale' => $m[1]));
                    }
                    return $this->redirectToRoute('fileedit', array('file' => $file->getPath()));
                } else {
                    $this->addFlash('error', Trans::__("File '%s' could not be saved, for some reason.", array('%s' => $file->getPath())));
                }
            }
            // If we reach this point, the form will be shown again, with the error
            // in the input, so the user can try again.
        }
    }

    /**
     * Handle the upload POST.
     *
     * @param Request $request   The Symfony Request
     * @param Form    $form
     * @param string  $namespace The filesystem namespace
     * @param string  $path      The path prefix
     */
    private function handleUpload(Request $request, Form $form, $namespace, $path)
    {
        $form->submit($request);
        if ($form->isValid()) {
            $files = $request->files->get($form->getName());
            $files = $files['FileUpload'];

            foreach ($files as $fileToProcess) {
                $fileToProcess = array(
                    'name'     => $fileToProcess->getClientOriginalName(),
                    'tmp_name' => $fileToProcess->getPathName()
                );

                $originalFilename = $fileToProcess['name'];
                $filename = preg_replace('/[^a-zA-Z0-9_\\.]/', '_', basename($originalFilename));

                if ($this->app['filepermissions']->allowedUpload($filename)) {
                    $this->app['upload.namespace'] = $namespace;
                    $handler = $this->app['upload'];
                    $handler->setPrefix($path . '/');
                    $result = $handler->process($fileToProcess);

                    if ($result->isValid()) {
                        $this->addFlash(
                            'info',
                            Trans::__("File '%file%' was uploaded successfully.", array('%file%' => $filename))
                        );

                        // Add the file to our stack.
                        $this->app['stack']->add($path . '/' . $filename);
                        $result->confirm();
                    } else {
                        foreach ($result->getMessages() as $message) {
                            $this->addFlash('error', (string) $message);
                        }
                    }
                } else {
                    $extensionList = array();
                    foreach ($this->app['filepermissions']->getAllowedUploadExtensions() as $extension) {
                        $extensionList[] = '<code>.' . htmlspecialchars($extension, ENT_QUOTES) . '</code>';
                    }
                    $extensionList = implode(' ', $extensionList);
                    $this->addFlash(
                        'error',
                        Trans::__("File '%file%' could not be uploaded (wrong/disallowed file type). Make sure the file extension is one of the following:", array('%file%' => $filename))
                        . $extensionList
                    );
                }
            }
        } else {
            $this->addFlash(
                'error',
                Trans::__("File '%file%' could not be uploaded.", array('%file%' => $filename))
            );
        }
    }
}
