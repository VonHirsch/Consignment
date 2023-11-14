<?php

/**
 * Consignment Controller
 * @since 1.0
 * @package modules/Consignment
**/

namespace Modules\Consignment\Http\Controllers;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\DashboardController;
use Modules\Consignment\Crud\FlightCrud;

class ConsignmentCrudTest extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
    }


    public function flightList()
    {
        return FlightCrud::table();
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
