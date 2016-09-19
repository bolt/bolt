<?php

namespace Bolt\Controller\Backend;

use Bolt\Filesystem\Exception\ExceptionInterface;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Handler\HandlerInterface;
use Bolt\Helpers\Input;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @return \Symfony\Component\HttpFoundation\Response
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

            return $this->redirectToRoute('dashboard');
        }

        try {
            /** @var FileInterface $file */
            $file = $filesystem->get($file);
            $type = Lib::getExtension($file->getPath());
            $data = ['contents' => $file->read()];
        } catch (FileNotFoundException $e) {
            $this->flashes()->error(Trans::__('general.phrase.file-not-exist', ['%s' => $file]));

            return $this->redirectToRoute('dashboard');
        } catch (IOException $e) {
            $this->flashes()->error(Trans::__('general.phrase.file-not-readable', ['%s' => $file->getPath()]));

            return $this->redirectToRoute('dashboard');
        }

        /** @var Form $form */
        $form = $this->createFormBuilder(FormType::class, $data)
            ->add('contents', TextareaType::class)
            ->getForm();

        // Handle the POST and check if it's valid.
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            return $this->handleEdit($form, $file, $type);
        }

        // For 'related' files we might need to keep track of the current dirname on top of the namespace.
        $additionalPath = $file->getDirname() !== '' ? $file->getDirname() . '/' : '';

        $context = [
            'form'           => $form->createView(),
            'filetype'       => $type,
            'file'           => $file->getPath(),
            'basename'       => $file->getFilename(),
            'pathsegments'   => $this->getPathSegments($file->getDirname()),
            'additionalpath' => $additionalPath,
            'namespace'      => $namespace,
            'write_allowed'  => true,
            'filegroup'      => $this->getFileGroup($file),
            'datechanged'    => $file->getCarbon()->toIso8601String(),
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

        $filesystem = $this->filesystem()->getFilesystem($namespace);

        if (!$filesystem->authorized($path)) {
            if (empty($path)) {
                $path = $namespace;
            }

            $this->flashes()->error(Trans::__('general.phrase.access-denied-permissions-view-file-directory', ['%s' => $path]));

            return $this->redirectToRoute('dashboard');
        }

        $form = null;
        if ($this->isAllowed('files:uploads')) {
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
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $this->handleUpload($form, $namespace, $path);

                return $this->redirectToRoute('files', ['path' => $path, 'namespace' => $namespace]);
            }
        }

        $it = $filesystem->listContents($path);
        $files = array_filter($it, function(HandlerInterface $handler) { return $handler->isFile(); });
        $directories = array_filter($it, function(HandlerInterface $handler) { return $handler->isDir(); });

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKeditor to insert a file.
        $template = $request->query->has('CKEditor') ? '@bolt/files_ck/files_ck.twig' : '@bolt/files/files.twig';

        $context = [
            'namespace'    => $namespace,
            'path'         => $path,
            'pathsegments' => $this->getPathSegments($path),
            'files'        => $files,
            'directories'  => $directories,
            'form'         => $form ? $form->createView() : false,
        ];

        return $this->render($template, $context);
    }

    /**
     * Handle a file edit POST.
     *
     * @param FormInterface $form
     * @param FileInterface $file
     * @param string        $type
     *
     * @return JsonResponse
     */
    private function handleEdit(FormInterface $form, FileInterface $file, $type)
    {
        if (!$form->isValid()) {
            return $this->json([
                'ok'  => false,
                'msg' => Trans::__('page.file-management.message.save-failed-invalid-form', ['%s' => $file->getPath()]),
            ]);
        }

        $data = $form->getData();
        $contents = Input::cleanPostedData($data['contents']) . "\n";
        // Remove ^M and \r characters from the file.
        $contents = str_ireplace("\x0D", '', $contents);

        // Before trying to save a yaml file, check if it's valid.
        if ($type === 'yml') {
            $yamlparser = new Parser();
            try {
                $yamlparser->parse($contents);
            } catch (ParseException $e) {
                return $this->json([
                    'ok'  => false,
                    'msg' => Trans::__('page.file-management.message.save-failed-colon', ['%s' => $file->getPath()]) . $e->getMessage(),
                ]);
            }
        }

        try {
            $file->update($contents);
            $result['msg'] = Trans::__('page.file-management.message.save-success', ['%s' => $file->getPath()]);
            $result['datechanged'] = $file->getCarbon()->toIso8601String();
        } catch (ExceptionInterface $e) {
            return $this->json([
                'ok'  => false,
                'msg' => Trans::__('page.file-management.message.save-failed-unknown', ['%s' => $file->getPath()]),
            ]);
        }

        return $this->json([
            'ok'          => true,
            'msg'         => Trans::__('page.file-management.message.save-success', ['%s' => $file->getPath()]),
            'datechanged' => $file->getCarbon()->toIso8601String(),
        ]);
    }

    /**
     * Handle the upload POST.
     *
     * @param FormInterface $form
     * @param string        $namespace The filesystem namespace
     * @param string        $path      The path prefix
     */
    private function handleUpload(FormInterface $form, $namespace, $path)
    {
        if (!$form->isValid()) {
            $this->flashes()->error(Trans::__('general.phrase.file-upload-failed'));

            return;
        }

        /** @var UploadedFile[] $files */
        $files = $form->getData()['FileUpload'];

        foreach ($files as $fileToProcess) {
            $fileToProcess = [
                'name'     => $fileToProcess->getClientOriginalName(),
                'tmp_name' => $fileToProcess->getPathname(),
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
     * @param FileInterface $file
     *
     * @return array
     */
    private function getFileGroup(FileInterface $file)
    {
        $dir = $file->getParent();

        $basename = str_replace('_local', '', $file->getFilename('.yml'));

        $filegroup = [];
        if ($dir->getFile($basename . '.yml')->exists()) {
            $filegroup[] = $basename . '.yml';
        }
        if ($dir->getFile($basename . '_local.yml')->exists()) {
            $filegroup[] = $basename . '_local.yml';
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
