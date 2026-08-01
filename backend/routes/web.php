<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// API uniquement : la racine redirige vers le health-check.
Route::redirect('/', '/up');
