<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

return Route::inertia('/', 'Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
    'laravelVersion' => Application::VERSION,
    'phpVersion' => PHP_VERSION,
]);
