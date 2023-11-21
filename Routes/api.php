<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Routing\Middleware\SubstituteBindings;
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

// Routes in this file don't seem to be picked up by NexoPOS ...
