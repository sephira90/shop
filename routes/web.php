<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/admin/{path?}', 'app')->where('path', '.*');
Route::view('/{path?}', 'app')->where('path', '^(?!api|up).*$');
