<?php
/**
 * Consignment Settings
 * @since 1.0
**/
namespace Modules\Consignment\Settings;

use App\Services\SettingsPage;
use App\Services\ModulesService;
use App\Services\Helper;
use Illuminate\Support\Facades\Auth;

class ConsignmentSettings extends SettingsPage
{
    protected $form;
    protected $labels;
    protected $identifier      =   'consignmentsettings';

    public function __construct()
    {
        /**
         * @var ModulesService $module
         */
        $module     =   app()->make( ModulesService::class );
        $this->form     =   [];

        $this->labels   = [
            'title'   =>  __( 'Consignment Settings' ),
            'description'  =>  __( 'Settings for the Consignment Module' )
        ];

        // dummy fields
        $this->form = [
            'tabs' => [
                'identification' => [
                    'label' => __( 'Identification' ),
                    'fields' => [
                        [
                            'label' => __( 'First Name' ),
                            'name' => 'consignment_first_name',
                            'value' => Auth::user()->attribute->first_name ?? '',
                            'type' => 'text',
                            'description' => __( 'Define what is the user first name. If not provided, the username is used instead.' ),
                        ], [
                            'label' => __( 'Second Name' ),
                            'name' => 'consignment_second_name',
                            'value' => Auth::user()->attribute->second_name ?? '',
                            'type' => 'text',
                            'description' => __( 'Define what is the user second name. If not provided, the username is used instead.' ),
                        ], [
                            'label' => __( 'Theme' ),
                            'name' => 'consignment_theme',
                            'value' => Auth::user()->attribute->theme ?? '',
                            'type' => 'select',
                            'options' => Helper::kvToJsOptions([
                                'dark' => __( 'Dark' ),
                                'light' => __( 'Light' ),
                            ]),
                            'description' => __( 'Define what is the theme that applies to the dashboard.' ),
                        ], [
                            'label' => __( 'Avatar' ),
                            'name' => 'consignment_avatar_link',
                            'value' => Auth::user()->attribute->avatar_link ?? '',
                            'type' => 'media',
                            'data' => [
                                'user_id' => Auth::id(),
                                'type' => 'url',
                            ],
                            'description' => __( 'Define the image that should be used as an avatar.' ),
                        ], [
                            'label' => __( 'Language' ),
                            'name' => 'consignment_language',
                            'value' => Auth::user()->attribute->language ?? '',
                            'type' => 'select',
                            'options' => Helper::kvToJsOptions( config( 'nexopos.languages' ) ),
                            'description' => __( 'Choose the language for the current account.' ),
                        ],
                    ],
                ],
            ],
        ];
    }
}