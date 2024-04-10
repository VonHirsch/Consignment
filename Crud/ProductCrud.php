<?php
namespace Modules\Consignment\Crud;

use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\UnitGroup;
use App\Services\BarcodeService;
use App\Services\CurrencyService;
use App\Services\ProductService;
use App\Services\TaxService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Services\CrudEntry;
use App\Exceptions\NotAllowedException;
use App\Models\User;
use Illuminate\Support\Str;
use Modules\Consignment\ConsignmentModule;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductCrud extends CrudService
{
    /**
     * define the base table
     * @param string
     */
    protected $table      =   'nexopos_products';

    /**
     * default slug
     * @param string
     */
    protected $slug   =   'consignment/products';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace  =   'consignment.products';

    /**
     * Model Used
     * @param string
     */
    protected $model      =   Product::class;

    /**
     * Define permissions
     * @param array
     */
    protected $permissions  =   [
        'create'    =>  'nexopos.consignment',
        'read'      =>  'nexopos.consignment',
        'update'    =>  'nexopos.consignment',
        'delete'    =>  'nexopos.consignment',
    ];

    /**
     * Adding relations
     * I'm not sure the default example here is correct ...
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param array
     */
    public $relations   =  [
        [ 'nexopos_users as user', 'nexopos_products.author', '=', 'user.id' ],
        [ 'nexopos_products_unit_quantities as unit', 'nexopos_products.id', '=', 'unit.product_id' ],
        ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations    =   [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
        // Thought I could use this to access $entry->unitQuantity->quantity in getForm, but doesn't work, perhaps it needs to be on a separate tab
        // This technique borrowed from CustomerCrud
        //'unitQuantity' => [ ProductUnitQuantity::class, 'product_id', 'id' ],
    ];

    /**
     * Export Columns defines the columns that
     * should be included on the exported csv file.
     */
    protected $exportColumns    =   []; // @getColumns will be used by default.

    /**
     * Pick
     * Restrict columns you retrieve from relation.
     * Should be an array of associative keys, where
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick = [
        'unit' => [ 'quantity', 'sale_price_edit' ],
        'user' => [ 'username' ],
    ];

    /**
     * Define where statement
     * @var array
    **/
    protected $listWhere    =   [];

    /**
     * Define where in statement
     * @var array
     */
    protected $whereIn      =   [];

    /**
     * Fields which will be filled during post/put
     */
    
    /**
     * If few fields should only be filled
     * those should be listed here.
     * These determine which fields will be in the default insert statement
     */
    public $fillable = [
        'name',
        'tax_type',
        //'tax_value',
        //'unit_group',
        'description',
        'barcode',
        'barcode_type',
        'sku',
        'unit_group',
        'author',     // if you include author in fillable, there's logic in the CrudService to overwrite it with the current Auth::Id, which I've modified to skip when the crud model is a Product
                        // It was modified so that admin's can edit items without taking ownership of them
        'category_id',
        'type'
    ];

    /**
     * If fields should be ignored during saving
     * those fields should be listed here
     */
    public $skippable   =   [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions     =   true;

    /**
     * ProductService
     * @var ProductService
     */
    protected ProductService $productService;

    /**
     * Define Constructor
     * @param
     */
    public function __construct()
    {
        parent::__construct();
        // Filter sorting
        $this->listWhere    =   [
            'nexopos_products.author' => [ Auth::id() ],
        ];
        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'addActions' ], 10, 2 );
        $this->productService = app()->make( ProductService::class );
    }

    // Filter items by author when passed with query parameters by the controller
    public function hook( $query ): void
    {
        if ( ! empty( request()->query( 'author' ) ) ) {
            $query->where( 'nexopos_products.author', request()->query( 'author' ) );
        }
    }

    /**
     * Return the label used for the crud
     * instance
     * @return array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Products List' ),
            'list_description'      =>  __( 'Display all products.' ),
            'no_entry'              =>  __( 'No products has been registered' ),
            'create_new'            =>  __( 'Add a new product' ),
            'create_title'          =>  __( 'Create an Item' ),
            'create_description'    =>  __( 'Create a consignment sale item' ),
            'edit_title'            =>  __( 'Edit Item' ),
            'edit_description'      =>  __( 'Edit a consignment sale item' ),
            'back_to_list'          =>  __( 'Return to Products' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     * @return boolean
    **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Fields
     * @param object/null
     * @return array of field
     */
    public function getForm( $entry = null )
    {

        if ( $entry instanceof Product ) {

            $unitGroup = UnitGroup::where( 'id', $entry->unit_group )->with( 'units' )->first() ?? [];

            // Get a handle to product unit quantities (by productID) and peel off the quantity and sale_price
            $unitQuantity = $this->getUnitQuantity(
                $entry->id,
                $unitGroup->id
            );

            $entry->sale_price_edit = $unitQuantity->sale_price_edit;
            $entry->quantity = $unitQuantity->quantity;

        }

        return [
            'main' =>  [
                'label'         =>  __( 'Name' ),
                // 'name'          =>  'name',
                // 'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
//                        [
//                            'type'  =>  'text',
//                            'name'  =>  'id',
//                            'label' =>  __( 'Id' ),
//                            'value' =>  $entry->id ?? '',
//                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'label' =>  __( 'Name' ),
                            'value' =>  $entry->name ?? '',
                            //'validation' => 'required',
                            'validation' => 'required|max:25',
                            'description' => 'Max 25 characters',

                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'tax_type',
//                            'label' =>  __( 'Tax_type' ),
//                            'value' =>  $entry->tax_type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'tax_group_id',
//                            'label' =>  __( 'Tax_group_id' ),
//                            'value' =>  $entry->tax_group_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'tax_value',
//                            'label' =>  __( 'Tax_value' ),
//                            'value' =>  $entry->tax_value ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'product_type',
//                            'label' =>  __( 'Product_type' ),
//                            'value' =>  $entry->product_type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'type',
//                            'label' =>  __( 'Type' ),
//                            'value' =>  $entry->type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'accurate_tracking',
//                            'label' =>  __( 'Accurate_tracking' ),
//                            'value' =>  $entry->accurate_tracking ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'status',
//                            'label' =>  __( 'Status' ),
//                            'value' =>  $entry->status ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'stock_management',
//                            'label' =>  __( 'Stock_management' ),
//                            'value' =>  $entry->stock_management ?? '',
//                        ], [
//                            'type'  =>  'hidden',
//                            'name'  =>  'barcode',
//                            'label' =>  __( 'Barcode' ),
//                            'value' =>  $entry->barcode ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'barcode_type',
//                            'label' =>  __( 'Barcode_type' ),
//                            'value' =>  $entry->barcode_type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'sku',
//                            'label' =>  __( 'Sku' ),
//                            'value' =>  $entry->sku ?? '',
//                        ], [
                            'type'  =>  'text',
                            'name'  =>  'sale_price_edit',
                            'label' =>  __( 'Sale Price' ),
                            'value' =>  $entry->sale_price_edit ?? '',
                            'validation' => 'required',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'quantity',
                            'label' =>  __( 'Quantity' ),
                            'value' =>  $entry->quantity ?? '',
                            'validation' => 'required',
                        ], [
                            'type'  =>  'textarea',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'thumbnail_id',
//                            'label' =>  __( 'Thumbnail_id' ),
//                            'value' =>  $entry->thumbnail_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'category_id',
//                            'label' =>  __( 'Category_id' ),
//                            'value' =>  $entry->category_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'parent_id',
//                            'label' =>  __( 'Parent_id' ),
//                            'value' =>  $entry->parent_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'unit_group',
//                            'label' =>  __( 'Unit_group' ),
//                            'value' =>  $entry->unit_group ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'on_expiration',
//                            'label' =>  __( 'On_expiration' ),
//                            'value' =>  $entry->on_expiration ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'expires',
//                            'label' =>  __( 'Expires' ),
//                            'value' =>  $entry->expires ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'searchable',
//                            'label' =>  __( 'Searchable' ),
//                            'value' =>  $entry->searchable ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'author',
//                            'label' =>  __( 'Author' ),
//                            'value' =>  $entry->author ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'uuid',
//                            'label' =>  __( 'Uuid' ),
//                            'value' =>  $entry->uuid ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'created_at',
//                            'label' =>  __( 'Created_at' ),
//                            'value' =>  $entry->created_at ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'updated_at',
//                            'label' =>  __( 'Updated_at' ),
//                            'value' =>  $entry->updated_at ?? '',
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * Filter POST input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {

        $this->validatePriceAndQty($inputs);

        /*
         * Technically we should use the barcodeService to generate this
         * but I'm not sure how to get a handle to that singleton atm,
         * so just borrow the code for code128
         * We could instantiate a BarcodeService, or get it from $self-ProductService
         */
        $inputs[ 'barcode_type' ] = BarcodeService::TYPE_CODE128;
        $inputs[ 'barcode' ] = Str::random(10);

        // Hardcode category to Consignment
        $category = ProductCategory::where( 'name', 'Consignment' )->first();
        if (!isset($category)) {
            throw new Exception( __( 'Please have the admin create a product category named Consignment' ) );
        }

        // Other Inputs
        $inputs[ 'sku' ] = Str::slug( $category->name ) . '--' . Str::slug( $inputs[ 'name' ] ) . '--' . strtolower( Str::random(5) );
        $inputs[ 'category_id' ] = $category->id;
        $inputs[ 'unit_group' ] = 1;    // hardcode to consignment
        $inputs[ 'author' ] = Auth::id();
        $inputs[ 'type' ] = Product::TYPE_MATERIALIZED;
        $inputs[ 'tax_type'] = 'inclusive';

        //ConsignmentModule::DumpVar($inputs);

        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, Product $entry )
    {
        $this->validatePriceAndQty($inputs);
        return $inputs;
    }

    /**
     * Validate Price And Qty
     * @param array of fields
     * @throws Exception
     */
    public function validatePriceAndQty( $inputs )
    {
        $price = $inputs[ 'sale_price_edit'];
        $quantity = $inputs[ 'quantity'];

        if (!is_numeric($price) || $price <= 0) {
            throw new Exception( __( 'Sale Price must be a positive number.' ) );
        }

        if (!is_numeric($quantity)) {
            throw new Exception( __('Quantity must be a positive whole number.' ) );
        }

        if ($quantity <= 0 || (floor($quantity) != $quantity) ) {
            throw new Exception( __('Quantity must be a positive whole number.' ) );
        }

    }

    /**
     * Before saving a record
     * @param Request $request
     * @return void
     */
    public function beforePost( $request )
    {

        // Author Check not required on post as the item will always be created with the logged on user's AuthID

        if ( $this->permissions[ 'create' ] !== false ) {
            ns()->restrict( $this->permissions[ 'create' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After saving a record
     * @param Request $request
     * @param Product $entry
     * @return void
     */
    public function afterPost( $request, Product $entry )
    {
        // Request is the form data, Entry is the Product that was created

        // Hardcode units for Consignment items - TODO: Make dynamic based on name
        $request[ 'units' ] = array
        (
            'accurate_tracking' => 0,
            'unit_group' => 1,                      // Assume unit_group 1 is "consignment" created by us during setup
            'selling_group' =>
                array (
                    0 =>
                        array (
                            'unit_id' => 1,         // Assume unit_id 1 is "single" created by us during setup
                            'sale_price_edit' => round($request[ 'sale_price_edit' ], 2),
                            'quantity' => $request[ 'quantity' ],
                            'wholesale_price_edit' => round($request[ 'sale_price_edit' ], 2),
                        ),
                ),
        );

        // This will create the associated product_unit_quantities record based on the hardcoded array above
        $this->__computeUnitQuantities( $request,  $entry );

        // Generate Barcode images
        $this->productService->generateProductBarcode( $entry );

        return $request;
    }

    // Borrowed from ProductService
    private function __computeUnitQuantities( $fields, Product $product )
    {
        if ( $fields[ 'units' ] ) {

            // TODO: Get the CurrencyService from $self->ProductService
            /**
             * @var CurrencyService
             */
            //$currencyService = app()->make( CurrencyService::class );
            /**
             * @var TaxService
             */
            $taxService = app()->make( TaxService::class );

            foreach ( $fields[ 'units' ][ 'selling_group' ] as $group ) {
                $unitQuantity = $this->getUnitQuantity(
                    $product->id,
                    $group[ 'unit_id' ]
                );

                if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
                    $unitQuantity = new ProductUnitQuantity;
                    $unitQuantity->unit_id = $group[ 'unit_id' ];
                    $unitQuantity->product_id = $product->id;
                    $unitQuantity->quantity = $group[ 'quantity' ]; // new item
                } else {
                    $unitQuantity->quantity = $group[ 'quantity' ]; // edit item
                }

                /**
                 * We don't need to save all the information
                 * available on the group variable, that's why we define
                 * explicitly how everything is saved here.
                 */

                // The $currencyService we created isn't initialized properly, so we're losing precision in the prices
                /*
                $unitQuantity->sale_price = $currencyService->define( $group[ 'sale_price_edit' ] )->getRaw();
                $unitQuantity->sale_price_edit = $currencyService->define( $group[ 'sale_price_edit' ] )->getRaw();
                $unitQuantity->wholesale_price_edit = $currencyService->define( $group[ 'wholesale_price_edit' ] )->getRaw();
                */

                $unitQuantity->sale_price = $group[ 'sale_price_edit' ];
                $unitQuantity->sale_price_edit = $group[ 'sale_price_edit' ];
                $unitQuantity->wholesale_price_edit = $group[ 'wholesale_price_edit' ];
                $unitQuantity->preview_url = $group[ 'preview_url' ] ?? '';
                $unitQuantity->low_quantity = $group[ 'low_quantity' ] ?? 0;
                $unitQuantity->stock_alert_enabled = $group[ 'stock_alert_enabled' ] ?? false;

                // Tax service seems to be working as-is.  It will populate the with and without price fields
                /**
                 * Let's compute the tax only
                 * when the tax group is provided.
                 */
                $taxService->computeTax(
                    $unitQuantity,
                    $fields[ 'tax_group_id' ] ?? null,
                    $fields[ 'tax_type' ] ?? null
                );

                /**
                 * save custom barcode for the created unit quantity
                 */
                $unitQuantity->barcode = $product->barcode . '-' . $unitQuantity->id;
                $unitQuantity->save();
            }
        }
    }

    public function getUnitQuantity( $product_id, $unit_id )
    {
        return ProductUnitQuantity::withProduct( $product_id )
            ->withUnit( $unit_id )
            ->first();
    }

    /**
     * get
     * @param string
     * @return mixed
     */
    public function get( $param )
    {
        switch( $param ) {
            case 'model' :
                return $this->model ;
                break;
        }
    }

    /**
     * Before updating a record
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function beforePut( $request, $entry )
    {
        // Request is the form data, Entry is the Product being Modified

        // This prevents someone editing another's item
        ConsignmentModule::CheckAuthor($entry->author);

        if ( $this->permissions[ 'update' ] !== false ) {
            ns()->restrict( $this->permissions[ 'update' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After updating a record
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function afterPut( $request, $entry )
    {
        $this->afterPost($request, $entry); // update price & qty
        return $request;
    }

    /**
     * Before Delete
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model ) {

        if ( $namespace == 'consignment.products' ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' )
             *  ], 403 );
            **/
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );

                // This prevents someone from deleting another's item
                ConsignmentModule::CheckAuthor($model->author);

                $this->deleteProductAttachedRelation( $model );

            } else {
                throw new NotAllowedException;
            }
        }
    }

    public function deleteProductAttachedRelation( $model )
    {
        //$model->sub_items()->delete();
        //$model->galleries()->delete();
        //$model->variations()->delete();
        //$model->product_taxes()->delete();
        $model->unit_quantities()->delete();
    }

    /**
     * Define Columns
     * @return array of columns configuration
     */
    public function getColumns() {
        return [
//            'id'  =>  [
//                'label'  =>  __( 'Id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
            'name'  =>  [
                'label'  =>  __( 'Item' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'unit_sale_price_edit' => [
                'label' => __( 'Price' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'unit_quantity' => [
                'label' => __( 'Quantity' ),
                '$direction' => '',
                '$sort' => false,
            ],
//            'tax_type'  =>  [
//                'label'  =>  __( 'Tax_type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'tax_group_id'  =>  [
//                'label'  =>  __( 'Tax_group_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'tax_value'  =>  [
//                'label'  =>  __( 'Tax_value' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'product_type'  =>  [
//                'label'  =>  __( 'Product_type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'type'  =>  [
//                'label'  =>  __( 'Type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'accurate_tracking'  =>  [
//                'label'  =>  __( 'Accurate_tracking' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'status'  =>  [
//                'label'  =>  __( 'Status' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'stock_management'  =>  [
//                'label'  =>  __( 'Stock_management' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'barcode_type'  =>  [
//                'label'  =>  __( 'Barcode_type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'sku'  =>  [
//                'label'  =>  __( 'Sku' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'description'  =>  [
//                'label'  =>  __( 'Description' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
            'barcode'  =>  [
                'label'  =>  __( 'Barcode' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'user_username'  =>  [
                'label'  =>  __( 'Username' ),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
//            'thumbnail_id'  =>  [
//                'label'  =>  __( 'Thumbnail_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'category_id'  =>  [
//                'label'  =>  __( 'Category_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'parent_id'  =>  [
//                'label'  =>  __( 'Parent_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'unit_group'  =>  [
//                'label'  =>  __( 'Unit_group' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'on_expiration'  =>  [
//                'label'  =>  __( 'On_expiration' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'expires'  =>  [
//                'label'  =>  __( 'Expires' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'searchable'  =>  [
//                'label'  =>  __( 'Searchable' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'uuid'  =>  [
//                'label'  =>  __( 'Uuid' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'created_at'  =>  [
//                'label'  =>  __( 'Created_at' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'updated_at'  =>  [
//                'label'  =>  __( 'Updated_at' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
        ];
    }

    /**
     * Define actions
     */
    public function addActions( CrudEntry $entry, $namespace )
    {
        /**
         * Declaring entry actions
         */
        $entry->addAction( 'edit', [
            'label'         =>      __( 'Edit' ),
            'namespace'     =>      'edit',
            'type'          =>      'GOTO',
            'url'           =>      ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id )
        ]);

        $entry->addAction( 'delete', [
            'label'     =>  __( 'Delete' ),
            'namespace' =>  'delete',
            'type'      =>  'DELETE',
            'url'       =>  ns()->url( '/api/nexopos/v4/crud/consignment.products/' . $entry->id ),
            'confirm'   =>  [
                'message'  =>  __( 'Would you like to delete this ?' ),
            ]
        ]);

        return $entry;
    }


    /**
     * Bulk Delete Action
     * @param  object Request with object
     * @return  false/array
     */
    public function bulkAction( Request $request )
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */

        $isAdmin = ns()->allowedTo([ 'nexopos.consignment.admin-features' ]);

        if ( $request->input( 'action' ) == 'delete_selected' ) {

            /**
             * Will control if the user has the permissoin to do that.
             */
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }

            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Product && ( $entity->author === Auth::id() || $isAdmin ) ) {   // prevent bulk-delete of another's items.  admin users can bulk-delete regardless of author id
                    $this->deleteProductAttachedRelation( $entity );
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }
            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    /**
     * get Links
     * @return array of links
     */
    public function getLinks(): array
    {
        return  [
            'list'      =>  ns()->url( 'dashboard/' . 'consignment/products' ),
            'create'    =>  ns()->url( 'dashboard/' . 'consignment/products/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'consignment/products/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.products' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.products/{id}' . '' ),
        ];
    }

    /**
     * Get Bulk actions
     * @return array of actions
    **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label'         =>  __( 'Delete Selected Groups' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' =>  $this->namespace
                ])
            ]
        ]);
    }

    /**
     * get exports
     * @return array of export formats
    **/
    public function getExports()
    {
        return [];
    }
}
