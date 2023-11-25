<?php

use App\Classes\Hook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div class="h-full flex-auto flex flex-col">
        @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
        <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
            <div class="-m-4 flex flex-wrap" id="dashboard-cards">

                <div onclick="location.href='faq';" style="cursor: pointer;" class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-info-secondary to-info-tertiary px-3 py-5">
                        <div class="w-1 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h3 class="text-2xl font-black">
                                FAQ
                            </h3>
                        </div>
                    </div>
                </div>

                <div onclick="location.href='consignor-edit';" style="cursor: pointer;" class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-green-400 to-green-600 px-3 py-5">
                        <div class="w-1 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h3 class="text-2xl font-black">
                                Payment Prefs
                            </h3>
                        </div>
                    </div>
                </div>

                <div onclick="location.href='reports/consignor-sales';" style="cursor: pointer;" class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-red-300 via-red-400 to-red-500 px-3 py-5">
                        <div class="w-1 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h3 class="text-2xl font-black">
                                My Sales
                            </h3>
                        </div>
                    </div>
                </div>

                <div onclick="location.href='products';" style="cursor: pointer;" class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-indigo-400 to-indigo-600 px-3 py-5">
                        <div class="w-1 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h3 class="text-2xl font-black">
                                My Items
                            </h3>
                        </div>
                    </div>
                </div>

                <div onclick="location.href='products';" style="cursor: pointer;" class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-purple-400 to-purple-600 px-3 py-5">
                        <div class="w-1 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h3 class="text-2xl font-black">
                                Print Labels
                            </h3>
                        </div>
                    </div>
                </div>

                <div onclick="location.href='products';" style="cursor: pointer;" class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-teal-500 to-teal-700 px-3 py-5">
                        <div class="w-1 md:w-full flex md:flex-col md:items-start items-center justify-center">
                            <h3 class="text-2xl font-black">
                                Contact Sellers
                            </h3>
                        </div>
                    </div>
                </div>


        </div>
    </div>
@endsection

