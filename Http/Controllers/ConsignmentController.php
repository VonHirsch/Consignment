<?php

/**
 * Consignment Controller
 * @since 1.0
 * @package modules/Consignment
 **/

namespace Modules\Consignment\Http\Controllers;
use App\Classes\Currency;
use App\Classes\Hook;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductUnitQuantity;
use App\Models\User;
use App\Services\Users;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\DashboardController;
use App\Services\OrdersService;
use App\Services\ReportService;
use App\Services\ProductService;
use Exception;
use Modules\Consignment\ConsignmentModule;
use Modules\Consignment\Crud\ConsignorSettingsCrud;
use Modules\Consignment\Crud\ProductCrud;
use Modules\Consignment\Models\ConsignorSettings;
use Modules\Consignment\Settings\ConsignmentSettings;

// https://my.nexopos.com/en/documentation/crud-api/how-to-create-a-crud-component

class ConsignmentController extends DashboardController
{
    public function __construct(
        protected OrdersService $ordersService,
        protected ReportService $reportService,
        protected ProductService $productService,
    ) {
        parent::__construct();
    }

    // ------------------------------------------------------
    // Product Crud
    // ------------------------------------------------------

    // List only products the user authored
    public function productList()
    {
        ns()->restrict([ 'nexopos.consignment' ]);
        return ProductCrud::table([
            'title' => __( 'My Items' ),
            'description' =>  __( 'To add an item, click the round plus button below' ),
            'queryParams' => [
                'author' => Auth::id(),
            ],
        ]);
    }

    // List all products
    public function productListAll()
    {
        ns()->restrict([ 'nexopos.consignment.admin-features' ]);
        return ProductCrud::table([
            'title' => __( 'All Items' ),
            'description' =>  __( 'All consignor items' ),
        ]);
    }

    public function createProduct()
    {
        ns()->restrict([ 'nexopos.consignment' ]);
        return ProductCrud::form();
    }

    public function editProduct( Product $product )
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        // This prevents someone from viewing another's item
        ConsignmentModule::CheckAuthor($product->author);

