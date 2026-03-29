<?php
/**
 * Consignment Settings
 * @since 1.0
**/
namespace Modules\Consignment\Settings;

use App\Models\TaxGroup;
use App\Services\SettingsPage;
use App\Services\Helper;

class ConsignmentSettings extends SettingsPage
{
    protected $form;
    protected $labels;
    protected $identifier      =   'consignmentsettings';

    public function __construct()
    {
        $this->form     =   [];

        $this->labels   = [
            'title'   =>  __( 'Consignment Settings' ),
            'description'  =>  __( 'Define defaults used when creating new consignment items.' )
        ];

        $this->form = [
            'tabs' => [
                'taxes' => [
                    'label' => __( 'Tax Defaults' ),
                    'fields' => [
                        [
                            'type' => 'select',
                            'options' => Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ] ),
                            'description' => __( 'Choose the tax group assigned to newly created consignment items.' ),
                            'name' => 'ns_consignment_default_tax_group_id',
                            'label' => __( 'Default Tax Group' ),
                            'value' => ns()->option->get( 'ns_consignment_default_tax_group_id', 2 ),
                        ], [
                            'type' => 'select',
                            'options' => Helper::kvToJsOptions([
                                'inclusive' => __( 'Inclusive' ),
                                'exclusive' => __( 'Exclusive' ),
                            ]),
                            'description' => __( 'Choose how tax should be computed for newly created consignment items.' ),
                            'name' => 'ns_consignment_default_tax_type',
                            'label' => __( 'Default Tax Type' ),
                            'value' => ns()->option->get( 'ns_consignment_default_tax_type', 'exclusive' ),
                        ],
                    ],
                ],
                'payouts' => [
                    'label' => __( 'Payout Defaults' ),
                    'fields' => [
                        [
                            'type' => 'number',
                            'description' => __( 'Percentage kept as commission or fee before consignor payout. For example, 18 means the consignor receives 82% of the sale amount.' ),
                            'name' => 'ns_consignment_percent_commission_fee',
                            'label' => __( 'Percent Commission / Fee' ),
                            'validation' => 'required|numeric|min:0|max:100',
                            'value' => ns()->option->get( 'ns_consignment_percent_commission_fee', 18 ),
                        ],
                    ],
                ],
            ],
        ];
    }
}
