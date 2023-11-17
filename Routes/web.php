<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\Consignment\Http\Controllers\ConsignmentCrudTest;
use Modules\Consignment\Http\Controllers\ConsignmentController;

/*
|--------------------------------------------------------------------------
| Consignment Module Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
| Web routes define routes that are for your web interface.
| These routes are assigned the web middleware group,
| which provides features like session state and CSRF protection.
|
|
| Difference Between Public And Private Routes (Dashboard)
| - Public Routes are likely to be available for the public. These routes usually don't use "dashboard" as a prefix and most of the time don't require permissions to be accessible.
| - Private Routes are routes that use "dashboard" as a prefix. These routes are means to be administrative routes and design all routes that take to dashboard interfaces.
| - NOTE: Use "Private" routes to require authentication
|
| For more about securing routes see: https://my.nexopos.com/en/documentation/developpers-guides/how-to-register-routes-for-modules
|
*/

// need this to make sure routes require authentication, and SubstituteBindings to fix model binding
Route::prefix( 'dashboard' )->group( function() {
    Route::middleware([
        SubstituteBindings::class,
        Authenticate::class, // <= will be accessible only if the user is authenticated.
    ])->group( function() {

        Route::get( '/consignment/index', [ ConsignmentController::class, 'index' ]);
        Route::get( '/consignment/faq', [ ConsignmentController::class, 'faq' ]);
        Route::get( '/consignment/products', [ ConsignmentController::class, 'productList' ]);
        Route::get( '/consignment/products/create', [ ConsignmentController::class, 'createProduct' ]);
        Route::get( '/consignment/products/edit/{product}', [ ConsignmentController::class, 'editProduct' ]);

    });
});

//// Bare Crud Test Routes
//Route::get( '/dashboard/Consignment/flights', [ ConsignmentCrudTest::class, 'flightList' ]);
//Route::get( '/dashboard/Consignment/flights/create', [ ConsignmentCrudTest::class, 'createFlight' ]);
//Route::get( '/dashboard/Consignment/flights/edit/{flight}', [ ConsignmentCrudTest::class, 'editFlight' ]);
