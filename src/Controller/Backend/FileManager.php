<?php

namespace Bolt\Controller\Backend;

use Bolt\Filesystem\Exception\ExceptionInterface;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\File;
use Bolt\Helpers\Input;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Backend controller for file/directory management routes.
 *
 * Prior to v3.0 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileManager extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/file/edit/{namespace}/{file}', 'edit')
            ->assert('file', '.+')
            ->assert('namespace', '[^/]+')
            ->value('namespace', 'files')
            ->bind('fileedit')
            ->after(function (Request $request, Response $response) {
                if ($request->isMethod('POST')) {
                    $response->headers->set('X-XSS-Protection', '0');
                }
            });

        $c->match('/files/{namespace}/{path}', 'manage')
            ->assert('namespace', '[^/]+')
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('files');
    }

    /**
     * File editor.
     *
     * @param Request $request   The Symfony Request
     * @param string  $namespace The filesystem namespace
     * @param string  $file      The file path
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $namespace, $file)
    {
        if ($namespace === 'app' && dirname($file) === 'config') {
            // Special case: If requesting one of the major config files, like contenttypes.yml, set the path to the
            // correct dir, which might be 'app/config', but it might be something else.
            $namespace = 'config';
        }

        /** @var FilesystemInterface $filesystem */
        $filesystem = $this->filesystem()->getFilesystem($namespace);

        if (!$filesystem->authorized($file)) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-permissions-edit-file', ['%s' => $file]));

            return new RedirectResponse($this->generateUrl('dashboard'));
        }

        try {
            /** @var File $file */
            $file = $filesystem->get($file);
            $type = Lib::getExtension($file->getPath());
            $data = ['contents' => $file->read()];
        } catch (FileNotFoundException $e) {
            $this->flashes()->error(Trans::__('general.phrase.file-not-exist', ['%s' => $file]));

            return new RedirectResponse($this->generateUrl('dashboard'));
        } catch (IOException $e) {
            $this->flashes()->error(Trans::__('general.phrase.file-not-readable', ['%s' => $file->getPath()]));

            return new RedirectResponse($this->generateUrl('dashboard'));
        }

        /** @var Form $form */
        $form = $this->createFormBuilder(FormType::class, $data)
            ->add('contents', TextareaType::class)
            ->getForm();

        // Handle the POST and check if it's valid.
        if ($request->isMethod('POST')) {
            return $this->handleEdit($request, $form, $file, $type);
        }

        // For 'related' files we might need to keep track of the current dirname on top of the namespace.
        if (dirname($file->getPath()) !== '') {
            $additionalPath = dirname($file->getPath()) . '/';
        } else {
            $additionalPath = '';
        }

        $context = [
            'form'           => $form->createView(),
            'filetype'       => $type,
            'file'           => $file->getPath(),
            'basename'       => basename($file->getPath()),
            'pathsegments'   => $this->getPathSegments(dirname($file->getPath())),
            'additionalpath' => $additionalPath,
            'namespace'      => $namespace,
            'write_allowed'  => true,
            'filegroup'      => $this->getFileGroup($filesystem, $file),
            'datechanged'    => date_format(new \DateTime('@' . $file->getTimestamp()), 'c'),
        ];

        return $this->render('@bolt/editfile/editfile.twig', $context);
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
    public function manage(Request $request, $namespace, $path)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        // Defaults
        $files       = [];
        $directories = [];
        $formview    = false;
        $uploadview  = true;

        $filesystem = $this->filesystem()->getFilesystem($namespace);

        if (!$filesystem->authorized($path)) {
            if (empty($path)) {
                $path = $namespace;
            }

            $this->flashes()->error(Trans::__('general.phrase.access-denied-permissions-view-file-directory', ['%s' => $path]));

            return new RedirectResponse($this->generateUrl('dashboard'));
        }

        if (!$this->isAllowed('files:uploads')) {
            $uploadview = false;
        }

        try {
            $visibility = $filesystem->getVisibility($path);
        } catch (FileNotFoundException $fnfe) {
            $visibility = false;
        }

        if ($visibility === 'public') {
            $validFolder = true;
        } elseif ($visibility === 'readonly') {
            $validFolder = true;
            $uploadview = false;
        } else {
            $this->flashes()->error(Trans::__('general.phrase.directory-not-found-writable', ['%s' => $path]));
            $formview = false;
            $validFolder = false;
        }

        if ($validFolder) {
            // Define the "Upload here" form.
            $form = $this->createFormBuilder(FormType::class)
                ->add(
                    'FileUpload',
                    FileType::class,
                    [
                        'label'    => false,
                        'multiple' => true,
                        'attr'     => [
                            'data-filename-placement' => 'inside',
                            'title'                   => Trans::__('general.phrase.select-file'),
                        ],
                    ]
                )
                ->getForm();

            // Handle the upload.
            if ($request->isMethod('POST')) {
                $this->handleUpload($request, $form, $namespace, $path);

                return $this->redirectToRoute('files', ['path' => $path, 'namespace' => $namespace]);
            }

            if ($uploadview !== false) {
                $formview = $form->createView();
            }

            $files = $filesystem->find()->in($path)->files()->depth(0)->toArray();
            $directories = $filesystem->find()->in($path)->directories()->depth(0)->toArray();
        }

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKeditor to insert a file.
        if (!$request->query->has('CKEditor')) {
            $twig = '@bolt/files/files.twig';
        } else {
            $twig = '@bolt/files_ck/files_ck.twig';
        }

        $context = [
            'path'         => $path,
            'files'        => $files,
            'directories'  => $directories,
            'pathsegments' => $this->getPathSegments($path),
            'form'         => $formview,
            'namespace'    => $namespace,
        ];

        return $this->render($twig, $context);
    }

    /**
     * Handle a file edit POST.
     *
     * @param Request $request
     * @param Form    $form
     * @param File    $file
     * @param string  $type
     *
     * @return JsonResponse
     */
    private function handleEdit(Request $request, Form $form, File $file, $type)
    {
        $form->submit($request->get($form->getName()));

        if ($form->isValid()) {
            $data = $form->getData();
            $contents = Input::cleanPostedData($data['contents']) . "\n";
            $result = ['ok' => true, 'msg' => 'Unhandled state.'];

            // Before trying to save a yaml file, check if it's valid.
            if ($type === 'yml') {
                $yamlparser = new Parser();
                try {
                    $yamlparser->parse($contents);
                } catch (ParseException $e) {
                    $result['ok'] = false;
                    $result['msg'] = Trans::__('page.file-management.message.save-failed-colon', ['%s' => $file->getPath()]) . $e->getMessage();
                }
            }

            if ($result['ok']) {
                // Remove ^M (or \r) characters from the file.
                $contents = str_ireplace("\x0D", '', $contents);

                try {
                    $file->update($contents);
                    $result['msg'] = Trans::__('page.file-management.message.save-success', ['%s' => $file->getPath()]);
                    $result['datechanged'] = $file->getCarbon()->toIso8601String();
                } catch (ExceptionInterface $e) {
                    $result['msg'] = Trans::__('page.file-management.message.save-failed-unknown', ['%s' => $file->getPath()]);
                }
            }
        } else {
            $result = [
                'ok'  => false,
                'msg' => Trans::__('page.file-management.message.save-failed-invalid-form', ['%s' => $file->getPath()]),
            ];
        }

        return $this->json($result);
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
        if (!$form->isValid()) {
            $this->flashes()->error(Trans::__('general.phrase.file-upload-failed'));

            return;
        }

        $files = $request->files->get($form->getName());
        $files = $files['FileUpload'];

        foreach ($files as $fileToProcess) {
            $fileToProcess = [
                'name'     => $fileToProcess->getClientOriginalName(),
                'tmp_name' => $fileToProcess->getPathName(),
            ];

            $originalFilename = $fileToProcess['name'];
            $filename = preg_replace('/[^a-zA-Z0-9_\\.]/', '_', basename($originalFilename));

            if ($this->app['filepermissions']->allowedUpload($filename)) {
                $this->processUpload($namespace, $path, $filename, $fileToProcess);
            } else {
                $extensionList = [];
                foreach ($this->app['filepermissions']->getAllowedUploadExtensions() as $extension) {
                    $extensionList[] = '<code>.' . htmlspecialchars($extension, ENT_QUOTES) . '</code>';
                }
                $extensionList = implode(' ', $extensionList);
                $this->flashes()->error(
                    Trans::__("File '%file%' could not be uploaded (wrong/disallowed file type). Make sure the file extension is one of the following:", ['%file%' => $filename])
                    . $extensionList
                );
            }
        }
    }

    /**
     * Process an individual file upload.
     *
     * @param string $namespace
     * @param string $path
     * @param string $filename
     * @param array  $fileToProcess
     *
     * @return void
     */
    private function processUpload($namespace, $path, $filename, array $fileToProcess)
    {
        $this->app['upload.namespace'] = $namespace;
        $handler = $this->app['upload'];
        $handler->setPrefix($path . '/');
        try {
            $result = $handler->process($fileToProcess);
        } catch (IOException $e) {
            $message = Trans::__('page.file-management.message.upload-not-writable', ['%TARGET%' => $namespace . '://']);
            $this->flashes()->error($message);

            return;
        }

        if ($result->isValid()) {
            $this->flashes()->info(
                Trans::__('page.file-management.message.upload-success', ['%file%' => $filename])
            );

            // Add the file to our stack.
            $this->app['stack']->add($path . '/' . $filename);
            $result->confirm();
        } else {
            foreach ($result->getMessages() as $message) {
                $this->flashes()->error((string) $message);
            }
        }
    }

    /**
     * Gather the 'similar' files, if present.
     *
     * i.e., if we're editing config.yml, we also want to check for
     * config.yml.dist and config_local.yml
     *
     * @param FilesystemInterface $filesystem
     * @param File                $file
     *
     * @return array
     */
    private function getFileGroup(FilesystemInterface $filesystem, File $file)
    {
        $basename = str_replace('.yml', '', str_replace('_local', '', $file->getPath()));
        $filegroup = [];
        if ($filesystem->has($basename . '.yml')) {
            $filegroup[] = basename($basename . '.yml');
        }
        if ($filesystem->has($basename . '_local.yml')) {
            $filegroup[] = basename($basename . '_local.yml');
        }

        return $filegroup;
    }

    /**
     * Get the path segments, so we can show the path.
     *
     * @param string $path
     *
     * @return array
     */
    private function getPathSegments($path)
    {
        $pathsegments = [];
        $cumulative = '';
        if (!empty($path)) {
            foreach (explode('/', $path) as $segment) {
                $cumulative .= $segment . '/';
                $pathsegments[$cumulative] = $segment;
            }
        }

        return $pathsegments;
    }
}
