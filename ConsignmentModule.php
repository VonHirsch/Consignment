<?php
namespace Modules\Consignment;

use Illuminate\Support\Facades\Event;
use App\Services\Module;
use App\Classes\Hook;

class ConsignmentModule extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );

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