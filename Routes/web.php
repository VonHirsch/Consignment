<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\ProductsController;
use App\Http\Controllers\Dashboard\ReportsController;
use App\Http\Middleware\Authenticate;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
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

        // Indexes / Static pages
        Route::get( '/consignment/index', [ ConsignmentController::class, 'index' ])->name( ns()->routeName( 'ns.consignment.index' ) );
        Route::get( '/consignment/index-labels', [ ConsignmentController::class, 'indexLabels' ])->name( ns()->routeName( 'ns.consignment.index-labels' ) );
        Route::get( '/consignment/index-admin', [ ConsignmentController::class, 'indexAdmin' ])->name( ns()->routeName( 'ns.consignment.index-admin' ) );
        Route::get( '/consignment/index-payouts', [ ConsignmentController::class, 'indexPayouts' ])->name( ns()->routeName( 'ns.consignment.index-payouts' ) );
        Route::get( '/consignment/faq', [ ConsignmentController::class, 'faq' ]);

        // Products CRUD
        Route::get( '/consignment/products', [ ConsignmentController::class, 'productList' ]);
        Route::get( '/consignment/products-all', [ ConsignmentController::class, 'productListAll' ]);
        Route::get( '/consignment/products/create', [ ConsignmentController::class, 'createProduct' ]);
        Route::get( '/consignment/products/edit/{product}', [ ConsignmentController::class, 'editProduct' ]);

        // ConsignorSettings CRUD
        Route::get( '/consignment/consignorsettings', [ ConsignmentController::class, 'consignorSettingsList' ])->name( ns()->routeName( 'ns.consignorsettings.list' ) );
        Route::get( '/consignment/consignorsettings/create', [ ConsignmentController::class, 'createConsignorSettings' ]);
        Route::get( '/consignment/consignorsettings/edit/{consignorSettings}', [ ConsignmentController::class, 'editConsignorSettings' ]);

        // Payment Prefs
        Route::get( '/consignment/consignor-edit', [ ConsignmentController::class, 'editPaymentPrefs' ]);

        // Print Labels
        Route::get( '/consignment/print-labels', [ ConsignmentController::class, 'printLabels' ]);
        Route::post( '/consignment/products/search', [ ConsignmentController::class, 'searchProducts' ]);
        Route::post( '/consignment/products/all', [ ConsignmentController::class, 'allProducts' ]);

        // Admin Print Labels
        Route::get( '/consignment/print-labels-by-seller', [ ConsignmentController::class, 'printLabelsBySeller' ]);
        Route::get( '/consignment/print-labels-by-item', [ ConsignmentController::class, 'printLabelsByItem' ]);

        // Search Sellers
        Route::post( '/consignment/sellers/search', [ ConsignmentController::class, 'searchSellers' ]);

        // Contact Sellers
        Route::get( '/consignment/contact-sellers', [ ConsignmentController::class, 'contactSellers' ]);

        // Search Product by Barcode
        Route::post( '/consignment/barcode/search', [ ConsignmentController::class, 'searchBarcodes' ])->name( ns()->routeName( 'ns.consignor.barcode.search' ) );

        // Get consignor contact info via product id
        Route::post( '/consignment/consignor-contact-info', [ ConsignmentController::class, 'getConsignorContactInfo' ]);

        // Reports
        Route::get( '/consignment/reports/consignor-sales', [ ConsignmentController::class, 'consignorSalesReport' ]);
        Route::post( '/consignment/reports/consignor-sales-report', [ ConsignmentController::class, 'getConsignorSalesReport' ]);

        // manage.options permissions only - these aren't used atm
        Route::get( '/consignment/options', [ ConsignmentController::class, 'showModuleOptionsPage' ]);

    });
});
