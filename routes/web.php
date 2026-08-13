<?php

use App\Http\Controllers\Benchmark\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response('Laravel PDF Benchmark. Use `php artisan benchmark:review <run>` for local fidelity review.'));

Route::prefix('benchmark-review/{run}')->name('benchmark.')->group(function (): void {
    Route::get('/', [ReviewController::class, 'show'])->name('review');
    Route::post('/', [ReviewController::class, 'update'])->name('review.update');
    Route::get('/artifact/{path}', [ReviewController::class, 'artifact'])->where('path', '.*')->name('artifact');
});
