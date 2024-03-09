<?php

use Slowlyo\OwlLogViewer\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('owl-log-viewer-view', [Controllers\OwlLogViewerController::class, 'view']);
Route::get('owl-log-viewer', [Controllers\OwlLogViewerController::class, 'index']);
