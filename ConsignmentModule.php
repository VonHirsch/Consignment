<?php
namespace Modules\Consignment;

use App\Classes\Hook;
use App\Services\Module;
use App\Exceptions\NotAllowedException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ConsignmentModule extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );

        Hook::addFilter( 'ns-dashboard-menus', function( $menus ) {
            $menus    =   array_insert_after( $menus, 'orders',
                [
                    'ConsignmentMain'    =>    [
                        'label'   =>    __( 'Consignment Home' ),
                        'permissions' => [ 'nexopos.consignment' ],
                        'icon'   => 'la-hand-holding-usd',
                        'href'    =>    url( 'dashboard/consignment/index' )
                    ],
                    'ConsignmentLabels'    =>    [
                        'label'   =>    __( 'Consignment Labels' ),
                        'permissions' => [ 'nexopos.consignment.print-labels' ],
                        'icon'   => 'la-tags',
                        'href'    =>    url( 'dashboard/consignment/index-labels' )
                    ],
                    'ConsignmentAdmin'    =>    [
                        'label'   =>    __( 'Consignment Admin' ),
                        'permissions' => [ 'nexopos.consignment.admin-features' ],
                        'icon'   => 'la-tools',
                        'href'    =>    url( 'dashboard/consignment/index-admin' )
                    ],

//  TODO: Create a consignment payouts crud that can be used to track payouts
//
//                    'ConsignmentPayouts'    =>    [
//                        'label'   =>    __( 'Consignment Payouts' ),
//                        'permissions' => [ 'nexopos.consignment.manage-payouts' ],
//                        'icon'   => 'la-comment-dollar',
//                        'href'    =>    url( 'dashboard/consignment/index-payouts' )
//                    ],

                ]
            );

            return $menus; // <= do not forget
        });

    }

    /**
     * check that the current user is the author of the product
     * Consignment admins bypass this check
     * @param  int $author
     * @throws NotAllowedException
     */
    public static function CheckAuthor( $author )
    {
        $isAdmin = ns()->allowedTo([ 'nexopos.consignment.admin-features' ]);

        if ($isAdmin) return;

        if ( $author !== Auth::id() ) {
            throw new NotAllowedException;
        }
    }

    /**
     * debug
     */
    public static function DumpVar( $var )
    {
        Log::debug('>>> DumpVar <<<');
        $var_dump = print_r($var, true);
        Log::debug('var = ' . $var_dump);
    }


}