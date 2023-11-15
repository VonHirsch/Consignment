<?php

/**
 * Consignment Controller
 * @since 1.0
 * @package modules/Consignment
**/

namespace Modules\Consignment\Http\Controllers;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\DashboardController;
use Modules\Consignment\Crud\ProductCrud;

// https://my.nexopos.com/en/documentation/crud-api/how-to-create-a-crud-component

class ConsignmentController extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
    }

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
        return ProductCrud::form( $product );
    }

    /**
     * Index Controller Page
     * @return view
     * @since 1.0
    **/
    public function index()
    {
        return $this->view( 'Consignment::index' );
    }
}
