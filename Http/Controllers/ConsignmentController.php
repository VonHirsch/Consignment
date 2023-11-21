<?php

/**
 * Consignment Controller
 * @since 1.0
 * @package modules/Consignment
**/

namespace Modules\Consignment\Http\Controllers;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function salesReport()
    {
        return $this->view( 'Consignment::sales-report', [
            'title' => __( 'Sales Report' ),
            'description' => __( 'Provides an overview of Sales' ),
        ]);
    }

    public function showConsignorsStatement()
    {
        return $this->view( 'Consignment::consignors-statement', [
            'title' => __( 'Consignors Statement' ),
            'description' => __( 'Display the complete consignor statement.' ),
        ]);
    }

    public function getConsignorsStatement( Customer $customer, Request $request )
    {
        return $this->getConsignorStatement(
            customer: $customer,
            rangeStarts: $request->input( 'rangeStarts' ),
            rangeEnds: $request->input( 'rangeEnds' )
        );
    }

    /**
     * Will return the actual customer statement
     *
     * @return array
     */
    public function getConsignorStatement( Customer $customer, $rangeStarts = null, $rangeEnds = null )
    {
        $rangeStarts = Carbon::parse( $rangeStarts )->toDateTimeString();
        $rangeEnds = Carbon::parse( $rangeEnds )->toDateTimeString();

        return [
            'purchases_amount' => $customer->purchases_amount,
            'owed_amount' => $customer->owed_amount,
            'account_amount' => $customer->account_amount,
            'total_orders' => $customer->orders()->count(),
            'credit_limit_amount' => $customer->credit_limit_amount,
            'orders' => Order::where( 'customer_id', $customer->id )
                ->paymentStatusIn([ Order::PAYMENT_PAID, Order::PAYMENT_UNPAID, Order::PAYMENT_REFUNDED, Order::PAYMENT_PARTIALLY ])
                ->where( 'created_at', '>=', $rangeStarts )
                ->where( 'created_at', '<=', $rangeEnds )
                ->get(),
            'wallet_transactions' => CustomerAccountHistory::where( 'customer_id', $customer->id )
                ->where( 'created_at', '>=', $rangeStarts )
                ->where( 'created_at', '<=', $rangeEnds )
                ->get(),
        ];
    }


}
