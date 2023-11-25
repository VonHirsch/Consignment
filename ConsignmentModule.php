<?php
namespace Modules\Consignment;

use App\Classes\Hook;
use App\Services\Module;
use App\Exceptions\NotAllowedException;
use App\Services\Users;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
                'ConsignmentMain'    =>    [
                    'label'   =>    __( 'Consignment Home' ),
                    'permissions' => [ 'nexopos.consignment' ],
                    'icon'   => 'la-hand-holding-heart',
                    'href'    =>    url( 'dashboard/consignment/index' )
                    //'href'    =>    url( 'dashboard/consignment/products' )
                ]
            ]);

            return $menus; // <= do not forget
        });

    }

    /**
     * check that the current user is the author of the product
     * @param  int $author
     * @throws NotAllowedException
     */
    public static function CheckAuthor( $author )
    {
        $user = app()->make( Users::class );
        if (! $user->is([ 'user' ]) ) {
            // Any non-vanilla user can CRUD consignment items they haven't authored
            return;
        }

        if ( $author !== Auth::id() ) {
            throw new NotAllowedException;
        }
    }

    /**
     * check that the current user is the author of the product
     */
    public static function DumpVar( $var )
    {
        Log::debug('>>> DumpVar <<<');
        $var_dump = print_r($var, true);
        Log::debug('var = ' . $var_dump);
    }


}