<?php

use Slowlyo\OwlLogViewer\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::post('owl-log-viewer', [Controllers\OwlLogViewerController::class, 'view']);
Route::get('owl-log-viewer', [Controllers\OwlLogViewerController::class, 'index']);
