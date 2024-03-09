<?php

namespace Slowlyo\OwlLogViewer;

use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;

class OwlLogViewerServiceProvider extends ServiceProvider
{
    protected $menu = [
        [
            'title' => '系统日志',
            'url'   => '/owl-log-viewer',
            'icon'  => 'octicon:log-24',
        ],
    ];

	public function settingForm()
	{
	    return $this->baseSettingForm()->body([
            TextControl::make()->name('value')->label('Value')->required(true),
	    ]);
	}
}
