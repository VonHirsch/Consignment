<?php
namespace Modules\Consignment;

use App\Services\Module;
use App\Classes\Hook;
use Illuminate\Support\Facades\Log;

class ConsignmentModule extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );

        //Log::debug('ConsignmentDebug : ' . __FUNCTION__);

        // Dashboard Menus
        // https://my.nexopos.com/en/documentation/filters/ns-dashboard-menus
        Hook::addFilter( 'ns-dashboard-menus', function( $menus ) {
            $menus    =   array_insert_after( $menus, 'orders', [
                'consignment'    =>    [
                    'label'   =>    __( 'Consignment' ),
                    'permissions' => [ 'nexopos.consignment' ],
                    'icon'   => 'la-hand-holding-heart',
                    'href'    =>    url( 'dashboard/consignmentItems/create' )
                ]
            ]);

            return $menus; // <= do not forget
        });

    }

}