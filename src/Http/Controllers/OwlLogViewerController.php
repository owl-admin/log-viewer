<?php

namespace Slowlyo\OwlLogViewer\Http\Controllers;

use Slowlyo\OwlAdmin\Admin;
use Illuminate\Support\Facades\Crypt;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Rap2hpoutre\LaravelLogViewer\LaravelLogViewer;

class OwlLogViewerController extends AdminController
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var LaravelLogViewer
     */
    private $log_viewer;

    /**
     * @var string
     */
    protected $view_log = 'laravel-log-viewer::log';

    /**
     * LogViewerController constructor.
     */
    public function __construct()
    {
        $this->log_viewer = new LaravelLogViewer();
        $this->request    = app('request');
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function view()
    {
        $folderFiles = [];
        if ($this->request->input('f')) {
            $this->log_viewer->setFolder(Crypt::decrypt($this->request->input('f')));
            $folderFiles = $this->log_viewer->getFolderFiles(true);
        }
        if ($this->request->input('l')) {
            $this->log_viewer->setFile(Crypt::decrypt($this->request->input('l')));
        }

        if ($early_return = $this->earlyReturn()) {
            return $early_return;
        }

        $data = [
            'logs'           => $this->log_viewer->all(),
            'folders'        => $this->log_viewer->getFolders(),
            'current_folder' => $this->log_viewer->getFolderName(),
            'folder_files'   => $folderFiles,
            'files'          => $this->log_viewer->getFiles(true),
            'current_file'   => $this->log_viewer->getFileName(),
            'standardFormat' => true,
            'structure'      => $this->log_viewer->foldersAndFiles(),
            'storage_path'   => $this->log_viewer->getStoragePath(),

        ];

        // if ($this->request->wantsJson()) {
        //     return $data;
        // }

        if (is_array($data['logs']) && count($data['logs']) > 0) {
            $firstLog = reset($data['logs']);
            if ($firstLog) {
                if (!$firstLog['context'] && !$firstLog['level']) {
                    $data['standardFormat'] = false;
                }
            }
        }

        return app('view')->make($this->view_log, $data);
    }

    public function index()
    {
        $schema = $this->basePage()->bodyClassName('custom-page')->css([
            '.bg-\[var\(--owl-body-bg\)\]' => ['background' => 'white'],
            '.custom-page'                 => ['height' => 'calc(100vh - 65px)', 'overflow' => 'hidden'],
            '.p-5'                         => ['padding' => '0 !important'],
        ])->body([
            amis()->IFrame()
                ->className('my-iframe')
                ->src('/' . Admin::config('admin.route.prefix') . '/owl-log-viewer'),
        ]);

        return $this->response()->success($schema);
    }

    /**
     * @return bool|mixed
     * @throws \Exception
     */
    private function earlyReturn()
    {
        if ($this->request->input('f')) {
            $this->log_viewer->setFolder(Crypt::decrypt($this->request->input('f')));
        }

        if ($this->request->input('dl')) {
            return $this->download($this->pathFromInput('dl'));
        } else if ($this->request->has('clean')) {
            app('files')->put($this->pathFromInput('clean'), '');
            return $this->redirect(url()->previous());
        } else if ($this->request->has('del')) {
            app('files')->delete($this->pathFromInput('del'));
            return $this->redirect($this->request->url());
        } else if ($this->request->has('delall')) {
            $files = ($this->log_viewer->getFolderName())
                ? $this->log_viewer->getFolderFiles(true)
                : $this->log_viewer->getFiles(true);
            foreach ($files as $file) {
                app('files')->delete($this->log_viewer->pathToLogFile($file));
            }
            return $this->redirect($this->request->url());
        }
        return false;
    }

    /**
     * @param string $input_string
     *
     * @return string
     * @throws \Exception
     */
    private function pathFromInput($input_string)
    {
        return $this->log_viewer->pathToLogFile(Crypt::decrypt($this->request->input($input_string)));
    }

    /**
     * @param $to
     *
     * @return mixed
     */
    private function redirect($to)
    {
        if (function_exists('redirect')) {
            return redirect($to);
        }

        return app('redirect')->to($to);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    private function download($data)
    {
        if (function_exists('response')) {
            return response()->download($data);
        }

        // For laravel 4.2
        return app('\Illuminate\Support\Facades\Response')->download($data);
    }
}