        // This is used to edit an item from "My Items" and "All Items", so set the return url to the previous
        return ProductCrud::form( $product, [
            'returnUrl' => url()->previous(),
        ]);

    }

    // ------------------------------------------------------
    // ConsignorSettings Crud
    // ------------------------------------------------------

    public function consignorSettingsList()
    {
        ns()->restrict([ 'nexopos.consignment.admin-features' ]);

        return ConsignorSettingsCrud::table([
            'title' => __( 'Payment Settings' ),
            'description' =>  __( 'All Consignor Payment Settings' )
        ]);
    }

    public function createConsignorSettings()
    {
        ns()->restrict([ 'nexopos.consignment' ]);
        return ConsignorSettingsCrud::form();
    }

    public function editConsignorSettings( ConsignorSettings $consignorSettings )
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        // This prevents someone from viewing another's settings
        ConsignmentModule::CheckAuthor($consignorSettings->author);

        return ConsignorSettingsCrud::form( $consignorSettings, [
            'title' => __( 'Edit Payment Settings' ),
            'description' =>  __( 'Payment and Contact Preferences' ),
        ]);

    }

    // ------------------------------------------------------
    // Access to the consignor crud for the current consignor
    // ------------------------------------------------------

    public function editPaymentPrefs()
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        $consignorSettings = ConsignorSettings::where('author', Auth::id())->first();

        return ConsignorSettingsCrud::form($consignorSettings, [
            'title' => __('Payment Settings'),
            'description' => __('Payment and Contact Preferences'),
            'returnUrl' => ns()->route('ns.consignment.index'),
        ]);
    }

    // ------------------------------------------------------
    // Print labels / barcodes
    // ------------------------------------------------------

    public function printLabels()
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        // Filtered item printing
        return $this->view( 'Consignment::consignor-print-labels', [
            'title' => __( 'Print Labels' ),
            'description' => __( 'Customize and print products labels.' ),
        ]);
    }

    public function printLabelsBySeller()
    {
        // Used by admins and the label print kiosk user
        $canAccess = ns()->allowedTo([ 'nexopos.consignment.admin-features' ]) || ns()->allowedTo([ 'nexopos.consignment.print-labels' ]);
        if (!$canAccess) {
            ns()->restrict([ '' ]);
        }

        return $this->view( 'Consignment::print-labels-by-seller', [
            'title' => __( 'Print Labels By Seller' ),
            'description' => __( 'Customize and print products labels.' ),
        ]);
    }

    public function searchSellers( Request $request )
    {
        // Used by admins and the label print kiosk user
        $canAccess = ns()->allowedTo([ 'nexopos.consignment.admin-features' ]) || ns()->allowedTo([ 'nexopos.consignment.print-labels' ]);
        if (!$canAccess) {
            ns()->restrict([ '' ]);
        }

        $search = $request->input( 'search' );

        $sellers = User::where( 'username', 'like', '%' . $search . '%' )
            ->orWhere( 'email', 'like', '%' . $search . '%' )
            ->get();

        return $sellers;

    }

    public function searchSellersForPayout( Request $request )
    {

        Log::debug('>>> searchSellersForPayout');

        // Used on the payoutsheet search
        $canAccess = ns()->allowedTo([ 'nexopos.consignment.admin-features' ]);
        if (!$canAccess) {
            ns()->restrict([ '' ]);
        }

        $search = $request->input( 'search' );

        // Equivalent Query to Eloquent Query below
        /*
            SELECT
                    <columnns>
                    // These are subqueries, I could have just used a LEFT JOIN instead...
                    (SELECT first_name from ns_nexopos_users_attributes WHERE ns_nexopos_users_attributes.user_id = ns_nexopos_users.id) AS first_name,
                    (SELECT second_name from ns_nexopos_users_attributes WHERE ns_nexopos_users_attributes.user_id = ns_nexopos_users.id) AS second_name
            FROM
                ns_nexopos_users users
            LEFT JOIN  <-- use LEFT JOIN to return records even if they haven't saved any consignor settings
                ns_consignor_settings ON ns_consignor_settings.author = ns_nexopos_users.id
            WHERE
                <wildcard search query>
        */

        // Pull information from:
        //      Users Table (username, registered email)
        //      Consignment "Payment Settings" screen (payment info, address, shared email, paypal email)
        //      Users Profile Screen (Firstname, Lastname)

        $usersTable = Hook::filter( 'ns-model-table', 'nexopos_users' );
        //$userAttributesTable = Hook::filter( 'ns-model-table', 'nexopos_users_attributes' );
        $consignorSettingsTable = Hook::filter( 'ns-model-table', 'consignor_settings' );

        $sellers = DB::table( $usersTable )
            ->select([
                $usersTable . '.id',
                $usersTable . '.username',
                $usersTable . '.email',
                $consignorSettingsTable . '.email as shared_email',
                $consignorSettingsTable . '.phone',
                $consignorSettingsTable . '.paypal_email',
                $consignorSettingsTable . '.street',
                $consignorSettingsTable . '.city',
                $consignorSettingsTable . '.state',
                $consignorSettingsTable . '.zip',
                $consignorSettingsTable . '.payout_preference',
                $consignorSettingsTable . '.notes',
            ])
            ->addSelect(DB::raw('(SELECT first_name from ns_nexopos_users_attributes WHERE ns_nexopos_users_attributes.user_id = ns_nexopos_users.id) AS first_name'))
            ->addSelect(DB::raw('(SELECT second_name from ns_nexopos_users_attributes WHERE ns_nexopos_users_attributes.user_id = ns_nexopos_users.id) AS second_name'))
            ->leftJoin( $consignorSettingsTable, $consignorSettingsTable . '.author', '=', $usersTable . '.id' )
            ->where( $usersTable . '.username', 'like', '%' . $search . '%' )
            ->orWhere( $usersTable . '.email', 'like', '%' . $search . '%' )
            ->get();

        ConsignmentModule::DumpVar($sellers);

        return $sellers;

    }

    public function printLabelsByItem()
    {
        // Unfiltered item printing
        ns()->restrict([ 'nexopos.consignment.admin-features' ]);

        return $this->view( 'Consignment::print-labels-by-item', [
            'title' => __( 'Print Labels By Item' ),
            'description' => __( 'Customize and print products labels.' ),
        ]);
    }

    public function searchProducts( Request $request )
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        return $this->searchConsignorProducts(
            search: $request->input( 'search' )
            );
    }

    public function searchConsignorProducts( $search, $limit = 5)
    {
        /**
         * @var Builder $query
         */
        $query = Product::query()
            ->searchable()
            ->where('author', '=', Auth::id())
            ->where( function ( $query ) use ( $search ) {
                $query
                    ->orWhere( 'name', 'LIKE', "%{$search}%" )
                    ->orWhere( 'sku', 'LIKE', "%{$search}%" )
                    ->orWhere( 'barcode', 'LIKE', "%{$search}%" );
            })
            ->with([
                'unit_quantities.unit',
                'tax_group.taxes',
            ])
            ->limit( $limit );

        return $query->get()
            ->map( function ( $product ) {
                $units = json_decode( $product->purchase_unit_ids );

                if ( $units ) {
                    $product->purchase_units = collect();
                    collect( $units )->each( function ( $unitID ) use ( &$product ) {
                        $product->purchase_units->push( Unit::find( $unitID ) );
                    });
                }

                return $product;
            });
    }

    // Return all products for a user
    public function allProducts( Request $request )
    {

        // TODO:  This only seems to honor the last one in the comma delimited list, bugfix?
        //ns()->restrict([ 'nexopos.consignment', 'nexopos.consignment.print-labels' ]);

        $canAccess = ns()->allowedTo([ 'nexopos.consignment' ]) || ns()->allowedTo([ 'nexopos.consignment.print-labels' ]);
        if (!$canAccess) {
            ns()->restrict([ '' ]);
        }

        return $this->getAllConsignorProducts(
            search: $request->input( 'search' )
            );
    }

    public function getAllConsignorProducts( $search, $limit = 500)
    {

        Log::debug('>>> getAllConsignorProducts, user_id: ' . $search);

        // if user has the correct permissions, then filter by passed in search term (user_id)
        $isPrinter = ns()->allowedTo([ 'nexopos.consignment.admin-features', 'nexopos.consignment.print-labels' ]);

        if ($isPrinter && $search) {    // if a $search hasn't been passed in, assume this is an admin using the default "Print Labels" screen as a normal user
            $author = $search;
        } else {
            $author = Auth::id();
        }

        /**
         * @var Builder $query
         */
        $query = Product::query()
            ->searchable()
            ->where('author', '=', $author)
            ->with([
                'unit_quantities.unit',
                'tax_group.taxes',
            ])
            ->limit( $limit );

        return $query->get()
            ->map( function ( $product ) {
                $units = json_decode( $product->purchase_unit_ids );

                if ( $units ) {
                    $product->purchase_units = collect();
                    collect( $units )->each( function ( $unitID ) use ( &$product ) {
                        $product->purchase_units->push( Unit::find( $unitID ) );
                    });
                }

                return $product;
            });
    }

    // ------------------------------------------------------
    // Contact Sellers
    // ------------------------------------------------------

    public function contactSellers()
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        return $this->view( 'Consignment::contact-sellers', [
            'title' => __( 'Contact Sellers' ),
            'description' => __( 'Enter item barcode to find contact info for a seller' ),
        ]);
    }

    public function searchBarcodes( Request $request )
    {
        ns()->restrict([ 'nexopos.consignment' ]);
        $reference = $request->input( 'search' );

        // this requires them to put in the whole barcode, probably reduces lag as well
        //$productUnitQuantity = ProductUnitQuantity::barcode( $reference )->with( 'unit' )->first();

        $productUnitQuantity = ProductUnitQuantity::where( 'barcode', 'like', '%' . $reference . '%' )->first();
        $products = null;

        if ( $productUnitQuantity instanceof ProductUnitQuantity ) {
            $products = Product::where( 'id', $productUnitQuantity->product_id )->get();
        }

        return $products;
    }

    public function getConsignorContactInfo( Request $request )
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        $author = $request->input( 'author' );
        $consignorInfo = ConsignorSettings::where( 'author', $author )->first();
        $emptyConsignorInfo = new ConsignorSettings;

        // if the consignor hasn't filled out their preferences
        if ($consignorInfo === null) {
            $consignorInfo = $emptyConsignorInfo;
        }

        if ($consignorInfo->share_email !== 'yes') {
            $emptyConsignorInfo->email = 'Consignor did not opt in to share email';
        } else {
            $emptyConsignorInfo->email = $consignorInfo->email;
        }

        if ($consignorInfo->share_phone !== 'yes') {
            $emptyConsignorInfo->phone = 'Consignor did not opt in to share phone';
        } else {
            $emptyConsignorInfo->phone = $consignorInfo->phone;
        }

        return $emptyConsignorInfo;
    }

    // ------------------------------------------------------
    // "Static" Pages
    // ------------------------------------------------------

    /**
     * Index Controller Page
     * @return view
     * @since 1.0
     **/
    public function index()
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        return $this->view( 'Consignment::index', [
            'title'   =>  __( 'Consignment' ),
            'description' =>  __( 'Consignment Home Page' )
        ]);
    }

    /**
     * Label Index Controller Page
     * @return view
     * @since 1.0
     **/
    public function indexLabels()
    {
        ns()->restrict([ 'nexopos.consignment.print-labels' ]);

        return $this->view( 'Consignment::index-labels', [
            'title'   =>  __( 'Print Labels' ),
            'description' =>  __( 'Label Printing Home Page' )
        ]);
    }

    /**
     * Admin Index Controller Page
     * @return view
     * @since 1.0
     **/
    public function indexAdmin()
    {
        ns()->restrict([ 'nexopos.consignment.admin-features' ]);

        return $this->view( 'Consignment::index-admin', [
            'title'   =>  __( 'Consignment Administration' ),
            'description' =>  __( 'Consignment Admin Home Page' )
        ]);
    }

    /**
     * Payout Index Controller Page
     * @return view
     * @since 1.0
     **/
    public function indexPayouts()
    {
        ns()->restrict([ 'nexopos.consignment.manage-payouts' ]);

        return $this->view( 'Consignment::index-payouts', [
            'title'   =>  __( 'Consignment Payouts' ),
            'description' =>  __( 'Manage Payouts' )
        ]);
    }

    /**
     * FAQ Controller Page
     * @return view
     * @since 1.0
     **/
    public function faq()
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        return $this->view( 'Consignment::faq', [
            'title'   =>  __( 'FAQ' ),
            'description' =>  __( 'FAQ Page' )
        ]);
    }

    // ------------------------------------------------------
    // Module Settings / Options (unused atm)
    // ------------------------------------------------------

    // I was going to use this for user settings, but need a crud for that
    // Leave this in case we need a module settings page down the line...
    public function showModuleOptionsPage()
    {
        ns()->restrict([ 'manage.options' ]);
        return ConsignmentSettings::renderForm();
    }


    // ------------------------------------------------------
    // Payout Sheet Report
    // ------------------------------------------------------

    public function payoutSheetReport()
    {
        ns()->restrict([ 'nexopos.consignment.admin-features' ]);
        return $this->view( 'Consignment::payout-sheet-report', [
            'title' => __( 'Payout Sheet' ),
            'description' => __( 'Provides detailed a payout report for the selected Consignor' ),
        ]);
    }

    // ------------------------------------------------------
    // Consignor Sales Report
    // ------------------------------------------------------

    public function consignorSalesReport()
    {
        ns()->restrict([ 'nexopos.consignment' ]);
        return $this->view( 'Consignment::consignor-sales-report', [
            'title' => __( 'My Sales' ),
            'description' => __( 'Provides an overview of your Sales' ),
        ]);
    }

    /**
     * get sales based on a specific time range
     *
     * @return array
     */
    public function getConsignorSalesReport( Request $request )
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        $search = $request->input( 'search' );
        Log::debug('>>> getConsignorSalesReport, user_id: ' . $search);

        // if user has the correct permissions, then filter by passed in search term (user_id)
        $isAdmin = ns()->allowedTo([ 'nexopos.consignment.admin-features' ]);

        if ($isAdmin && $search) {    // if a $search hasn't been passed in, assume this is an admin using the default "My Ssles" screen as a normal user
            $author = $search;
        } else {
            $author = Auth::id();
        }

        // In the case of the consignors sales report, we're only concerned with type 'products_report', and for the current user
        // The start & end dates are hard-coded to +/- one month in the blade
        return $this->getConsignorProductsReports( $request->input( 'startDate' ), $request->input( 'endDate' ), $author );
    }

    public function getConsignorProductsReports( $start, $end, $user_id = null )
    {
        $orderTable = Hook::filter( 'ns-model-table', 'nexopos_orders' );
        $productsTable = Hook::filter( 'ns-model-table', 'nexopos_products' );
        $orderProductTable = Hook::filter( 'ns-model-table', 'nexopos_orders_products' );

        // Equivalent Query to Eloquent Query below
        /*
            SELECT * FROM
                ns_nexopos_orders orders,
                ns_nexopos_products products,
                ns_nexopos_orders_products orders_products
            WHERE
                orders_products.order_id = orders.id
                AND orders_products.product_id = products.id
                AND products.author = 60
                AND orders.payment_status = 'paid'
         */

        $OrdersProducts = DB::table( $orderProductTable )
            ->join( $orderTable, $orderTable . '.id', '=', $orderProductTable . '.order_id' )
            ->join( $productsTable, $productsTable . '.id', '=', $orderProductTable . '.product_id' )
            ->where( $productsTable . '.author', '=', $user_id )
            ->where( $orderTable . '.payment_status', '=',Order::PAYMENT_PAID )->get();

        // TODO: Refunds: if the payment_status is "partially_refunded" then query the ns_nexopos_orders_products_refunds table to see which product_id was refunded
        // TODO: Refunds: as it is now, if an order is partially refunded, then no-one will see a payout on this report from that entire order (if there were multiple items on the order)
        // TODO: Refunds: full order refunds should be fine

        //throw new Exception( $OrdersProducts->toSql() );
        //throw new Exception('Order Count: ' . count($OrdersProducts) );

        $summary = $this->getConsignorSalesSummary( $OrdersProducts );

        $productsIds = $OrdersProducts->map( fn( $orderProduct ) => $orderProduct->product_id )->unique();

        // Sample Map Code
        /*
        $productTable = $productsIds->map( function ( $id ) use ( $OrdersProducts ) {
            $product = $OrdersProducts->where( 'product_id', $id )->first();
            return $product->name;
            }
        );
        ConsignmentModule::DumpVar($productTable);
        */

        return [

            // for each unique product
            'result' => $productsIds->map( function ( $id ) use ( $OrdersProducts ) {

                // unique product instance
                $product = $OrdersProducts->where( 'product_id', $id )->first();

                // collection of these products
                $filteredProducts = $OrdersProducts->where( 'product_id', $id )->all();

                // sum the filtered product fields
                $summable = [ 'quantity', 'discount', 'wholesale_tax_value', 'sale_tax_value', 'tax_value', 'total_price_without_tax', 'total_price', 'total_price_with_tax', 'total_purchase_price' ];
                foreach ( $summable as $key ) {
                    $product->$key = collect( $filteredProducts )->sum( $key );
                }

                return $product;

            })->values(),

            'summary' => $summary,

        ];


    }

    private function getConsignorSalesSummary( $orders )
    {
        $allSales = $orders->map( function ( $order ) {
            return [
                'total' => ($order->total_price * .85),     // TODO: calculate this from module settings
            ];
        });

        return [
            'total' => Currency::define( $allSales->sum( 'total' ) )->getRaw(),
        ];
    }

}
