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

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        // these don't seem to be working, possibly the hook is broken or too early?

        // customize page titles
        Hook::filter( 'ns-page-title', function( $pageTitle ) {;
            return __( '%s &mdash; VCF Consignment' );
        });

        // customize footers
        Hook::addFilter( 'ns-footer-signature', function( $signature ) {
            return __( 'VCF Consignment' );
        });


        // TODO: Register Crud Instance?
        // Not sure if I will need this.  Putting it here while i'm going through the docs
        // https://my.nexopos.com/en/documentation/filters/ns-crud-resource
        /*
        Hook::addFilter( 'ns-crud-resource', function( $identifier ) {
            switch( $identifier ) {
                case 'ns.my-crud-identifier': return MyOrdersCrud::class;
                default: return $identifier;
            }
        });
        */





    }

}