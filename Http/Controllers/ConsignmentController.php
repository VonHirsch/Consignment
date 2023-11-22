<?php

/**
 * Consignment Controller
 * @since 1.0
 * @package modules/Consignment
**/

namespace Modules\Consignment\Http\Controllers;
use App\Classes\Currency;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\DashboardController;
use App\Services\OrdersService;
use App\Services\ReportService;
use Modules\Consignment\ConsignmentModule;
use Modules\Consignment\Crud\ProductCrud;

// https://my.nexopos.com/en/documentation/crud-api/how-to-create-a-crud-component

class ConsignmentController extends DashboardController
{
    public function __construct(
        protected OrdersService $ordersService,
        protected ReportService $reportService
    ) {
        parent::__construct();
    }

//    public function __construct()
//    {
//        parent::__construct();
//    }

    public function productList()
    {
        ns()->restrict([ 'nexopos.consignment' ]);
        return ProductCrud::table([
            'title' => __( 'My Consignment Items' )
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

    public function consignorSalesReport()
    {
        return $this->view( 'Consignment::consignor-sales-report', [
            'title' => __( 'Sales Report' ),
            'description' => __( 'Provides an overview of Sales' ),
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

    public function getConsignorProductsReports2( $start, $end, $user_id = null )
    {

        // get all products by $user_id
        // get all orders with those products
        // filter orders by PAID
        // calculate summary
        //
        // map

    }
    public function getConsignorProductsReports( $start, $end, $user_id = null )
    {

        // get all payments in date range
        $request = Order::paymentStatus( Order::PAYMENT_PAID )
            ->from( $start )
            ->to( $end );

        // filter by author (cashier)
        if ( ! empty( $user_id ) ) {
            $request = $request->where( 'author', $user_id );
        }

        // orders with products ...
        $orders = $request->with( 'products' )->get();

        $summary = $this->getConsignorSalesSummary( $orders );

        // map orders to products
        $products = $orders->map( fn( $order ) => $order->products )->flatten();

        // get unique products
        $productsIds = $products->map( fn( $product ) => $product->product_id )->unique();

        return [

            // for each unique product
            'result' => $productsIds->map( function ( $id ) use ( $products ) {

                // unique product instance
                $product = $products->where( 'product_id', $id )->first();

                // collection of these products
                $filteredProducts = $products->where( 'product_id', $id )->all();

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
                'subtotal' => $order->subtotal,
                'sales_discounts' => $order->discount,
                'sales_taxes' => $order->tax_value,
                'shipping' => $order->shipping,
                'total' => $order->total,
            ];
        });

        return [
            'sales_discounts' => Currency::define( $allSales->sum( 'sales_discounts' ) )->getRaw(),
            'sales_taxes' => Currency::define( $allSales->sum( 'sales_taxes' ) )->getRaw(),
            'subtotal' => Currency::define( $allSales->sum( 'subtotal' ) )->getRaw(),
            'shipping' => Currency::define( $allSales->sum( 'shipping' ) )->getRaw(),
            'total' => Currency::define( $allSales->sum( 'total' ) )->getRaw(),
        ];
    }

}
