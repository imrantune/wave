<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Wave\Facades\Wave;
use Wave\Plugins\ExcelImporter\Pages\ImportExcel;

// Wave routes
Wave::routes();

Route::get('role', function(){
    dd(\App\Models\User::find(2)->roles);
});

Route::post('/admin/import-excel', [ImportExcel::class, 'import'])->name('filament.pages.ImportExcel');
Route::get('/admin/import-excel', [ImportExcel::class, 'render'])->name('filament.pages.ImportExcel');
