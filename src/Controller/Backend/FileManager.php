<?php

namespace Bolt\Controller\Backend;

use Bolt\Common\Str;
use Bolt\Exception\FileNotStackableException;
use Bolt\Filesystem\Exception\ExceptionInterface;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\File;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Listing;
use Bolt\Form\FormType\FileEditType;
use Bolt\Form\FormType\FileUploadType;
use Bolt\Form\Validator\Constraints;
use Bolt\Helpers\Input;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Webmozart\PathUtil\Path;

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
        /** @var File $file */
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

        $data = compact('contents');
        $options = [
            'write_allowed'        => true,
            'contents_allow_empty' => $file->getExtension() !== 'yml' ?: false,
            'contents_constraints' => $file->getExtension() === 'yml' ? [new Constraints\Yaml()] : [],
        ];
        /** @var Form $form */
        $form = $this->createFormBuilder(FileEditType::class, $data, $options)
            ->getForm()
        ;

        // Handle the POST and check if it's valid.
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $form->get('save')->isClicked()) {
            return $this->handleEdit($form, $file);
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            return $this->json([
                'ok'  => false,
                'msg' => Trans::__('page.file-management.message.save-failed-invalid-form', ['%s' => $file->getPath()]),
            ]);
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
        $fullPath = "$namespace://$path";
        $directory = $this->filesystem()->getDir($fullPath);
        $listing = new Listing($directory);
        $showHidden = $this->isAllowed('files:hidden');

        if (!$listing->isAuthorized()) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-permissions-view-file-directory', ['%s' => $fullPath]));

            return $this->redirectToRoute('dashboard');
        }
        try {
            $directories = $listing->getDirectories($showHidden);
            $files = $listing->getFiles($showHidden);
        } catch (IOException $e) {
            $this->flashes()->error(Trans::__('page.file-management.message.folder-not-found', ['%s' => $path]));

            return $this->redirectToRoute('dashboard');
        }

        $form = null;
        if (!$request->query->has('CKEditor') && $this->isAllowed('files:uploads')) {
            // Define the "Upload here" form.
            $options = ['accept' => '.' . implode(',.', $this->getOption('general/accept_file_types'))];
            $form = $this->createFormBuilder(FileUploadType::class, null, $options)
                ->getForm()
            ;

            // Handle the upload.
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->handleUpload($form, $directory);

                return $this->redirectToRoute('files', ['path' => $path, 'namespace' => $namespace]);
            }
            if ($form->isSubmitted() && !$form->isValid()) {
                $this->flashes()->error(Trans::__('general.phrase.file-upload-failed'));
            }
        }

        // Select the correct template to render this. If we've got 'CKEditor' in the title, it's a dialog
        // from CKEditor to insert a file.
        $template = $request->query->has('CKEditor') ? '@bolt/files_ck/files_ck.twig' : '@bolt/files/files.twig';

        $context = [
            'directory'   => $directory,
            'directories' => $directories,
            'files'       => $files,
            'form'        => $form ? $form->createView() : false,
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
        /** @var UploadedFile[] $files */
        $files = $form->get('select')->getData();
        $permissions = $this->app['filepermissions'];

        foreach ($files as $fileToProcess) {
            $fileToProcess = [
                'name'     => $fileToProcess->getClientOriginalName(),
                'tmp_name' => $fileToProcess->getPathname(),
            ];

            $originalFilename = $fileToProcess['name'];
            $filename = basename($originalFilename);
            $filename = $this->app['upload.sanitizer']->slugify($filename, $this->app['config']->get('general/upload/replacement', '-'));

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
        $ext = Path::getExtension($base);
        $base = Path::getFilenameWithoutExtension($base);
        $base = Str::replaceLast($base, '_local', '');

        $dir = $file->getParent();
        $related = [];
        foreach ([".$ext", "_local.$ext"] as $tail) {
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
                $plugins = ['overlay', 'htmltwig', 'xml', 'javascript', 'css', 'htmlmixed', 'twig'];
                break;
            case 'php':
                $plugins = ['matchbrackets', 'javascript', 'css', 'htmlmixed', 'clike', 'php'];
                break;
            case 'yml':
            case 'yaml':
                $plugins = ['yaml'];
                break;
            case 'md':
            case 'markdown':
                $plugins = ['markdown'];
                break;
            case 'css':
            case 'less':
                $plugins = ['css'];
                break;
            case 'js':
                $plugins = ['javascript'];
                break;
            default:
                $plugins = [];
        }

        return array_merge($plugins, ['fold/foldcode', 'fold/foldgutter', 'fold/indent-fold']);
    }
}
