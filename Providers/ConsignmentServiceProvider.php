<?php
namespace Modules\Consignment\Providers;

use App\Classes\Hook;
use App\Events\AfterSuccessfulLoginEvent;
use Illuminate\Support\ServiceProvider as CoreServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Modules\Consignment\Crud\ConsignorSettingsCrud;
use Modules\Consignment\Crud\FlightCrud;
use Modules\Consignment\Crud\ProductCrud;
use Modules\Consignment\Settings\ConsignmentSettings;

class ConsignmentServiceProvider extends CoreServiceProvider
{

    /*

    // Sample Service Provider Code
    // https://my.nexopos.com/en/documentation/developpers-guides/service-provider

    public function register()
    {
        // registering a module
        Hook::filter( 'ns.dashboard-menus', [ MyFilters::class, 'addMenus' ]);

        // registering a service
        $this->app->singleton( ConsignmentService::class, fn() => new ConsignmentClass( ns()->option->get( 'custom-option' ) ) );
        
        // catch events
        Event::listen( AfterSuccessfulLoginEvent::class, [ MyEvent::class, 'catchSuccessfulLogin' ]);
    }
    */

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //Log::debug('ConsignmentDebug : ' . __FUNCTION__);

        // Register our Crud Test (see function below)
        Hook::addFilter( 'ns-crud-resource', [ $this, 'registerCrud' ]);

        // customize page titles
        Hook::filter( 'ns-page-title', function( $pageTitle ) {;
            return __( '%s &mdash; VCF Consignment' );
        });
        
        // customize footers
        Hook::addFilter( 'ns-footer-signature', function( $signature ) {
            // Get git hash
            $gitBasePath = base_path().'/.git';
            $gitStr = file_get_contents($gitBasePath.'/HEAD');
            $gitBranchName = rtrim(preg_replace("/(.*?\/){2}/", '', $gitStr));
            $gitPathBranch = $gitBasePath.'/refs/heads/'.$gitBranchName;
            $gitHash = substr(file_get_contents($gitPathBranch),0, 7);
            return __( 'VCF ' . $gitHash);
        });

        // settings
        Hook::addFilter( 'ns.settings', function( $class, $identifier ) {
            switch( $identifier ) {
                case 'consignmentsettings': return new ConsignmentSettings; break;
                default: return $class;
            }
        }, 10, 2 );

    }

    /**
     * Boot services.  This method is called right after the method "register" of all enabled modules.
     *
     * @return void
     */
    public function boot()
    {
        //Log::debug('ConsignmentDebug : ' . __FUNCTION__);
    }

    public function registerCrud( $identifier )
    {

        //Log::debug('ConsignmentDebug : ' . __FUNCTION__);

        switch( $identifier ) {
            case 'consignment.products': return ProductCrud::class;
            case 'consignment.consignor.settings': return ConsignorSettingsCrud::class;
            default: return $identifier; // required
        }
    }

}