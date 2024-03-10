<?php

namespace Slowlyo\OwlLogViewer\Http\Controllers;

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
        if ($l = $this->request->input('l')) {
            if (is_file($l)) {
                $this->log_viewer->setFile($l);
            }
        }

        try {
            if ($this->request->input('dl')) {
                return $this->download($this->pathFromInput('dl'));
            } else if ($this->request->has('clean')) {
                app('files')->put($this->pathFromInput('clean'), '');

                return $this->response()->success();
            } else if ($this->request->has('del')) {
                app('files')->delete($this->pathFromInput('del'));

                return $this->response()->success();
            } else if ($this->request->has('delall')) {
                $files = ($this->log_viewer->getFolderName())
                    ? $this->log_viewer->getFolderFiles(true)
                    : $this->log_viewer->getFiles(true);
                foreach ($files as $file) {
                    app('files')->delete($this->log_viewer->pathToLogFile($file));
                }

                return $this->response()->success();
            }
        } catch (\Throwable $e) {
            admin_abort($e->getMessage());
        }

        $files = $this->log_viewer->getFiles();

        foreach ($files as &$f) {
            $search = config('logviewer.storage_path') ?: storage_path('logs');

            $f = [
                'label' => str_replace($search . '/', '', $f),
                'value' => $f,
            ];
        }

        $data = [
            'logs'           => $this->log_viewer->all(),
            'folders'        => $this->log_viewer->getFolders(),
            'current_folder' => $this->log_viewer->getFolderName(),
            'folder_files'   => $folderFiles,
            'files'          => $files,
            'current_file'   => $this->log_viewer->getFileName(),
            'standardFormat' => true,
            'structure'      => $this->log_viewer->foldersAndFiles(),
            'storage_path'   => $this->log_viewer->getStoragePath(),

        ];

        if ($this->request->wantsJson()) {
            return $this->response()->success($data);
        }

        if (is_array($data['logs']) && count($data['logs']) > 0) {
            $firstLog = reset($data['logs']);
            if ($firstLog) {
                if (!$firstLog['context'] && !$firstLog['level']) {
                    $data['standardFormat'] = false;
                }
            }
        }

        return $this->response()->success(['view' => app('view')->make($this->view_log, $data)->render()]);
    }

    public function index()
    {
        $schema = $this->basePage()->css([
            '.cxd-Tree-itemArrowPlaceholder' => ['display' => 'none'],
            '.cxd-Tree-itemLabel'            => ['padding-left' => '0 !important'],
        ])->body([
            amis()->Service()->name('log_viewer_service')->api([
                'url'    => '/owl-log-viewer?l=${l}',
                'method' => 'post',
                'data'   => [],
            ])->body([
                amis()->Page()->body([
                    amis()->Flex()->items([
                        amis()->Card()->className('w-1/4 mr-5 mb-0 min-w-xs')->body([
                            amis()->ButtonToolbar()->className('mb-3')->buttons([
                                amis()->Action()
                                    ->level('success')
                                    ->label('下载')
                                    ->icon('fa fa-download')
                                    ->actionType('download')
                                    ->reload('log_viewer_service')
                                    ->api('post:/owl-log-viewer?dl=${l}'),
                                amis()->AjaxAction()
                                    ->level('warning')
                                    ->label('清空')
                                    ->confirmText('该操作将清空日志文件')
                                    ->icon('fa fa-file-alt')
                                    ->reload('log_viewer_service')
                                    ->api('post:/owl-log-viewer?clean=${l}'),
                                amis()->AjaxAction()
                                    ->level('danger')
                                    ->label('删除')
                                    ->confirmText('该操作将删除日志文件')
                                    ->icon('fa fa-trash-alt')
                                    ->reload('log_viewer_service')
                                    ->api('post:/owl-log-viewer?del=${l}'),
                            ]),
                            amis()->TreeControl('l')->source('${files}')->searchable()->selectFirst(),
                        ]),
                        amis()->CRUDTable()
                            ->className('w-3/4')
                            ->source('${logs}')
                            ->tableClassName('pt-3')
                            ->footable()
                            ->columns([
                                amis()->TableColumn('level', 'Level')
                                    ->classNameExpr('font-bold text-${level_class}')
                                    ->width(200)
                                    ->sortable(),
                                amis()->TableColumn('date', 'Date')->width(200)->sortable(),
                                amis()->TableColumn('text', 'Content')
                                    ->type('tpl')
                                    ->tpl('${ text |truncate:200 }')
                                    ->popOver(
                                        amis()->SchemaPopOver()
                                            ->trigger('hover')
                                            ->showIcon(false)
                                            ->position('right-top')
                                            ->body(
                                                amis()->Code()->value('${text | raw}')
                                            )
                                    ),
                                amis()->Operation()->label('Stack')->buttons([
                                    amis()->DialogAction()->label('View')->level('link')->dialog(
                                        amis()->Dialog()
                                            ->title('Stack')
                                            ->size('xl')
                                            ->actions([])
                                            ->closeOnOutside()
                                            ->body([
                                                amis()->Code()->value('${stack | raw}'),
                                            ])
                                    ),
                                ])->set('width', 120),
                            ]),
                    ]),
                ]),
            ]),
        ]);

        return $this->response()->success($schema);
    }

    /**
     * @param string $input_string
     *
     * @return string
     * @throws \Exception
     */
    private function pathFromInput($input_string)
    {
        return $this->log_viewer->pathToLogFile($this->request->input($input_string));
    }

    /**
     * @param string $data
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function download($data)
    {
        return response()->download($data);
    }
}
