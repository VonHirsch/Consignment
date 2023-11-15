<?php

/**
 * Consignment Controller
 * @since 1.0
 * @package modules/Consignment
**/

namespace Modules\Consignment\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\DashboardController;
use Modules\Consignment\Crud\FlightCrud;
use Modules\Consignment\Models\Flight;

class ConsignmentCrudTest extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function flightList()
    {
        ns()->restrict([ 'nexopos.consignment' ]);

        /*
        Items you can include:
        title:
            Will ensure you can customize the table title
        description:
            Will be used to provide a description to the crud table.
        src:
            If you would like to customize the source url used by the frontend library for fetching entries.
        createUrl:
            Will be used to overwrite the URL that takes to the creation forms
        queryParams:
            Any custom parameters that you would like to submit to the Crud instance. This can be useful to apply custom filters.
        menus:
            Provided to render the side menu.
        src: https://my.nexopos.com/en/documentation/crud-api/how-to-create-a-crud-component
        */

        return FlightCrud::table([
            'title' => __( 'Here are the Flights:' ),
            //'createUrl' => ns()->url( '/dashboard/Consignment/flights/create' ),  // not needed now that I fixed the slug
            //'submitUrl' => ns()->url( '/dashboard/Consignment/flights' ),
        ]);

    }

    public function createFlight()
    {

        return FlightCrud::form();

        /*
        Items you can include (to use these with empty form, pass null for the first argument.  e.g. form(null, []);
        title:
            To provide a title for the form.
        description:
            To provide a description for the form.
        src:
            To change the source URL used by the front-end library to interact with the Crud instance.
        returnUrl:
            This URL is used as a return URL to the list of entries. It's also used to automatically redirect the user when the modification has been successfully made.
        submitMethod:
            If for some reason, you would like NexoPOS 4.x to change the method used to update/create an entry you can define that here. Note that "post" and "put" are supported.
        src: https://my.nexopos.com/en/documentation/crud-api/how-to-create-a-crud-component

        // sample code from consignment-products crud
        return $this->view( 'Consignment::items.create', [
            'title' => __( 'Create a consignment item' ),
            'description' => __( 'Add a new consignment item to the system' ),
            'submitUrl' => ns()->url( '/consignmentItems' ),
            'returnUrl' => ns()->url( '/consignmentItems' ),
            'unitsUrl' => ns()->url( '/api/nexopos/v4/units-groups/{id}/units' ),
            'src' => ns()->url( '/api/nexopos/v4/crud/ns.consignment-products/form-config' ),
        ]);

        */
    }

    public function editFlight( Flight $flight )
    {

        Log::debug('>>> product editFlight');
        $var_dump = print_r($flight, true);
        Log::debug('flight = ' . $var_dump);

        return FlightCrud::form( $flight );
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
