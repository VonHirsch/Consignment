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
        protected ReportService $reportService
    ) {
        parent::__construct();
    }

    // ------------------------------------------------------
    // Product Crud
    // ------------------------------------------------------

    public function productList()
    {
        ns()->restrict([ 'nexopos.consignment' ]);
        return ProductCrud::table([
            'title' => __( 'My Items' ),
            'description' =>  __( 'Add, edit and delete your consignment items' )
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

        return ProductCrud::form( $product );
    }

    // ------------------------------------------------------
    // ConsignorSettings Crud
    // ------------------------------------------------------

    public function consignorSettingsList()
    {
        ns()->restrict([ 'nexopos.consignment' ]);
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

        // This prevents someone from viewing another's item
        ConsignmentModule::CheckAuthor($consignorSettings->author);

        return ConsignorSettingsCrud::form( $consignorSettings, [
            'title' => __( 'Payment Settings' ),
            'description' =>  __( 'Payment and Contact Preferences' ),
        ]);

    }

    // ------------------------------------------------------
    // Access to the consignor crud for the current consignor
    // ------------------------------------------------------

    public function editPaymentPrefs()
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        $user = app()->make( Users::class );
        if ($user->is([ 'admin' ]) ) {
            // Admins will see the full crud listing
            return redirect( ns()->route( 'ns.consignorsettings.list' ) );
        } else {

            $consignorSettings = ConsignorSettings::where('author', Auth::id())->first();

            return ConsignorSettingsCrud::form($consignorSettings, [
                'title' => __('Payment Settings'),
                'description' => __('Payment and Contact Preferences'),
                'returnUrl' => ns()->route('ns.consignment.index'),
            ]);
        }
    }

    /**
     * Index Controller Page
     * @return view
     * @since 1.0
     **/
    public function index()
    {
        return $this->view( 'Consignment::index', [
            'title'   =>  __( 'Consignment' ),
            'description' =>  __( 'Consignment Home Page' )
        ]);
    }

    /**
     * FAQ Controller Page
     * @return view
     * @since 1.0
     **/
    public function faq()
    {
        return $this->view( 'Consignment::faq', [
            'title'   =>  __( 'FAQ' ),
            'description' =>  __( 'FAQ Page' )
        ]);
    }

    // I was going to use this for user settings, but need a crud for that
    // Leave this in case we need a module settings page down the line...
    public function showModuleOptionsPage()
    {
        ns()->restrict([ 'manage.options' ]);
        return ConsignmentSettings::renderForm();
    }

    public function consignorSalesReport()
    {
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
        // In the case of the consignors sales report, we're only concerned with type 'products_report', and for the current user
        // The start & end dates are hard-coded to +/- one month in the blade
        return $this->getConsignorProductsReports( $request->input( 'startDate' ), $request->input( 'endDate' ), Auth::id() );
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
            ->where( $productsTable . '.author', '=', Auth::id() )
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
                'total' => $order->total_price,
            ];
        });

        return [
            'total' => Currency::define( $allSales->sum( 'total' ) )->getRaw(),
        ];
    }

}
