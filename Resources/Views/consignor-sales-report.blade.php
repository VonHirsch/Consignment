@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
    <ns-consignor-sales-report inline-template>
        <div id="report-section" class="px-4">
            <div class="flex -mx-2">
                {{--<div class="px-2">--}}
                    {{--<ns-date-time-picker :date="startDate" @change="setStartDate( $event )"></ns-date-time-picker>--}}
                {{--</div>--}}
                {{--<div class="px-2">--}}
                    {{--<ns-date-time-picker :date="endDate" @change="setEndDate( $event )"></ns-date-time-picker>--}}
                {{--</div>--}}
                <div class="px-2">
                    <button @click="loadReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                        <i class="las la-sync-alt text-xl"></i>
                        <span class="pl-2">{{ __( 'Refresh' ) }}</span>
                    </button>
                </div>
                <div class="px-2">
                    <button @click="printSaleReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                        <i class="las la-print text-xl"></i>
                        <span class="pl-2">{{ __( 'Print' ) }}</span>
                    </button>
                </div>
            </div>
            {{--<div class="flex -mx-2">--}}
                {{--<div class="px-2">--}}
                    {{--<button @click="printSaleReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">--}}
                        {{--<i class="las la-print text-xl"></i>--}}
                        {{--<span class="pl-2">{{ __( 'Print' ) }}</span>--}}
                    {{--</button>--}}
                {{--</div>--}}
                {{--<div class="px-2">--}}
                    {{--<button @click="openSettings()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">--}}
                        {{--<i class="las la-cogs text-xl"></i>--}}
                        {{--<span class="pl-2">{{ __( 'Type' ) }} : @{{ getType( reportType.value ) }}</span>--}}
                    {{--</button>--}}
                {{--</div>--}}
                {{--<div class="px-2">--}}
                    {{--<button @click="openUserFiltering()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">--}}
                        {{--<i class="las la-user text-xl"></i>--}}
                        {{--<span class="pl-2">{{ __( 'Filter By User' ) }} : @{{ selectedUser || __( 'All Users' ) }}</span>--}}
                    {{--</button>--}}
                {{--</div>--}}
            {{--</div>--}}
            <div id="sale-report" class="anim-duration-500 fade-in-entrance">
                <div class="flex w-full">
                    <div class="my-4 flex justify-between w-full">
                        <div class="text-secondary">
                            <ul>
                                <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                <li class="pb-1 border-b border-dashed">{{ __( 'Document : Consignor Sales Report' ) }}</li>
                                <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                            </ul>
                        </div>
                        <div>
                            <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                        </div>
                    </div>
                </div>
                <div>
                    <div class="-mx-4 flex md:flex-row flex-col">
                        <div class="w-full md:w-1/2 px-4">
                            <div class="shadow rounded my-4 ns-box">
                                <div class="border-b ns-box-body">
                                    <table class="table ns-table w-full">
                                        <tbody class="text-primary">
                                        {{--<tr class="">--}}
                                            {{--<td width="200" class="font-semibold p-2 border text-left bg-info-secondary border-info-primary text-white">{{ __( 'Sub Total' ) }}</td>--}}
                                            {{--<td class="p-2 border text-right border-info-primary">@{{ summary.subtotal | currency }}</td>--}}
                                        {{--</tr>--}}
                                        {{--<tr class="">--}}
                                            {{--<td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Sales Discounts' ) }}</td>--}}
                                            {{--<td class="p-2 border text-right border-error-primary">@{{ summary.sales_discounts | currency }}</td>--}}
                                        {{--</tr>--}}
                                        {{--<tr class="">--}}
                                            {{--<td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Sales Taxes' ) }}</td>--}}
                                            {{--<td class="p-2 border text-right border-error-primary">@{{ summary.sales_taxes | currency }}</td>--}}
                                        {{--</tr>--}}
                                        {{--<tr class="">--}}
                                            {{--<td width="200" class="font-semibold p-2 border text-left bg-info-secondary  text-white">{{ __( 'Shipping' ) }}</td>--}}
                                            {{--<td class="p-2 border text-right border-success-primary">@{{ summary.shipping | currency }}</td>--}}
                                        {{--</tr>--}}
                                        <tr class="">
                                            <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-success-secondary text-white">{{ __( 'Total' ) }}</td>
                                            <td class="p-2 border text-right border-success-primary">@{{ summary.total | currency }}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 px-4">
                        </div>
                    </div>
                </div>
                <div class="bg-box-background shadow rounded my-4" v-if="reportType.value === 'products_report'">
                    <div class="border-b border-box-edge">
                        <table class="table ns-table w-full">
                            <thead class="text-primary">
                            <tr>
                                <th class="border p-2 text-left">{{ __( 'Products' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Quantity' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Discounts' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Taxes' ) }}</th>
                                <th width="150" class="border p-2">{{ __( 'Total' ) }}</th>
                            </tr>
                            </thead>
                            <tbody class="text-primary">
                            <tr v-for="product of result" :key="product.id">
                                <td class="p-2 border">@{{ product.name }}</td>
                                <td class="p-2 border text-right">@{{ product.quantity }}</td>
                                <td class="p-2 border text-right">@{{ product.discount | currency }}</td>
                                <td class="p-2 border text-right">@{{ product.tax_value | currency }}</td>
                                <td class="p-2 border text-right">@{{ product.total_price | currency }}</td>
                            </tr>
                            </tbody>
                            <tfoot class="text-primary font-semibold">
                            <tr>
                                <td class="p-2 border text-primary"></td>
                                <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'quantity' ) }}</td>
                                <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'discount' ) | currency }}</td>
                                <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'tax_value' ) | currency }}</td>
                                <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_price' ) | currency }}</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </ns-consignor-sales-report>
