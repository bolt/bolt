<?php

namespace Bolt\Controller\Backend;

use Bolt\Exception\FileNotStackableException;
use Bolt\Filesystem\Exception\ExceptionInterface;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Handler\HandlerInterface;
use Bolt\Helpers\Input;
use Bolt\Helpers\Str;
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
        ;

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
        $file = $this->filesystem()->getFile("$namespace://$file");

        if (!$file->authorized()) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-permissions-edit-file', ['%s' => $file->getPath()]));

            return $this->redirectToRoute('dashboard');
        }

        try {
            $contents = $file->read();
        } catch (FileNotFoundException $e) {
            $this->flashes()->error(Trans::__('general.phrase.file-not-exist', ['%s' => $file->getPath()]));

            return $this->redirectToRoute('dashboard');
        } catch (IOException $e) {
            $this->flashes()->error(Trans::__('general.phrase.file-not-readable', ['%s' => $file->getPath()]));

            return $this->redirectToRoute('dashboard');
        }

        /** @var Form $form */
        $form = $this->createFormBuilder(FormType::class, compact('contents'))
            ->add('contents', TextareaType::class)
            ->getForm();

        // Handle the POST and check if it's valid.
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            return $this->handleEdit($form, $file);
        }

        $context = [
            'form'              => $form->createView(),
            'file'              => $file,
            'write_allowed'     => true,
            'related'           => $this->getRelatedFiles($file),
            'datechanged'       => $file->getCarbon()->toIso8601String(),
            'codeMirrorPlugins' => $this->getCodeMirrorPlugins($file),
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
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function manage(Request $request, $namespace, $path)
    {
        $directory = $this->filesystem()->getDir("$namespace://$path");

        if (!$directory->authorized()) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-permissions-view-file-directory', ['%s' => $directory->getPath()]));

            return $this->redirectToRoute('dashboard');
        }

        $form = null;
        if (!$request->query->has('CKEditor') && $this->isAllowed('files:uploads')) {
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
                            'accept'                  => '.' . implode(',.', $this->getOption('general/accept_file_types')),
                        ],
                    ]
                )
                ->getForm();

            // Handle the upload.
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $this->handleUpload($form, $directory);

                return $this->redirectToRoute('files', ['path' => $directory->getPath(), 'namespace' => $directory->getMountPoint()]);
            }
        }

        $it = $directory->getContents();
        $files = array_filter($it, function (HandlerInterface $handler) {
            return $handler->isFile();
        });
        $directories = array_filter($it, function (HandlerInterface $handler) {
            return $handler->isDir();
        });

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKeditor to insert a file.
        $template = $request->query->has('CKEditor') ? '@bolt/files_ck/files_ck.twig' : '@bolt/files/files.twig';

        $context = [
            'directory'    => $directory,
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
     *
     * @return JsonResponse
     */
    private function handleEdit(FormInterface $form, FileInterface $file)
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
        if ($file->getType() === 'yaml') {
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
     * @param FormInterface      $form
     * @param DirectoryInterface $directory
     */
    private function handleUpload(FormInterface $form, DirectoryInterface $directory)
    {
        if (!$form->isValid()) {
            $this->flashes()->error(Trans::__('general.phrase.file-upload-failed'));

            return;
        }

        /** @var UploadedFile[] $files */
        $files = $form->getData()['FileUpload'];
        $permissions = $this->app['filepermissions'];

        foreach ($files as $fileToProcess) {
            $fileToProcess = [
                'name'     => $fileToProcess->getClientOriginalName(),
                'tmp_name' => $fileToProcess->getPathname(),
            ];

            $originalFilename = $fileToProcess['name'];
            $filename = preg_replace('/[^a-zA-Z0-9_\\.]/', '_', basename($originalFilename));

            try {
                $isAllowed = $permissions->allowedUpload($filename);
            } catch (IOException $e) {
                $this->flashes()->error($e->getMessage());

                continue;
            }

            if ($isAllowed) {
                $this->processUpload($directory, $filename, $fileToProcess);
            } else {
                $extensionList = [];
                foreach ($permissions->getAllowedUploadExtensions() as $extension) {
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
     * @param DirectoryInterface $directory
     * @param string             $filename
     * @param array              $fileToProcess
     */
    private function processUpload(DirectoryInterface $directory, $filename, array $fileToProcess)
    {
        $this->app['upload.namespace'] = $directory->getMountPoint();
        $handler = $this->app['upload'];
        $handler->setPrefix($directory->getPath() . '/');
        try {
            $result = $handler->process($fileToProcess);
        } catch (IOException $e) {
            $message = Trans::__('page.file-management.message.upload-not-writable', ['%TARGET%' => $directory->getPath()]);
            $this->flashes()->error($message);

            return;
        }

        if ($result->isValid()) {
            $this->flashes()->info(
                Trans::__('page.file-management.message.upload-success', ['%file%' => $filename])
            );

            // Add the file to our stack.
            try {
                $this->app['stack']->add($directory->getFile($filename));
            } catch (FileNotStackableException $e) {
                // Doesn't matter. Just trying to help the user.
            }

            $result->confirm();
        } else {
            foreach ($result->getMessages() as $message) {
                $this->flashes()->error((string) $message);
            }
        }
    }

    /**
     * Gather related (present) files.
     *
     * Matches: foo(_local)?\.*(.dist)?
     *
     * i.e., if we're editing config.yml, we also want to check for
     * config.yml.dist and config_local.yml
     *
     * @param FileInterface $file
     *
     * @return FileInterface[]
     */
    private function getRelatedFiles(FileInterface $file)
    {
        // Match foo(_local).*(.dist)
        $base = $file->getFilename();
        if (Str::endsWith($base, '.dist')) {
            $base = substr($base, 0, -5);
        }
        $ext = pathinfo($base, PATHINFO_EXTENSION);
        $base = Str::replaceLast(".$ext", '', $base);
        $base = Str::replaceLast('_local', '', $base);

        $dir = $file->getParent();
        $related = [];
        foreach ([".$ext", "_local.$ext", ".$ext.dist"] as $tail) {
            $f = $dir->getFile($base . $tail);
            if ($f->getFilename() !== $file->getFilename() && $f->exists()) {
                $related[] = $f;
            }
        }

        return $related;
    }

    private function getCodeMirrorPlugins(FileInterface $file)
    {
        switch (strtolower($file->getExtension())) {
            case 'twig':
            case 'html':
            case 'htm':
                return ['xml', 'javascript', 'css', 'htmlmixed'];
            case 'php':
                return ['matchbrackets', 'javascript', 'css', 'htmlmixed', 'clike', 'php'];
            case 'yml':
            case 'yaml':
                return ['yaml'];
            case 'md':
            case 'markdown':
                return ['markdown'];
            case 'css':
            case 'less':
                return ['css'];
            case 'js':
                return ['javascript'];
            default:
                return [];
        }
    }
}
