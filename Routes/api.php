<?php

use Illuminate\Support\Facades\Route;
use Modules\Consignment\Http\Controllers\ConsignmentController;

/*
|--------------------------------------------------------------------------
| Consignment Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
| API routes are stateless and are assigned the api middleware group.
|
*/

Route::get( 'consignment/feed', [ ConsignmentController::class, 'salesFeed' ] );