@endsection

@section( 'layout.dashboard.footer.inject' )

    <script type="module">

        // can't seem to get this working...
        // import nsAlertPopup from './ns-alert-popup.vue';

        Vue.component( 'ns-consignor-sales-report', {
            data() {
                return {
                    // search plus / minus 1 month
                    startDate: moment().subtract(1, 'months'),
                    endDate: moment().add(1, 'months'),
                    result: [],
                    users: [],
                    summary: {},
                    selectedUser: '',
                    reportType: {
                        label: __( 'Report Type' ),
                        name: 'reportType',
                        type: 'select',
                        value: 'products_report',
                        options: [
                            {
                                label: __( 'Categories Detailed' ),
                                name: 'categories_report',
                            }, {
                                label: __( 'Categories Summary' ),
                                name: 'categories_summary',
                            },
                            {
                                label: __( 'Products' ),    // this is the only type we're going to offer atm
                                name: 'products_report',
                            }
                        ],
                        description: __( 'Allow you to choose the report type.' ),
                    },
                    filterUser: {
                        label: __( 'Filter User' ),
                        name: 'filterUser',
                        type: 'select',
                        value: '',
                        options: [
                            // ...
                        ],
                        description: __( 'Allow you to choose the report type.' ),
                    },
                    field: {
                        type: 'datetimepicker',
                        value: '2021-02-07',
                        name: 'date'
                    }
                }
            },
            components: {
                // nsAlertPopup
                // nsDatepicker,
                // nsDateTimePicker,
            },
            computed: {
                // ..
            },
            beforeMount() {
                // run the report on page load
                this.loadReport()
            },
            methods: {

                printSaleReport() {
                    this.$htmlToPaper( 'sale-report' );
                },
                setStartDate( moment ) {
                    this.startDate  =   moment.format();
                    console.log('setStartDate');
                },

                async openSettings() {
                    try {
                        const result    =   await new Promise( ( resolve, reject ) => {
                            Popup.show( nsSelectPopupVue, {
                                ...this.reportType,
                                resolve,
                                reject
                            });
                        });

                        this.reportType.value   =   result[0].name;
                        this.result             =   [];
                        this.loadReport();
                    } catch( exception ) {
                        // ...
                    }
                },

                async openUserFiltering() {
                    try {
                        /**
                         * let's try to pull the users first.
                         */
                        const result    =   await new Promise( ( resolve, reject ) => {
                            nsHttpClient.get( `/api/nexopos/v4/users` )
                                .subscribe({
                                    next: (users) => {
                                        this.users      =   users;

                                        this.filterUser.options     =   [
                                            {
                                                label: __( 'All Users' ),
                                                value: ''
                                            },
                                            ...this.users.map( user => {
                                                return {
                                                    label: user.username,
                                                    value: user.id
                                                }
                                            })
                                        ];

                                        Popup.show( nsSelectPopupVue, {
                                            ...this.filterUser,
                                            resolve,
                                            reject
                                        });
                                    },
                                    error: error => {
                                        nsSnackBar.error( __( 'No user was found for proceeding the filtering.' ) );
                                        reject( error );
                                    }
                                });
                        });

                        this.selectedUser       =   result[0].label;
                        this.filterUser.value   =   result[0].value;
                        this.result             =   [];
                        this.loadReport();
                    } catch( exception ) {
                        // ...
                    }
                },

                getType( type ) {
                    const option    =   this.reportType.options.filter( option => {
                        return option.name === type;
                    });

                    if ( option.length > 0 ) {
                        return option[0].label;
                    }

                    return __( 'Unknown' );
                },

                loadReport() {

                    console.log('loadReport');

                    if ( this.startDate === null || this.endDate ===null ) {
                        return nsSnackBar.error( __( 'Unable to proceed. Select a correct time range.' ) ).subscribe();
                    }

                    const startMoment   =   moment( this.startDate );
                    const endMoment     =   moment( this.endDate );

                    if ( endMoment.isBefore( startMoment ) ) {
                        return nsSnackBar.error( __( 'Unable to proceed. The current time range is not valid.' ) ).subscribe();
                    }

                    nsHttpClient.post( '/dashboard/consignment/reports/consignor-sales-report', {
                    //nsHttpClient.post( '/api/nexopos/v4/reports/sale-report', {
                        startDate: this.startDate,
                        endDate: this.endDate,
                        type: this.reportType.value,
                        user_id: this.filterUser.value
                    }).subscribe({
                        next: response => {
                            this.result     =   response.result;
                            this.summary    =   response.summary;
                        },
                        error : ( error ) => {
                            nsSnackBar.error( error.message ).subscribe();
                        }
                    });
                },

                computeTotal( collection, attribute ) {
                    if ( collection.length > 0 ) {
                        return collection.map( entry => parseFloat( entry[ attribute ] ) )
                            .reduce( ( b, a ) => b + a );
                    }

                    return 0;
                },

                setEndDate( moment ) {
                    this.endDate    =   moment.format();
                },
            }
        })
    </script>
@endsection
