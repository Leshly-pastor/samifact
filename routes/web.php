<?php

use App\Http\Controllers\Tenant\AdvancesController;
use App\Http\Controllers\Tenant\BillOfExchangeController;
use App\Http\Controllers\Tenant\BillOfExchangePayController;
use App\Http\Controllers\Tenant\MultiCompanyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\PersonController;
use App\Http\Controllers\Tenant\SettingController;
use App\Http\Controllers\Tenant\NameDocumentController;
use App\Http\Controllers\Tenant\NameQuotationsController;
use App\Http\Controllers\Tenant\PurchaseResponsibleLicenseController;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Http\Controllers\DashboardController;

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);
if ($hostname) {
    Route::domain($hostname->fqdn)->group(function () {
        Auth::routes([
            'register' => false,
            'verify'   => false
        ]);
        Route::post('/cambiar_contrasena', [UserController::class, 'cambiarContrasena'])->name('cambiar_contrasena');

        Route::get('restaurant/worker/print-ticket/{id}', '\Modules\Restaurant\Http\Controllers\OrdenController@printTicket');

        //Route::post('restaurant/login', '\Modules\Restaurant\Http\Controllers\RestaurantController@login');
        Route::get('search', 'Tenant\SearchController@index')->name('search.index');
        Route::get('buscar/{external_id?}', 'Tenant\SearchController@index')->name('search.index');
        Route::get('search/tables', 'Tenant\SearchController@tables');
        Route::post('search', 'Tenant\SearchController@store');

        Route::get('downloads/{model}/{type}/{external_id}/{format?}', 'Tenant\DownloadController@downloadExternal')->name('tenant.download.external_id');
        Route::get('print/{model}/{external_id}/{format?}', 'Tenant\DownloadController@toPrint');
        Route::get('printticket/{model}/{external_id}/{format?}', 'Tenant\DownloadController@toTicket');
        Route::get('/exchange_rate/ecommence/{date}', 'Tenant\Api\ServiceController@exchangeRateTest');

        Route::get('production-order/print/{external_id}/{format?}', 'Tenant\ProductionOrderController@toPrint');
        Route::get('dispatch-order/print/{external_id}/{format?}', 'Tenant\DispatchOrderController@toPrint');
        Route::get('sale-notes/tables-company/{company_id}', 'Tenant\SaleNoteController@tablesCompany');
        Route::get('sale-notes/print/{external_id}/{format?}', 'Tenant\SaleNoteController@toPrint');
        Route::get('sale-notes/ticket/{id}/{format?}', 'Tenant\SaleNoteController@toTicket');
        Route::get('purchases/print/{external_id}/{format?}', 'Tenant\PurchaseController@toPrint');

        Route::get('quotations/print/{external_id}/{format?}', 'Tenant\QuotationController@toPrint');

        Route::middleware(['auth', 'redirect.module', 'locked.tenant','locked.user'])->group(function () {

            Route::prefix('package-handler')->group(function () {
                Route::get('/', 'Tenant\PackageHandlerController@index')->name('tenant.package_handler.index');
                Route::post('/', 'Tenant\PackageHandlerController@store');
                Route::get('create/{packagehandler?}', 'Tenant\PackageHandlerController@create')->name('tenant.package_handler.create');
                Route::get('/records', 'Tenant\PackageHandlerController@records');
                Route::get('/export/excel', 'Tenant\SaleNoteController@excel');
                Route::get('/export_packages/excel', 'Tenant\PackageHandlerController@excelPackages');
                Route::get('/ticket/{id}', 'Tenant\PackageHandlerController@ticket');
                Route::get('/record/{id}', 'Tenant\PackageHandlerController@record');
                Route::get('/columns', 'Tenant\PackageHandlerController@columns');
                Route::get('/tables', 'Tenant\PackageHandlerController@tables');
                Route::get('/search/customer/{id}', 'Tenant\PackageHandlerController@searchCustomerById');
            });

            Route::prefix('multi-companies')->group(function () {
                Route::get('/', [MultiCompanyController::class, 'index'])->name('tenant.multi_companies.index');
                Route::post('/save-companies', [MultiCompanyController::class, 'saveCompanies']);
                Route::post('/save-configuration', [MultiCompanyController::class, 'saveConfiguration']);
                Route::post('/login', [MultiCompanyController::class, 'login']);

            });
            Route::prefix('document-columns')->group(function () {
                Route::get('/', 'Tenant\DocumentColumnController@index')->name('tenant.document_columns.index');
                Route::get('/records', 'Tenant\DocumentColumnController@records');
                Route::get('/record/{id}', 'Tenant\DocumentColumnController@record');
                Route::post('/', 'Tenant\DocumentColumnController@store');
            });
            Route::prefix('bill-of-exchange-pay')->group(function () {
                Route::get('/', [BillOfExchangePayController::class, 'index'])->name('tenant.bill_of_exchange_pay.index');
                Route::post('/', [BillOfExchangePayController::class, 'store']);
                // Route::get('create/{packagehandler?}', [BillOfExchangeController::class,'create'])->name('tenant.package_handler.create');
                Route::delete('/{id}', [BillOfExchangePayController::class, 'delete']);
                Route::delete('/payment/{id}', [BillOfExchangePayController::class, 'delete_payment']);
                Route::get('/records', [BillOfExchangePayController::class, 'records']);
                Route::get('/pdf/{id}', [BillOfExchangePayController::class, 'pdf']);
                Route::get('/payments/{id}', [BillOfExchangePayController::class, 'payments']);
                Route::post('/payments', [BillOfExchangePayController::class, 'store_payment']);
                Route::get('/record/{id}', [BillOfExchangePayController::class, 'record']);
                Route::get('/document/{id}', [BillOfExchangePayController::class, 'document']);
                Route::get('/columns', [BillOfExchangePayController::class, 'columns']);
                Route::get('/columns', [BillOfExchangePayController::class, 'columns']);
                Route::get('/list-by-client', [BillOfExchangePayController::class, 'documentsCreditByClient']);
                Route::get('/tables', [BillOfExchangePayController::class, 'tables']);
                // Route::get('/search/customer/{id}', [BillOfExchangeController::class,'searchCustomerById']);

            });
            Route::prefix('bill-of-exchange')->group(function () {
                Route::get('/', [BillOfExchangeController::class, 'index'])->name('tenant.bill_of_exchange.index');
                Route::post('/', [BillOfExchangeController::class, 'store']);
                // Route::get('create/{packagehandler?}', [BillOfExchangeController::class,'create'])->name('tenant.package_handler.create');
                Route::delete('/{id}', [BillOfExchangeController::class, 'delete']);
                Route::delete('/payment/{id}', [BillOfExchangeController::class, 'delete_payment']);
                Route::get('/records', [BillOfExchangeController::class, 'records']);
                Route::get('/pdf/{id}', [BillOfExchangeController::class, 'pdf']);
                Route::get('/payments/{id}', [BillOfExchangeController::class, 'payments']);
                Route::post('/payments', [BillOfExchangeController::class, 'store_payment']);
                Route::get('/record/{id}', [BillOfExchangeController::class, 'record']);
                Route::get('/document/{id}', [BillOfExchangeController::class, 'document']);
                Route::get('/columns', [BillOfExchangeController::class, 'columns']);
                Route::get('/columns', [BillOfExchangeController::class, 'columns']);
                Route::get('/list-by-client', [BillOfExchangeController::class, 'documentsCreditByClient']);
                Route::get('/tables', [BillOfExchangeController::class, 'tables']);
                // Route::get('/search/customer/{id}', [BillOfExchangeController::class,'searchCustomerById']);

            });

            Route::get('sunat_purchase_sale/records/{year}', [DashboardController::class, 'sunat_purchase_sale']);
            Route::post('sunat_purchase_sale', [DashboardController::class, 'save_sunat_purchase_sale']);

            Route::post('name_document', [NameDocumentController::class, 'store']);
            Route::post('ubigeo', [PersonController::class, 'ubigeo']);
            Route::get('name_document/record', [NameDocumentController::class, 'record']);

            Route::post('name_quotations', [NameQuotationsController::class, 'store']);
            Route::get('name_quotations/record', [NameQuotationsController::class, 'record']);
            //'Tenant\SettingController@document_quotations'
            Route::get('document-quotations', [SettingController::class, 'document_quotations'])->name('tenant.document_quotations.index');


            //Route::post('login', '\Modules\Restaurant\Http\Controllers\RestaurantController@login');
            Route::post('whatsapp', 'Tenant\WhatsappController@sendwhatsapp');
            Route::post('whatsapp/pos', 'Tenant\WhatsappController@sendwhatsapppos');
            // Route::get('catalogs', 'Tenant\CatalogController@index')->name('tenant.catalogs.index');
            Route::get('list-reports', 'Tenant\SettingController@listReports');
            Route::get('list-extras', 'Tenant\SettingController@listExtras');
            Route::get('list-settings', 'Tenant\SettingController@indexSettings')->name('tenant.general_configuration.index');
            Route::get('list-banks', 'Tenant\SettingController@listBanks');
            Route::get('list-bank-accounts', 'Tenant\SettingController@listAccountBanks');
            Route::get('list-currencies', 'Tenant\SettingController@listCurrencies');
            Route::get('list-cards', 'Tenant\SettingController@listCards');
            Route::get('list-platforms', 'Tenant\SettingController@listPlatforms');
            Route::get('list-agencies', 'Tenant\SettingController@listAgenciesTransport');

            Route::get('document-names', 'Tenant\SettingController@documentNames')->name('tenant.document_names.index');
            Route::get('yape-plin-qr', 'Tenant\SettingController@YaplePlinQr')->name('tenant.yape_plin_qr.index');
            //Route::get('document-quotations', 'Tenant\SettingController@document_quotations')->name('tenant.document_quotations.index');

            Route::get('inventory-references', 'Tenant\InventoryReferenceController@index')->name('tenant.inventory_references.index');
            Route::get('inventory-references/columns', 'Tenant\InventoryReferenceController@columns');
            Route::get('inventory-references/records', 'Tenant\InventoryReferenceController@records');
            Route::get('inventory-references/record/{id}', 'Tenant\InventoryReferenceController@record');
            Route::post('inventory-references', 'Tenant\InventoryReferenceController@store');
            Route::delete('inventory-references/{id}', 'Tenant\InventoryReferenceController@destroy');


            Route::get('list-attributes', 'Tenant\SettingController@listAttributes');
            Route::get('list-detractions', 'Tenant\SettingController@listDetractions');
            Route::get('list-units', 'Tenant\SettingController@listUnits');
            Route::post('list-units/store', 'Tenant\SettingController@storeUnits');
            Route::get('list-units/pdf', 'Tenant\SettingController@listUnitsPdf');
            Route::get('list-payment-methods', 'Tenant\SettingController@listPaymentMethods');
            Route::get('list-incomes', 'Tenant\SettingController@listIncomes');
            Route::get('list-payments', 'Tenant\SettingController@listPayments');
            Route::get('list-vouchers-type', 'Tenant\SettingController@listVouchersType');
            Route::get('list-transfer-reason-types', 'Tenant\SettingController@listTransferReasonTypes');

            Route::get('advanced', 'Tenant\AdvancedController@index')->name('tenant.advanced.index')->middleware('redirect.level');

            Route::get('tasks', 'Tenant\TaskController@index')->name('tenant.tasks.index')->middleware('redirect.level');
            Route::post('tasks/commands', 'Tenant\TaskController@listsCommand');
            Route::post('tasks/tables', 'Tenant\TaskController@tables');
            Route::post('tasks', 'Tenant\TaskController@store');
            Route::delete('tasks/{task}', 'Tenant\TaskController@destroy');

            //Orders
            Route::get('orders', 'Tenant\OrderController@index')->name('tenant_orders_index');
            Route::get('orders/columns', 'Tenant\OrderController@columns');
            Route::get('orders/records', 'Tenant\OrderController@records');
            Route::get('orders/record/{order}', 'Tenant\OrderController@record');
            //Route::get('orders/print/{external_id}/{format?}', 'Tenant\OrderController@toPrint');
            Route::post('statusOrder/update/', 'Tenant\OrderController@updateStatusOrders');
            Route::get('orders/pdf/{id}', 'Tenant\OrderController@pdf');

            //warehouse
            Route::post('orders/warehouse', 'Tenant\OrderController@searchWarehouse');
            Route::get('orders/tables', 'Tenant\OrderController@tables');

            Route::get('orders/tables/item/{internal_id}', 'Tenant\OrderController@item');

            //Status Orders
            Route::get('statusOrder/records', 'Tenant\StatusOrdersController@records');

            //Company
            Route::get('companies/create', 'Tenant\CompanyController@create')->name('tenant.companies.create')->middleware('redirect.level');
            Route::get('companies/tables', 'Tenant\CompanyController@tables');
            Route::get('companies/record', 'Tenant\CompanyController@record');
            Route::post('companies', 'Tenant\CompanyController@store');
            Route::post('companies/uploads', 'Tenant\CompanyController@uploadFile');

            //configuracion envio documento a

            Route::post('companies/store-send-pse', 'Tenant\CompanyController@storeSendPse');
            Route::get('companies/record-send-pse', 'Tenant\CompanyController@recordSendPse');
            Route::post('companies/pse', 'Tenant\CompanyController@storePse');
            Route::get('companies/pse', 'Tenant\CompanyController@recordPse');

            //configuracion WhatsApp Api
            Route::post('companies/store-whatsapp-api', 'Tenant\CompanyController@storeWhatsAppApi');
            Route::get('companies/record-whatsapp-api', 'Tenant\CompanyController@recordWhatsAppApi');


            //Card Brands
            Route::get('card_brands/records', 'Tenant\CardBrandController@records');
            Route::get('card_brands/record/{card_brand}', 'Tenant\CardBrandController@record');
            Route::post('card_brands', 'Tenant\CardBrandController@store');
            Route::delete('card_brands/{card_brand}', 'Tenant\CardBrandController@destroy');

            //Configurations
            Route::get('configurations/sale-notes', 'Tenant\SaleNoteController@SetAdvanceConfiguration')->name('tenant.sale_notes.configuration')->middleware('redirect.level');
            Route::post('configurations/sale-notes', 'Tenant\SaleNoteController@SaveSetAdvanceConfiguration');
            Route::get('configurations/shortcuts', 'Tenant\ConfigurationController@shortcuts')->name('tenant.shortcuts.index');
            Route::post('configurations/shortcuts', 'Tenant\ConfigurationController@store_shortcuts');
            Route::get('configurations/addSeeder', 'Tenant\ConfigurationController@addSeeder');
            Route::get('configurations/preprinted/addSeeder', 'Tenant\ConfigurationController@addPreprintedSeeder');
            Route::get('configurations/getFormats', 'Tenant\ConfigurationController@getFormats');
            Route::get('configurations/preprinted/getFormats', 'Tenant\ConfigurationController@getPreprintedFormats');
            Route::get('configurations/create', 'Tenant\ConfigurationController@create')->name('tenant.configurations.create');
            Route::get('configurations/record', 'Tenant\ConfigurationController@record');
            Route::post('configurations', 'Tenant\ConfigurationController@store');
            Route::post('configurations/apiruc', 'Tenant\ConfigurationController@storeApiRuc');
            Route::post('configurations/icbper', 'Tenant\ConfigurationController@icbper');
            Route::post('configurations/changeFormat', 'Tenant\ConfigurationController@changeFormat');
            Route::get('configurations/tables', 'Tenant\ConfigurationController@tables');
            Route::get('configurations/visual_defaults', 'Tenant\ConfigurationController@visualDefaults')->name('visual_defaults');
            Route::get('configurations/visual/get_menu', 'Tenant\ConfigurationController@visualGetMenu')->name('visual_get_menu');
            Route::post('configurations/visual/set_menu', 'Tenant\ConfigurationController@visualSetMenu')->name('visual_set_menu');
            Route::post('configurations/visual_settings', 'Tenant\ConfigurationController@visualSettings')->name('visual-settings');
            Route::post('configurations/visual/upload_skin', 'Tenant\ConfigurationController@visualUploadSkin')->name('visual_upload_skin');
            Route::post('configurations/visual/delete_skin', 'Tenant\ConfigurationController@visualDeleteSkin')->name('visual_delete_skin');
            Route::get('configurations/pdf_templates', 'Tenant\ConfigurationController@pdfTemplates')->name('tenant.advanced.pdf_templates');
            Route::get('configurations/pdf_guide_templates', 'Tenant\ConfigurationController@pdfGuideTemplates')->name('tenant.advanced.pdf_guide_templates');
            Route::get('configurations/pdf_preprinted_templates', 'Tenant\ConfigurationController@pdfPreprintedTemplates')->name('tenant.advanced.pdf_preprinted_templates');
            Route::post('configurations/uploads', 'Tenant\ConfigurationController@uploadFile');
            Route::post('configurations/upload_background', 'Tenant\ConfigurationController@uploadBackground');
            Route::post('configurations/preprinted/generateDispatch', 'Tenant\ConfigurationController@generateDispatch');
            Route::get('configurations/preprinted/{template}', 'Tenant\ConfigurationController@show');
            Route::get('configurations/change-mode', 'Tenant\ConfigurationController@changeMode')->name('settings.change_mode');

            Route::get('configurations/templates/ticket/refresh', 'Tenant\ConfigurationController@refreshTickets');
            Route::get('configurations/pdf_templates/ticket', 'Tenant\ConfigurationController@pdfTicketTemplates')->name('tenant.advanced.pdf_ticket_templates');
            Route::get('configurations/templates/ticket/records', 'Tenant\ConfigurationController@getTicketFormats');
            Route::post('configurations/templates/ticket/update', 'Tenant\ConfigurationController@changeTicketFormat');
            Route::get('configurations/apiruc', 'Tenant\ConfigurationController@apiruc');

            Route::post('configurations/pdf-footer-images', 'Tenant\ConfigurationController@pdfFooterImages');
            Route::get('configurations/get-pdf-footer-images', 'Tenant\ConfigurationController@getPdfFooterImages');

            //Certificates
            Route::get('certificates/record', 'Tenant\CertificateController@record');
            Route::post('certificates/uploads', 'Tenant\CertificateController@uploadFile');
            Route::delete('certificates', 'Tenant\CertificateController@destroy');

            //Establishments
            Route::get('establishments', 'Tenant\EstablishmentController@index')->name('tenant.establishments.index');
            Route::get('establishments/create', 'Tenant\EstablishmentController@create');
            Route::get('establishments/tables', 'Tenant\EstablishmentController@tables');
            Route::get('establishments/record/{establishment}', 'Tenant\EstablishmentController@record');
            Route::post('establishments', 'Tenant\EstablishmentController@store');
            Route::post('establishments/remove-image/{type}', 'Tenant\EstablishmentController@removeImage');
            Route::get('establishments/records', 'Tenant\EstablishmentController@records');
            Route::delete('establishments/{establishment}', 'Tenant\EstablishmentController@destroy');

            //Bank Accounts
            Route::get('bank_accounts', 'Tenant\BankAccountController@index')->name('tenant.bank_accounts.index');
            Route::get('bank_accounts/records', 'Tenant\BankAccountController@records');
            Route::get('bank_accounts/create', 'Tenant\BankAccountController@create');
            Route::get('bank_accounts/tables', 'Tenant\BankAccountController@tables');
            Route::get('bank_accounts/record/{bank_account}', 'Tenant\BankAccountController@record');
            Route::post('bank_accounts', 'Tenant\BankAccountController@store');
            Route::delete('bank_accounts/{bank_account}', 'Tenant\BankAccountController@destroy');

            //Series
            Route::get('series/records/{establishment}/{document_type?}', 'Tenant\SeriesController@records');
            Route::get('series/create', 'Tenant\SeriesController@create');
            Route::get('series/tables', 'Tenant\SeriesController@tables');
            Route::post('series', 'Tenant\SeriesController@store');
            Route::delete('series/{series}', 'Tenant\SeriesController@destroy');

            //Users
            Route::get('users', 'Tenant\UserController@index')->name('tenant.users.index');
            Route::get('users/create', 'Tenant\UserController@create')->name('tenant.users.create');
            Route::get('users/tables', 'Tenant\UserController@tables');
            Route::get('users/record/{user}', 'Tenant\UserController@record');
            Route::post('users', 'Tenant\UserController@store');
            Route::post('users/token/{user}', 'Tenant\UserController@regenerateToken');
            Route::get('users/records', 'Tenant\UserController@records');
            Route::post('users/lock', 'Tenant\UserController@lock');
            Route::post('users/unlock', 'Tenant\UserController@unlock');
            Route::get('users/records-lite', 'Tenant\UserController@records_lite');
            Route::delete('users/{user}', 'Tenant\UserController@destroy');

            //ChargeDiscounts
            Route::get('charge_discounts', 'Tenant\ChargeDiscountController@index')->name('tenant.charge_discounts.index');
            Route::get('charge_discounts/records/{type}', 'Tenant\ChargeDiscountController@records');
            Route::get('charge_discounts/create', 'Tenant\ChargeDiscountController@create');
            Route::get('charge_discounts/tables/{type}', 'Tenant\ChargeDiscountController@tables');
            Route::get('charge_discounts/record/{charge}', 'Tenant\ChargeDiscountController@record');
            Route::post('charge_discounts', 'Tenant\ChargeDiscountController@store');
            Route::delete('charge_discounts/{charge}', 'Tenant\ChargeDiscountController@destroy');

            //Items Ecommerce
            Route::get('items_ecommerce', 'Tenant\ItemController@index_ecommerce')->name('tenant.items_ecommerce.index');

            //Items
            Route::get('items', 'Tenant\ItemController@index')->name('tenant.items.index')->middleware('redirect.level');
            Route::get('services', 'Tenant\ItemController@indexServices')->name('tenant.services')->middleware('redirect.level');
            Route::get('items/columns', 'Tenant\ItemController@columns');
            Route::get('items/formats/items-update-prices-warehouses', 'Tenant\ItemController@templateUpdatePricesWarehouses');
            Route::get('items/formats/items-update-prices-person-type', 'Tenant\ItemController@templateUpdatePricesPersonType');
            Route::get('items/records', 'Tenant\ItemController@records');
            Route::get('items/erase/{item_id}', 'Tenant\ItemController@erase');
            Route::get('items/details/{item_id}', 'Tenant\ItemController@details');
            Route::get('items/tables', 'Tenant\ItemController@tables');
            Route::get('items/record/{item}', 'Tenant\ItemController@record');
            Route::post('items', 'Tenant\ItemController@store');
            Route::delete('items/{item}', 'Tenant\ItemController@destroy');
            Route::delete('items/item-unit-type/{item}', 'Tenant\ItemController@destroyItemUnitType');
            Route::post('items/import', 'Tenant\ItemController@import');
            Route::post('items/catalog', 'Tenant\ItemController@catalog');
            Route::get('items/import/tables', 'Tenant\ItemController@tablesImport');
            Route::post('items/upload', 'Tenant\ItemController@upload');
            Route::post('items/visible_store', 'Tenant\ItemController@visibleStore');
            Route::post('items/duplicate', 'Tenant\ItemController@duplicate');
            Route::get('items/disable/{item}', 'Tenant\ItemController@disable');
            Route::get('items/enable/{item}', 'Tenant\ItemController@enable');
            Route::get('items/images/{item}', 'Tenant\ItemController@images');
            Route::get('items/images/delete/{id}', 'Tenant\ItemController@delete_images');
            Route::get('items/export', 'Tenant\ItemController@export')->name('tenant.items.export');
            Route::get('items/export_migration', 'Tenant\ItemController@export_migration');
            Route::get('items/export/wp', 'Tenant\ItemController@exportWp')->name('tenant.items.export.wp');
            Route::get('items/export/digemid', 'Tenant\ItemController@exportDigemid');
            Route::get('items/export/digemid-csv', 'Tenant\ItemController@exportDigemidCsv');
            Route::get('items/search-items', 'Tenant\ItemController@searchItems');
            Route::get('items/search/item/{item}', 'Tenant\ItemController@searchItemById');
            Route::get('items/item/tables', 'Tenant\ItemController@item_tables');
            Route::get('items/export/barcode', 'Tenant\ItemController@exportBarCode')->name('tenant.items.export.barcode');
            Route::get('items/export/extra_atrributes/PDF', 'Tenant\ItemController@downloadExtraDataPdf');
            Route::get('items/export/extra_atrributes/XLSX', 'Tenant\ItemController@downloadExtraDataItemsExcel');
            Route::get('items/export/barcode_full', 'Tenant\ItemController@exportBarCodeFull');
            Route::get('items/export/barcode/print', 'Tenant\ItemController@printBarCode')->name('tenant.items.export.barcode.print');
            Route::get('items/export/barcode/print_x', 'Tenant\ItemController@printBarCodeX')->name('tenant.items.export.barcode.print.x');
            Route::get('items/export/barcode/last', 'Tenant\ItemController@itemLast')->name('tenant.items.last');
            Route::post('get-items', 'Tenant\ItemController@getAllItems');

            //Persons
            Route::prefix('persons')->group(function () {
                /**
                 *persons/columns
                 *persons/tables
                 *persons/{type}
                 *persons/{type}/records
                 *persons/
                 *persons/{person}
                 *persons/import
                 *persons/enabled/{type}/{person}
                 *persons/{type}/exportation
                 */
                Route::get('/last-no-document', 'Tenant\PersonController@getLastDocument');
                Route::get('/columns', 'Tenant\PersonController@columns');
                Route::get('/tables', 'Tenant\PersonController@tables');
                Route::get('/drivers', 'Tenant\PersonController@drivers')->name('tenant.persons_drivers.index');
                Route::get('/{type}', 'Tenant\PersonController@index')->name('tenant.persons.index');
                Route::get('/{type}/records', 'Tenant\PersonController@records');
                Route::get('/record/{person}', 'Tenant\PersonController@record');
                Route::post('', 'Tenant\PersonController@store');
                Route::delete('/{person}', 'Tenant\PersonController@destroy');
                Route::post('/import', 'Tenant\PersonController@import');
                Route::get('/enabled/{type}/{person}', 'Tenant\PersonController@enabled');
                Route::get('/{type}/export_migration', 'Tenant\PersonController@export_migration');
                Route::get('/{type}/exportation', 'Tenant\PersonController@export')->name('tenant.persons.export');
                Route::get('/export/barcode/print', 'Tenant\PersonController@printBarCode')->name('tenant.persons.export.barcode.print');
                Route::get('/barcode/{item}', 'Tenant\PersonController@generateBarcode');
                Route::get('/search/{barcode}', 'Tenant\PersonController@getPersonByBarcode');

                Route::get('accumulated-points/{id}', 'Tenant\PersonController@getAccumulatedPoints');
            });
            //Documents

            Route::post('documents/check-series', 'Tenant\DocumentController@checkSeries');

            Route::get('documents/tables-company/{company_id}', 'Tenant\DocumentController@tablesCompany');
            Route::get('documents/update-user/{user_id}/{document_id}', 'Tenant\DocumentController@updateUser');
            Route::get('documents/change_sire/{id}/{appendix}', 'Tenant\DocumentController@changeSire');
            Route::get('documents/check_pse/{id}', 'Tenant\DocumentController@checkPse');
            Route::get('documents/voided_pse/{id}', 'Tenant\DocumentController@anulatePse');
            Route::get('documents/voided_check_pse/{id}', 'Tenant\DocumentController@anulatePseCheck');
            Route::get('documents/json_pse/{id}', 'Tenant\DocumentController@jsonPse');
            Route::get('documents/voided_pdf/{id}', 'Tenant\DocumentController@voidedPdf');
            Route::post('documents/categories', 'Tenant\DocumentController@storeCategories');
            Route::post('documents/brands', 'Tenant\DocumentController@storeBrands');
            Route::get('documents/ind/{id}', 'Tenant\DocumentController@sendInd');
            Route::get('documents/change_state/{state_id}/{document_id}', 'Tenant\DocumentController@change_state');
            Route::get('documents/res/{id}', 'Tenant\DocumentController@sendRes');
            Route::get('documents/search/customers', 'Tenant\DocumentController@searchCustomers');
            Route::get('documents/copy/{id}', 'Tenant\DocumentController@copy');
            Route::get('documents/send_pse/{id}', 'Tenant\DocumentController@sendPse');

            Route::get('documents/search/customer/{id}', 'Tenant\DocumentController@searchCustomerById');
            Route::get('documents/search/externalId/{external_id}', 'Tenant\DocumentController@searchExternalId');

            Route::get('documents', 'Tenant\DocumentController@index')->name('tenant.documents.index')->middleware(['redirect.level', 'tenant.internal.mode']);
            Route::get('documents/columns', 'Tenant\DocumentController@columns');
            Route::get('documents/records', 'Tenant\DocumentController@records');
            Route::get('documents/recordsTotal', 'Tenant\DocumentController@recordsTotal');
            Route::get('documents/create', 'Tenant\DocumentController@create')->name('tenant.documents.create')->middleware(['redirect.level', 'tenant.internal.mode']);
            Route::get('documents/create_tensu', 'Tenant\DocumentController@create_tensu')->name('tenant.documents.create_tensu');
            Route::get('documents/{id}/edit', 'Tenant\DocumentController@edit')->middleware(['redirect.level', 'tenant.internal.mode']);
            Route::get('documents/{id}/show', 'Tenant\DocumentController@show');

            Route::get('documents/tables', 'Tenant\DocumentController@tables');
            Route::get('documents/duplicate/{id}', 'Tenant\DocumentController@duplicate');
            Route::get('documents/record/{document}', 'Tenant\DocumentController@record');
            Route::post('documents', 'Tenant\DocumentController@store');
            Route::post('documents/{id}/update', 'Tenant\DocumentController@update');
            Route::get('documents/send/{document}', 'Tenant\DocumentController@send');
            // Route::get('documents/remove/{document}', 'Tenant\DocumentController@remove');
            // Route::get('documents/consult_cdr/{document}', 'Tenant\DocumentController@consultCdr');
            Route::post('documents/email', 'Tenant\DocumentController@email');
            Route::get('documents/note/{document}', 'Tenant\NoteController@create');
            Route::get('documents/note_other', 'Tenant\NoteController@createOther');
            Route::get('documents/note/record/{document}', 'Tenant\NoteController@record');
            Route::get('documents/item/tables', 'Tenant\DocumentController@item_tables');
            Route::get('documents/table/{table}', 'Tenant\DocumentController@table');
            Route::get('documents/re_store/{document}', 'Tenant\DocumentController@reStore');
            Route::get('documents/locked_emission', 'Tenant\DocumentController@messageLockedEmission');
            Route::get('documents/note/has-documents/{document}', 'Tenant\NoteController@hasDocuments');

            Route::get('document_payments/records/{document_id}', 'Tenant\DocumentPaymentController@records');
            Route::get('document_payments/document/{document_id}', 'Tenant\DocumentPaymentController@document');
            Route::get('document_payments/tables', 'Tenant\DocumentPaymentController@tables');
            Route::post('document_payments', 'Tenant\DocumentPaymentController@store');
            Route::delete('document_payments/{document_payment}', 'Tenant\DocumentPaymentController@destroy');
            Route::get('document_payments/initialize_balance', 'Tenant\DocumentPaymentController@initialize_balance');
            Route::get('document_payments/report/{start}/{end}/{report}', 'Tenant\DocumentPaymentController@report');

            Route::get('documents/send_server/{document}/{query?}', 'Tenant\DocumentController@sendServer');
            Route::get('documents/check_server/{document}', 'Tenant\DocumentController@checkServer');
            Route::get('documents/change_to_registered_status/{document}', 'Tenant\DocumentController@changeToRegisteredStatus');

            Route::post('documents/import', 'Tenant\DocumentController@import');
            Route::post('documents/import_second_format', 'Tenant\DocumentController@importTwoFormat');
            Route::get('documents/data_table', 'Tenant\DocumentController@data_table');
            Route::get('documents/payments/excel/{month}/{anulled}', 'Tenant\DocumentController@report_payments')->name('tenant.document.payments.excel');
            Route::get('documents/payments-complete', 'Tenant\DocumentController@report_payments');


            Route::post('documents/import_excel_format', 'Tenant\DocumentController@importExcelFormat');
            Route::get('documents/import_excel_tables', 'Tenant\DocumentController@importExcelTables');


            Route::delete('documents/delete_document/{document_id}', 'Tenant\DocumentController@destroyDocument');
            Route::get('documents/kill/{document_id}', 'Tenant\DocumentController@killDocument');

            Route::get('documents/data-table/items', 'Tenant\DocumentController@getDataTableItem');
            Route::get('documents/retention/{document}', 'Tenant\DocumentController@retention');
            Route::post('documents/retention', 'Tenant\DocumentController@retentionStore');
            Route::post('documents/retention/upload', 'Tenant\DocumentController@retentionUpload');

            //Contingencies
            Route::get('contingencies', 'Tenant\ContingencyController@index')->name('tenant.contingencies.index')->middleware('redirect.level', 'tenant.internal.mode');
            Route::get('contingencies/columns', 'Tenant\ContingencyController@columns');
            Route::get('contingencies/records', 'Tenant\ContingencyController@records');
            Route::get('contingencies/create', 'Tenant\ContingencyController@create')->name('tenant.contingencies.create');

            //Summaries
            Route::get('summaries', 'Tenant\SummaryController@index')->name('tenant.summaries.index')->middleware('redirect.level', 'tenant.internal.mode');
            Route::get('summaries/records', 'Tenant\SummaryController@records');
            Route::post('summaries/documents', 'Tenant\SummaryController@documents');
            Route::post('summaries', 'Tenant\SummaryController@store');
            Route::get('summaries/status/{summary}', 'Tenant\SummaryController@status');
            Route::get('summaries/columns', 'Tenant\SummaryController@columns');
            Route::delete('summaries/{summary}', 'Tenant\SummaryController@destroy');
            Route::get('summaries/record/{summary}', 'Tenant\SummaryController@record');
            Route::get('summaries/regularize/{summary}', 'Tenant\SummaryController@regularize');
            Route::get('summaries/cancel-regularize/{summary}', 'Tenant\SummaryController@cancelRegularize');
            Route::get('summaries/tables', 'Tenant\SummaryController@tables');

            //Voided
            Route::get('voided', 'Tenant\VoidedController@index')->name('tenant.voided.index')->middleware('redirect.level', 'tenant.internal.mode');
            Route::get('voided/columns', 'Tenant\VoidedController@columns');
            Route::get('voided/records', 'Tenant\VoidedController@records');
            Route::post('voided', 'Tenant\VoidedController@store');
            //            Route::get('voided/download/{type}/{voided}', 'Tenant\VoidedController@download')->name('tenant.voided.download');
            Route::get('voided/status/{voided}', 'Tenant\VoidedController@status');
            Route::get('voided/status_masive', 'Tenant\VoidedController@status_masive');

            Route::delete('voided/{voided}', 'Tenant\VoidedController@destroy');
            //            Route::get('voided/ticket/{voided_id}/{group_id}', 'Tenant\VoidedController@ticket');

            //Retentions
            Route::get('retentions', 'Tenant\RetentionController@index')->name('tenant.retentions.index');
            Route::get('retentions/columns', 'Tenant\RetentionController@columns');
            Route::get('retentions/records', 'Tenant\RetentionController@records');
            Route::get('retentions/create', 'Tenant\RetentionController@create')->name('tenant.retentions.create');
            Route::get('retentions/tables', 'Tenant\RetentionController@tables');
            Route::get('retentions/record/{retention}', 'Tenant\RetentionController@record');
            Route::post('retentions', 'Tenant\RetentionController@store');
            Route::delete('retentions/{retention}', 'Tenant\RetentionController@destroy');
            Route::get('retentions/document/tables', 'Tenant\RetentionController@document_tables');
            Route::get('retentions/table/{table}', 'Tenant\RetentionController@table');

            /** Dispatches
             * dispatches
             * dispatches/columns
             * dispatches/records
             * dispatches/create/{document?}/{type?}/{dispatch?}
             * dispatches/tables
             * dispatches
             * dispatches/record/{id}
             * dispatches/sendSunat/{document}
             * dispatches/email
             * dispatches/generate/{sale_note}
             * dispatches/record/{id}/tables
             * dispatches/record/{id}/set-document-id
             * dispatches/search/customers
             * dispatches/search/customer/{id}
             * dispatches/client/{id}
             * dispatches/items
             * dispatches/data_table
             * dispatches/search/customer/{id}
             */
            Route::prefix('dispatches')->group(function () {
                Route::get('', 'Tenant\DispatchController@index')->name('tenant.dispatches.index');
                Route::get('/columns', 'Tenant\DispatchController@columns');
                Route::get('/records', 'Tenant\DispatchController@records');
                Route::get('/export-excel', 'Tenant\DispatchController@exportExcel');
                Route::get('/create/{document?}/{type?}/{dispatch?}', 'Tenant\DispatchController@create');
                Route::post('/tables', 'Tenant\DispatchController@tables');
                Route::post('', 'Tenant\DispatchController@store');
                Route::get('/record/{id}', 'Tenant\DispatchController@record');
                Route::post('/sendSunat/{document}', 'Tenant\DispatchController@sendDispatchToSunat');
                Route::post('/email', 'Tenant\DispatchController@email');
                Route::get('/check_pse/{id}', 'Tenant\DispatchController@download_file');
                Route::get('/send_pse/{id}', 'Tenant\DispatchController@send_pse');
                Route::get('/json_pse/{id}', 'Tenant\DispatchController@json_pse');
                Route::get('/generate/{sale_note}', 'Tenant\DispatchController@generate');
                Route::get('/record/{id}/tables', 'Tenant\DispatchController@generateDocumentTables');
                Route::post('/record/{id}/set-document-id', 'Tenant\DispatchController@setDocumentId');
                Route::get('/client/{id}', 'Tenant\DispatchController@dispatchesByClient');
                Route::post('/items', 'Tenant\DispatchController@getItemsFromDispatches');
                Route::post('/getDocumentType', 'Tenant\DispatchController@getDocumentTypeToDispatches');
                Route::get('/data_table', 'Tenant\DispatchController@data_table');
                Route::get('/search/customers', 'Tenant\DispatchController@searchCustomers');
                Route::get('/search/customer/{id}', 'Tenant\DispatchController@searchClientById');
                Route::post('/status_ticket', 'Tenant\Api\DispatchController@statusTicket');
                Route::get('create_new/{table}/{id}', 'Tenant\DispatchController@createNew');
                Route::get('/get_origin_addresses/{establishment_id}', 'Tenant\DispatchController@getOriginAddresses');
                Route::get('/get_delivery_addresses/{person_id}', 'Tenant\DispatchController@getDeliveryAddresses');
            });

            Route::prefix('dispatch_carrier')->group(function () {
                Route::get('', 'Tenant\DispatchCarrierController@index')->name('tenant.dispatch_carrier.index');
                Route::get('/columns', 'Tenant\DispatchCarrierController@columns');
                Route::get('/records', 'Tenant\DispatchCarrierController@records');
                Route::get('/export-excel', 'Tenant\DispatchCarrierController@ExportExcel');
                Route::get('/create/{document?}/{type?}/{dispatch?}', 'Tenant\DispatchCarrierController@create');
                Route::post('/tables', 'Tenant\DispatchCarrierController@tables');
                Route::post('', 'Tenant\DispatchCarrierController@store');
                Route::get('/record/{id}', 'Tenant\DispatchCarrierController@record');
                Route::post('/sendSunat/{document}', 'Tenant\DispatchCarrierController@sendDispatchToSunat');
                Route::post('/email', 'Tenant\DispatchCarrierController@email');
                Route::get('/generate/{sale_note}', 'Tenant\DispatchCarrierController@generate');
                Route::get('/record/{id}/tables', 'Tenant\DispatchCarrierController@generateDocumentTables');
                Route::post('/record/{id}/set-document-id', 'Tenant\DispatchCarrierController@setDocumentId');
                Route::get('/client/{id}', 'Tenant\DispatchCarrierController@dispatchesByClient');
                Route::post('/items', 'Tenant\DispatchCarrierController@getItemsFromDispatches');
                Route::post('/getDocumentType', 'Tenant\DispatchCarrierController@getDocumentTypeToDispatches');
                Route::get('/data_table', 'Tenant\DispatchCarrierController@data_table');
                Route::get('/search/customers', 'Tenant\DispatchCarrierController@searchCustomers');
                Route::get('/search/customer/{id}', 'Tenant\DispatchCarrierController@searchClientById');
                Route::post('/status_ticket', 'Tenant\Api\DispatchCarrierController@statusTicket');
                Route::get('create_new/{table}/{id}', 'Tenant\DispatchCarrierController@createNew');
                Route::get('/get_origin_addresses/{establishment_id}', 'Tenant\DispatchCarrierController@getOriginAddresses');
                Route::get('/get_delivery_addresses/{person_id}', 'Tenant\DispatchCarrierController@getDeliveryAddresses');
            });

            Route::get('customers/listById/{id}', 'Tenant\PersonController@clientsForGenerateCPEById');
            Route::get('customers/list', 'Tenant\PersonController@clientsForGenerateCPE');
            Route::get('suppliers/list', 'Tenant\PersonController@suppliersForGenerateCPE');
            Route::get('reports/consistency-documents', 'Tenant\ReportConsistencyDocumentController@index')->name('tenant.consistency-documents.index')->middleware('tenant.internal.mode');
            Route::post('reports/consistency-documents/lists', 'Tenant\ReportConsistencyDocumentController@lists');

            Route::post('options/delete_documents', 'Tenant\OptionController@deleteDocuments');
            Route::post('options/delete_items', 'Tenant\OptionController@delete_items');

            // apiperu no usa estas rutas - revisar
            Route::get('services/ruc/{number}', 'Tenant\Api\ServiceController@ruc');
            Route::get('services/dni/{number}', 'Tenant\Api\ServiceController@dni');
            Route::post('services/exchange_rate', 'Tenant\Api\ServiceController@exchange_rate');
            Route::post('services/search_exchange_rate', 'Tenant\Api\ServiceController@searchExchangeRateByDate');
            Route::get('services/exchange_rate/{date}', 'Tenant\Api\ServiceController@exchangeRateTest');

            //BUSQUEDA DE DOCUMENTOS
            // Route::get('busqueda', 'Tenant\SearchController@index')->name('search');
            // Route::post('busqueda', 'Tenant\SearchController@index')->name('search');

            //Codes
            Route::get('codes/records', 'Tenant\Catalogs\CodeController@records');
            Route::get('codes/tables', 'Tenant\Catalogs\CodeController@tables');
            Route::get('codes/record/{code}', 'Tenant\Catalogs\CodeController@record');
            Route::post('codes', 'Tenant\Catalogs\CodeController@store');
            Route::delete('codes/{code}', 'Tenant\Catalogs\CodeController@destroy');

            //Units
            Route::get('unitmeasure/records', 'Tenant\UnitTypeController@tables');
            Route::get('unit_types/records', 'Tenant\UnitTypeController@records');
            Route::get('unit_types/record/{code}', 'Tenant\UnitTypeController@record');
            Route::post('unit_types', 'Tenant\UnitTypeController@store');
            Route::delete('unit_types/{code}', 'Tenant\UnitTypeController@destroy');
            Route::get('unit_types/show_symbol/{code}', 'Tenant\UnitTypeController@show_symbol');

            //Transfer Reason Types
            Route::get('transfer-reason-types/records', 'Tenant\TransferReasonTypeController@records');
            Route::get('transfer-reason-types/record/{code}', 'Tenant\TransferReasonTypeController@record');
            Route::post('transfer-reason-types', 'Tenant\TransferReasonTypeController@store');
            Route::delete('transfer-reason-types/{code}', 'Tenant\TransferReasonTypeController@destroy');

            //Detractions
            Route::get('detraction_types/records', 'Tenant\DetractionTypeController@records');
            Route::get('detraction_types/tables', 'Tenant\DetractionTypeController@tables');
            Route::get('detraction_types/record/{code}', 'Tenant\DetractionTypeController@record');
            Route::post('detraction_types', 'Tenant\DetractionTypeController@store');
            Route::delete('detraction_types/{code}', 'Tenant\DetractionTypeController@destroy');

            //Banks
            Route::get('banks/records', 'Tenant\BankController@records');
            Route::get('banks/record/{bank}', 'Tenant\BankController@record');
            Route::post('banks', 'Tenant\BankController@store');
            Route::delete('banks/{bank}', 'Tenant\BankController@destroy');

            //Exchange Rates
            Route::get('exchange_rates/records', 'Tenant\ExchangeRateController@records');
            Route::post('exchange_rates', 'Tenant\ExchangeRateController@store');

            //Currency Types
            Route::get('exchange_currency', 'Tenant\ExchangeCurrencyController@index')->name('tenant.exchange_currency.index');
            Route::post('exchange_currency', 'Tenant\ExchangeCurrencyController@store');
            Route::get('exchange_currency/tables', 'Tenant\ExchangeCurrencyController@tables');
            Route::get('exchange_currency/records', 'Tenant\ExchangeCurrencyController@records');
            Route::get('exchange_currency/record/{id}', 'Tenant\ExchangeCurrencyController@record');
            Route::get('exchange_currency/{date}/{currency_id}', 'Tenant\ExchangeCurrencyController@exchange_date');

            Route::get('currency_types/records', 'Tenant\CurrencyTypeController@records');
            Route::get('currency_types/record/{currency_type}', 'Tenant\CurrencyTypeController@record');
            Route::post('currency_types', 'Tenant\CurrencyTypeController@store');
            Route::delete('currency_types/{currency_type}', 'Tenant\CurrencyTypeController@destroy');

            //Perceptions
            Route::get('perceptions', 'Tenant\PerceptionController@index')->name('tenant.perceptions.index');
            Route::get('perceptions/columns', 'Tenant\PerceptionController@columns');
            Route::get('perceptions/records', 'Tenant\PerceptionController@records');
            Route::get('perceptions/create', 'Tenant\PerceptionController@create')->name('tenant.perceptions.create');
            Route::get('perceptions/tables', 'Tenant\PerceptionController@tables');
            Route::get('perceptions/record/{perception}', 'Tenant\PerceptionController@record');
            Route::post('perceptions', 'Tenant\PerceptionController@store');
            Route::delete('perceptions/{perception}', 'Tenant\PerceptionController@destroy');
            Route::get('perceptions/document/tables', 'Tenant\PerceptionController@document_tables');
            Route::get('perceptions/table/{table}', 'Tenant\PerceptionController@table');

            //Tribute Concept Type
            Route::get('tribute_concept_types/records', 'Tenant\TributeConceptTypeController@records');
            Route::get('tribute_concept_types/record/{id}', 'Tenant\TributeConceptTypeController@record');
            Route::post('tribute_concept_types', 'Tenant\TributeConceptTypeController@store');
            Route::delete('tribute_concept_types/{id}', 'Tenant\TributeConceptTypeController@destroy');

            //purchases
            Route::get('purchases', 'Tenant\PurchaseController@index')->name('tenant.purchases.index');
            Route::post('purchases/exist', 'Tenant\PurchaseController@existPurchase');
            Route::get('purchases/columns', 'Tenant\PurchaseController@columns');
            Route::get('purchases/records', 'Tenant\PurchaseController@records');
            Route::get('purchases/create/{purchase_order_id?}', 'Tenant\PurchaseController@create')->name('tenant.purchases.create');
            Route::get('purchases/tables', 'Tenant\PurchaseController@tables');
            Route::get('purchases/table/{table}', 'Tenant\PurchaseController@table');
            Route::post('purchases', 'Tenant\PurchaseController@store');
            Route::post('purchases/update', 'Tenant\PurchaseController@update');
            Route::get('purchases/record/{document}', 'Tenant\PurchaseController@record');
            Route::get('purchases/edit/{id}', 'Tenant\PurchaseController@edit');
            Route::get('purchases/anular/{id}', 'Tenant\PurchaseController@anular');
            Route::post('purchases/guide/{purchase}', 'Tenant\PurchaseController@processGuides');
            Route::post('purchases/guide-file/upload', 'Tenant\PurchaseController@uploadAttached');
            Route::post('purchases/guide-file/upload', 'Tenant\PurchaseController@uploadAttached');
            Route::get('purchases/guides-file/download-file/{purchase}/{filename}', 'Tenant\PurchaseController@downloadGuide');
            Route::post('purchases/save_guide/{purchase}', 'Tenant\PurchaseController@processGuides');
            Route::get('purchases/delete/{id}', 'Tenant\PurchaseController@delete');
            Route::post('purchases/import', 'Tenant\PurchaseController@import');
            // Route::get('purchases/print/{external_id}/{format?}', 'Tenant\PurchaseController@toPrint');
            Route::get('purchases/search-items', 'Tenant\PurchaseController@searchItems');
            Route::get('purchases/search/item/{item}', 'Tenant\PurchaseController@searchItemById');
            Route::post('purchases/search/purchase_order', 'Tenant\PurchaseController@searchPurchaseOrder');
            // Route::get('purchases/item_resource/{id}', 'Tenant\PurchaseController@itemResource');

            // Route::get('documents/send/{document}', 'Tenant\DocumentController@send');
            // Route::get('documents/consult_cdr/{document}', 'Tenant\DocumentController@consultCdr');
            // Route::post('documents/email', 'Tenant\DocumentController@email');
            // Route::get('documents/note/{document}', 'Tenant\NoteController@create');
            Route::get('purchases/item/tables', 'Tenant\PurchaseController@item_tables');
            // Route::get('documents/table/{table}', 'Tenant\DocumentController@table');

            Route::delete('purchases/destroy_purchase_item/{purchase_item}', 'PurchaseController@destroy_purchase_item');

            Route::get('purchases-responsible/records', [PurchaseResponsibleLicenseController::class, 'responsible_records']);
            Route::post('purchases-responsible', [PurchaseResponsibleLicenseController::class, 'store_responsible']);
            Route::get('purchases-responsible/record/{id}', [PurchaseResponsibleLicenseController::class, 'responsible_record']);
            Route::get('purchases-license/records', [PurchaseResponsibleLicenseController::class, 'license_records']);
            Route::post('purchases-license', [PurchaseResponsibleLicenseController::class, 'store_license']);
            Route::post('purchases-license/record/{id}', [PurchaseResponsibleLicenseController::class, 'license_record']);
            //quotations
            Route::get('quotations', 'Tenant\QuotationController@index')->name('tenant.quotations.index')->middleware('redirect.level');
            Route::get('quotations/columns', 'Tenant\QuotationController@columns');
            Route::get('quotations/records', 'Tenant\QuotationController@records');
            Route::get('quotations/create/{saleOpportunityId?}', 'Tenant\QuotationController@create')->name('tenant.quotations.create')->middleware('redirect.level');
            Route::get('quotations/edit/{id}', 'Tenant\QuotationController@edit')->middleware('redirect.level');

            Route::get('quotations/state-type/{state_type_id}/{id}', 'Tenant\QuotationController@updateStateType');
            Route::get('quotations/filter', 'Tenant\QuotationController@filter');
            Route::get('quotations/tables', 'Tenant\QuotationController@tables');
            Route::get('quotations/table/{table}', 'Tenant\QuotationController@table');
            Route::post('quotations', 'Tenant\QuotationController@store');
            Route::post('quotations/update', 'Tenant\QuotationController@update');
            Route::get('quotations/record/{quotation}', 'Tenant\QuotationController@record');
            Route::get('quotations/anular/{id}', 'Tenant\QuotationController@anular');
            Route::get('quotations/item/tables', 'Tenant\QuotationController@item_tables');
            Route::get('quotations/option/tables/{id?}', 'Tenant\QuotationController@option_tables');
            Route::get('quotations/search/customers', 'Tenant\QuotationController@searchCustomers');
            Route::get('quotations/search/customer/{id}', 'Tenant\QuotationController@searchCustomerById');
            Route::get('quotations/download/{external_id}/{format?}', 'Tenant\QuotationController@download');
            // Route::get('quotations/print/{external_id}/{format?}', 'Tenant\QuotationController@toPrint');
            Route::post('quotations/email', 'Tenant\QuotationController@email');
            Route::post('quotations/duplicate', 'Tenant\QuotationController@duplicate');
            Route::get('quotations/record2/{quotation}', 'Tenant\QuotationController@record2');
            Route::get('quotations/changed/{quotation}', 'Tenant\QuotationController@changed');
            Route::post('quotations/change-description/{quotation}', 'Tenant\QuotationController@changed_description');

            Route::get('quotations/search-items', 'Tenant\QuotationController@searchItems');
            Route::get('quotations/search/item/{item}', 'Tenant\QuotationController@searchItemById');
            Route::get('quotations/item-warehouses/{item}', 'Tenant\QuotationController@itemWarehouses');
            //production-orders
            Route::prefix('message-integrate-system')
            ->group(function(){
                Route::get('/', 'Tenant\MessageIntegrateSystemController@index')
                ->name('tenant.message_integrate_system.index')
                ;
                Route::get('/columns', 'Tenant\MessageIntegrateSystemController@columns');
                Route::get('/records', 'Tenant\MessageIntegrateSystemController@records');
                Route::get('/record/{id}', 'Tenant\MessageIntegrateSystemController@record');
                Route::post('/', 'Tenant\MessageIntegrateSystemController@store');
            });
            //production-orders
            Route::prefix('agency')->group(function () {
                Route::get('columns', 'Tenant\AgencyController@columns');
                Route::get('records', 'Tenant\AgencyController@records');
                Route::get('record/{agency}', 'Tenant\AgencyController@record');
                Route::post('', 'Tenant\AgencyController@store');
                Route::post('/agency-dispatch', 'Tenant\AgencyController@saveAgencyDispatch');
                Route::get('/agency-dispatch/{record}', 'Tenant\AgencyController@getAgencyDispatch');
            });
            Route::prefix('integrate-system')
            ->group(function(){
                Route::get('user-types', 'Tenant\IntegrateSystemController@userTypes');
            });
            Route::prefix('production-order')->group(function () {
                Route::get('', 'Tenant\ProductionOrderController@index')->name('tenant.production_order.index')->middleware('redirect.level');
                Route::get('columns', 'Tenant\ProductionOrderController@columns');
                Route::get('states', 'Tenant\ProductionOrderController@states');
                Route::get('tables', 'Tenant\ProductionOrderController@tables');
                Route::get('search/customers', 'Tenant\ProductionOrderController@searchCustomers');
                Route::get('search/customer/{id}', 'Tenant\ProductionOrderController@searchCustomerById');
                Route::get('create/{dispatchorder?}', 'Tenant\ProductionOrderController@create')->name('tenant.production_order.create');
                Route::get('records', 'Tenant\ProductionOrderController@records');
                Route::post('', 'Tenant\ProductionOrderController@store');
                Route::get('record2/{order_production_id}', 'Tenant\ProductionOrderController@record2');
                Route::get('paymentdestinations/{userid?}', 'Tenant\ProductionOrderController@paymentdestinations');
                Route::get('users', 'Tenant\ProductionOrderController@users');
                Route::get('responsibles', 'Tenant\ProductionOrderController@responsibles');
                Route::get('set-responsible/{production_order_id}/{user_id}', 'Tenant\ProductionOrderController@setResponsible');
                Route::post('generate/{sale_note_id}', 'Tenant\ProductionOrderController@generateFromSaleNote');
                Route::get('records', 'Tenant\ProductionOrderController@records');
                Route::get('record/{production_order_id}', 'Tenant\ProductionOrderController@record');
                Route::get('change-state/{production_order_id}/{state_id}', 'Tenant\ProductionOrderController@changeState');
                Route::get('downloadExternal/{external_id}/{format?}', 'Tenant\ProductionOrderController@downloadExternal');
            });
            Route::prefix('production-order-payments')->group(function () {
                Route::get('document/{production_order_id}', 'Tenant\ProductionOrderPaymentController@document');
                Route::get('records/{production_order}', 'Tenant\ProductionOrderPaymentController@records');
            });
            //dispatch-orders
            Route::prefix('dispatch-order')->group(function () {
                Route::get('', 'Tenant\DispatchOrderController@index')->name('tenant.dispatch_order.index')->middleware('redirect.level');
                Route::get('columns', 'Tenant\DispatchOrderController@columns');
                Route::get('states', 'Tenant\DispatchOrderController@states');
                Route::get('tables', 'Tenant\DispatchOrderController@tables');
                Route::get('search/customers', 'Tenant\DispatchOrderController@searchCustomers');
                Route::get('search/customer/{id}', 'Tenant\DispatchOrderController@searchCustomerById');
                Route::get('create/{dispatchorder?}', 'Tenant\DispatchOrderController@create')->name('tenant.dispatch_order.create');
                Route::get('records', 'Tenant\DispatchOrderController@records');
                Route::post('', 'Tenant\DispatchOrderController@store');
                Route::get('record2/{dispatch_order_id}', 'Tenant\DispatchOrderController@record2');
                Route::get('paymentdestinations/{userid?}', 'Tenant\DispatchOrderController@paymentdestinations');
                Route::get('responsibles', 'Tenant\DispatchOrderController@responsibles');
                Route::get('users', 'Tenant\DispatchOrderController@users');
                Route::get('set-responsible/{dispatch_order_id}/{user_id}', 'Tenant\DispatchOrderController@setResponsible');
                Route::post('generate/{dispatch_order_id}', 'Tenant\DispatchOrderController@generateFromProductionOrder');
                Route::get('records', 'Tenant\DispatchOrderController@records');
                Route::get('record/{dispatch_order_id}', 'Tenant\DispatchOrderController@record');
                Route::get('change-state/{dispatch_order_id}/{state_id}', 'Tenant\DispatchOrderController@changeState');
                Route::get('downloadExternal/{external_id}/{format?}', 'Tenant\DispatchOrderController@downloadExternal');
            });
            Route::prefix('dispatch-order-payments')->group(function () {
                Route::get('document/{dispatch_order_id}', 'Tenant\DispatchOrderPaymentController@document');
                Route::get('records/{dispatch_order}', 'Tenant\DispatchOrderPaymentController@records');
            });
            //sale-notes
            Route::get('sale-notes', 'Tenant\SaleNoteController@index')->name('tenant.sale_notes.index')->middleware('redirect.level');
            Route::get('sale-notes/columns', 'Tenant\SaleNoteController@columns');
            Route::get('sale-notes/columns2', 'Tenant\SaleNoteController@columns2');
            Route::get('sale-notes/paymentdestinations/{userid?}', 'Tenant\SaleNoteController@paymentdestinations');
            Route::get('sale-notes/records', 'Tenant\SaleNoteController@records');
            Route::get('sale-notes/totals', 'Tenant\SaleNoteController@totals');
            // Route::get('sale-notes/create', 'Tenant\SaleNoteController@create')->name('tenant.sale_notes.create');
            Route::get('sale-notes/create/{salenote?}', 'Tenant\SaleNoteController@create')->name('tenant.sale_notes.create')->middleware('redirect.level');
            Route::get('sale-notes/receipt/{id}', 'Tenant\SaleNoteController@receipt');

            Route::get('sale-notes/tables', 'Tenant\SaleNoteController@tables');
            Route::post('sale-notes/UpToOther', 'Tenant\SaleNoteController@EnviarOtroSitio');
            Route::post('sale-notes/getUpToOther', 'Tenant\SaleNoteController@getSaleNoteToOtherSite');
            Route::post('sale-notes/urlUpToOther', 'Tenant\SaleNoteController@getSaleNoteToOtherSiteUrl');
            Route::post('sale-notes/duplicate', 'Tenant\SaleNoteController@duplicate');
            Route::get('sale-notes/table/{table}', 'Tenant\SaleNoteController@table');
            Route::post('sale-notes', 'Tenant\SaleNoteController@store');
            Route::get('sale-notes/record/{salenote}', 'Tenant\SaleNoteController@record');
            Route::get('sale-notes/item/tables', 'Tenant\SaleNoteController@item_tables');
            Route::get('sale-notes/search/customers', 'Tenant\SaleNoteController@searchCustomers');
            Route::get('sale-notes/search/customer/{id}', 'Tenant\SaleNoteController@searchCustomerById');
            // Route::get('sale-notes/print/{external_id}/{format?}', 'Tenant\SaleNoteController@toPrint');
            Route::get('sale-notes/change-state-payment/{salenote}/{stateEnum}', 'Tenant\SaleNoteController@changeStatePayment');
            Route::get('sale-notes/record2/{salenote}', 'Tenant\SaleNoteController@record2');
            Route::get('sale-notes/option/tables/{id?}', 'Tenant\SaleNoteController@option_tables');
            Route::get('sale-notes/changed/{salenote}', 'Tenant\SaleNoteController@changed');
            Route::post('sale-notes/email', 'Tenant\SaleNoteController@email');
            Route::post('sale-notes/send_email', 'Tenant\SaleNoteController@sendEmail');
            Route::get('sale-notes/print-a5/{sale_note_id}/{format}', 'Tenant\SaleNotePaymentController@toPrint');
            Route::get('sale-notes/dispatches', 'Tenant\SaleNoteController@dispatches');
            Route::delete('sale-notes/destroy_sale_note_item/{sale_note_item}', 'Tenant\SaleNoteController@destroy_sale_note_item');
            Route::get('sale-notes/search-items', 'Tenant\SaleNoteController@searchItems');
            Route::get('sale-notes/search/item/{item}', 'Tenant\SaleNoteController@searchItemById');
            Route::get('sale-notes/list-by-client', 'Tenant\SaleNoteController@saleNotesByClient');
            Route::post('sale-notes/items', 'Tenant\SaleNoteController@getItemsFromNotes');
            Route::get('sale-notes/config-group-items', 'Tenant\SaleNoteController@getConfigGroupItems');

            Route::get('sale_note_payments/records/{sale_note}', 'Tenant\SaleNotePaymentController@records');
            Route::get('sale_note_payments/document/{sale_note}', 'Tenant\SaleNotePaymentController@document');
            Route::get('sale_note_payments/tables', 'Tenant\SaleNotePaymentController@tables');
            Route::post('sale_note_payments', 'Tenant\SaleNotePaymentController@store');
            Route::delete('sale_note_payments/{sale_note_payment}', 'Tenant\SaleNotePaymentController@destroy');

            Route::post('sale-notes/enabled-concurrency', 'Tenant\SaleNoteController@enabledConcurrency');

            Route::get('sale-notes/anulate/{id}', 'Tenant\SaleNoteController@anulate');

            Route::get('sale-notes/downloadExternal/{external_id}/{format?}', 'Tenant\SaleNoteController@downloadExternal');

            Route::post('sale-notes/transform-data-order', 'Tenant\SaleNoteController@transformDataOrder');
            Route::post('sale-notes/items-by-ids', 'Tenant\SaleNoteController@getItemsByIds');
            Route::post('sale-notes/delete-relation-invoice', 'Tenant\SaleNoteController@deleteRelationInvoice');
            Route::get('sale-notes/kill/{id}', 'Tenant\SaleNoteController@killDocument');

            // Route::get('sale-notes/record-generate-document/{salenote}', 'Tenant\SaleNoteController@recordGenerateDocument');

            //POS
            Route::get('pos', 'Tenant\PosController@index')->name('tenant.pos.index');
            Route::get('pos_full', 'Tenant\PosController@index_full')->name('tenant.pos_full.index');

            Route::get('pos/search_items', 'Tenant\PosController@search_items');
            Route::get('pos/tables', 'Tenant\PosController@tables');
            Route::get('pos/table/{table}', 'Tenant\PosController@table');
            Route::get('pos/payment_tables', 'Tenant\PosController@payment_tables');
            Route::get('pos/payment', 'Tenant\PosController@payment')->name('tenant.pos.payment');
            Route::get('pos/status_configuration', 'Tenant\PosController@status_configuration');
            Route::get('pos/validate_stock/{item}/{quantity}', 'Tenant\PosController@validate_stock');
            Route::get('pos/items', 'Tenant\PosController@item');
            Route::get('pos/search_items_cat', 'Tenant\PosController@search_items_cat');
            Route::prefix('advances')->group(function (){
                Route::get('/', [AdvancesController::class, 'index'])->name('tenant.advances.index')
                ;
                Route::post('/', [AdvancesController::class, 'store']);
                Route::get('/records', [AdvancesController::class, 'records']);
                Route::get('/record/{id}', [AdvancesController::class, 'record']);
                Route::post('/advance_document', [AdvancesController::class, 'advanceDocument']);
                Route::get('/columns', [AdvancesController::class, 'columns']);
                Route::get('/type/{type}', [AdvancesController::class, 'index'])->name('tenant.advances.index');
                Route::delete('/{id}', [AdvancesController::class, 'destroy']);
                Route::get('/persons/{type}', [AdvancesController::class, 'persons']);
                Route::get('/get-advance/{person_id}', [AdvancesController::class, 'getAdvance']);
                Route::get('report-a4/{cash}', [AdvancesController::class,'reportA4']);
                Route::get('report-ticket/{cash}/{format?}', [AdvancesController::class, 'reportTicket']);
                Route::get('report-excel/{cash}', [AdvancesController::class, 'reportExcel']);
                Route::get('simple/report-a4/{cash}', [AdvancesController::class, 'reportSimpleA4']);
                // Route::get('report-cash-income-egress/{cash}', [AdvancesController::class, 'reportCashIncomeEgress']);
            });
            Route::get('cash', 'Tenant\CashController@index')->name('tenant.cash.index');
            Route::get('cash/get_cash/{user_id}', 'Tenant\CashController@getCashSeller');
            Route::get('cash/columns', 'Tenant\CashController@columns');
            Route::get('cash/records', 'Tenant\CashController@records');
            Route::get('cash/create', 'Tenant\CashController@create')->name('tenant.sale_notes.create');
            Route::get('cash/tables', 'Tenant\CashController@tables');
            Route::get('cash/opening_cash', 'Tenant\CashController@opening_cash');
            Route::get('cash/opening_cash_check/{user_id}', 'Tenant\CashController@opening_cash_check');

            Route::post('cash', 'Tenant\CashController@store');
            Route::post('cash/cash_document', 'Tenant\CashController@cash_document');
            Route::get('cash/close/{cash}', 'Tenant\CashController@close');
            Route::get('cash/re_open/{cash}', 'Tenant\CashController@re_open');
            Route::get('cash/report/{cash}', 'Tenant\CashController@report');
            Route::get('cash/report', 'Tenant\CashController@report_general');

            Route::get('cash/record/{cash}', 'Tenant\CashController@record');
            Route::delete('cash/{cash}', 'Tenant\CashController@destroy');
            Route::get('cash/item/tables', 'Tenant\CashController@item_tables');
            Route::get('cash/search/customers', 'Tenant\CashController@searchCustomers');
            Route::get('cash/search/customer/{id}', 'Tenant\CashController@searchCustomerById');

            Route::get('cash/report/products/{cash}/{is_garage?}', 'Tenant\CashController@report_products');
            Route::get('cash/report/products-excel/{cash}', 'Tenant\CashController@report_products_excel');
            Route::get('cash/report/cash-excel/{cash}', 'Tenant\CashController@report_cash_excel');

            //POS VENTA RAPIDA
            Route::get('pos/fast', 'Tenant\PosController@fast')->name('tenant.pos.fast');
            Route::get('pos/garage', 'Tenant\PosController@garage')->name('tenant.pos.garage');

            Route::get('shortcuts', 'Tenant\TutorialsController@index')->name('shortcuts.index');
            Route::get('shortcuts/columns', 'Tenant\TutorialsController@columns');
            Route::get('shortcuts/records', 'Tenant\TutorialsController@records');
            Route::get('shortcuts/record/{tag}', 'Tenant\TutorialsController@record');
            Route::post('shortcuts/uploads', 'Tenant\TutorialsController@subir_imagen');
            Route::post('shortcuts', 'Tenant\TutorialsController@store');
            Route::delete('shortcuts/{tag}', 'Tenant\TutorialsController@destroy');

            //Tags
            Route::get('tags', 'Tenant\TagController@index')->name('tenant.tags.index');
            Route::get('tags/columns', 'Tenant\TagController@columns');
            Route::get('tags/records', 'Tenant\TagController@records');
            Route::post('tags/{id}/upload', 'Tenant\TagController@updateFavicon');
            Route::get('tags/record/{tag}', 'Tenant\TagController@record');
            Route::post('tags', 'Tenant\TagController@store');
            Route::delete('tags/{tag}', 'Tenant\TagController@destroy');

            //Promotion
            Route::get('promotions', 'Tenant\PromotionController@index')->name('tenant.promotion.index');
            Route::get('promotions/columns', 'Tenant\PromotionController@columns');
            Route::get('promotions/tables', 'Tenant\PromotionController@tables');
            Route::get('promotions/records', 'Tenant\PromotionController@records');
            Route::get('promotions/record/{tag}', 'Tenant\PromotionController@record');
            Route::post('promotions', 'Tenant\PromotionController@store');
            Route::delete('promotions/{promotion}', 'Tenant\PromotionController@destroy');
            Route::post('promotions/upload', 'Tenant\PromotionController@upload');

            Route::get('item-sets', 'Tenant\ItemSetController@index')->name('tenant.item_sets.index')->middleware('redirect.level');
            Route::get('item-sets/columns', 'Tenant\ItemSetController@columns');
            Route::get('item-sets/records', 'Tenant\ItemSetController@records');
            Route::get('item-sets/tables', 'Tenant\ItemSetController@tables');
            Route::get('item-sets/record/{item}', 'Tenant\ItemSetController@record');
            Route::post('item-sets', 'Tenant\ItemSetController@store');
            Route::delete('item-sets/{item}', 'Tenant\ItemSetController@destroy');
            Route::delete('item-sets/item-unit-type/{item}', 'Tenant\ItemSetController@destroyItemUnitType');
            Route::post('item-sets/import', 'Tenant\ItemSetController@import');
            Route::post('item-sets/upload', 'Tenant\ItemSetController@upload');
            Route::post('item-sets/visible_store', 'Tenant\ItemSetController@visibleStore');
            Route::get('item-sets/item/tables', 'Tenant\ItemSetController@item_tables');

            Route::get('person-types/columns', 'Tenant\PersonTypeController@columns');
            Route::get('person-types', 'Tenant\PersonTypeController@index')->name('tenant.person_types.index');
            Route::get('person-types/records', 'Tenant\PersonTypeController@records');
            Route::get('person-types/record/{person}', 'Tenant\PersonTypeController@record');
            Route::post('person-types', 'Tenant\PersonTypeController@store');
            Route::delete('person-types/{person}', 'Tenant\PersonTypeController@destroy');

            //Cuenta
            Route::get('cuenta/payment_index', 'Tenant\AccountController@paymentIndex')->name('tenant.payment.index');
            Route::get('cuenta/configuration', 'Tenant\AccountController@index')->name('tenant.configuration.index');
            Route::get('cuenta/payment_records', 'Tenant\AccountController@paymentRecords');
            Route::get('cuenta/tables', 'Tenant\AccountController@tables');
            Route::post('cuenta/update_plan', 'Tenant\AccountController@updatePlan');
            Route::post('cuenta/payment_culqui', 'Tenant\AccountController@paymentCulqui')->name('tenant.account.payment_culqui');

            //Payment Methods
            Route::get('payment_method/records', 'Tenant\PaymentMethodTypeController@records');
            Route::get('payment_method/record/{code}', 'Tenant\PaymentMethodTypeController@record');
            Route::post('payment_method', 'Tenant\PaymentMethodTypeController@store');
            Route::delete('payment_method/{code}', 'Tenant\PaymentMethodTypeController@destroy');

            //formats PDF
            Route::get('templates', 'Tenant\FormatTemplateController@records');
            // Configuración del Login
            Route::get('login-page', 'Tenant\LoginConfigurationController@index')->name('tenant.login_page')->middleware('redirect.level');
            Route::post('login-page/upload-bg-image', 'Tenant\LoginConfigurationController@uploadBgImage');
            Route::post('login-page/update', 'Tenant\LoginConfigurationController@update');


            Route::post('extra_info/items', 'Tenant\ExtraInfoController@getExtraDataForItems');

            //liquidacion de compra
            Route::get('purchase-settlements', 'Tenant\PurchaseSettlementController@index')->name('tenant.purchase-settlements.index');
            Route::get('purchase-settlements/columns', 'Tenant\PurchaseSettlementController@columns');
            Route::get('purchase-settlements/records', 'Tenant\PurchaseSettlementController@records');

            Route::get('purchase-settlements/create/{order_id?}', 'Tenant\PurchaseSettlementController@create')->name('tenant.purchase-settlements.create');

            Route::post('purchase-settlements', 'Tenant\PurchaseSettlementController@store');
            Route::get('purchase-settlements/tables', 'Tenant\PurchaseSettlementController@tables');
            Route::get('purchase-settlements/table/{table}', 'Tenant\PurchaseSettlementController@table');
            Route::get('purchase-settlements/record/{document}', 'Tenant\PurchaseSettlementController@record');

            //Almacen de columnas por usuario
            Route::post('validate_columns', 'Tenant\SettingController@getColumnsToDatatable');

            Route::post('general-upload-temp-image', 'Controller@generalUploadTempImage');

            Route::get('general-get-current-warehouse', 'Controller@generalGetCurrentWarehouse');
            Route::get('questions', 'Tenant\WhatsappController@questions')->name('tenant.questions');
            Route::get('answers', 'Tenant\WhatsappController@answers')->name('tenant.answers');
            Route::get('account_whatsapp', 'Tenant\WhatsappController@account_whatsapp')->name('tenant.account.whatsapp');
            // test theme
            // Route::get('testtheme', function () {
            //     return view('tenant.layouts.partials.testtheme');
            // });
        });
    });
} else {
    $prefix = env('PREFIX_URL', null);
    $prefix = !empty($prefix) ? $prefix . "." : '';
    $app_url = $prefix . env('APP_URL_BASE');

    Route::domain($app_url)->group(function () {
        Route::get('login', 'System\LoginController@showLoginForm')->name('login');
        Route::post('login', 'System\LoginController@login');
        Route::post('logout', 'System\LoginController@logout')->name('logout');
        Route::get('phone', 'System\UserController@getPhone');
        Route::middleware('auth:admin')->group(function () {
            Route::get('php_version', function () {
                phpinfo();
            });
            Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
            Route::get('/', function () {
                return redirect()->route('system.dashboard');
            });
            Route::get('dashboard', 'System\HomeController@index')->name('system.dashboard');
            Route::post("users/create_admin", "System\UserController@create_admin");
            // Route::post("users/delete_admin", "System\UserController@delete_admin");
            //Clients
            Route::get('clients', 'System\ClientController@index')->name('system.clients.index');
            Route::get('clients/records', 'System\ClientController@records');
            Route::get('clients/record/{client}', 'System\ClientController@record');

            Route::get('clients/create', 'System\ClientController@create');
            Route::post('clients/mail', 'System\ClientController@email');
            Route::get('clients/tables', 'System\ClientController@tables');
            Route::get('clients/charts', 'System\ClientController@charts');
            Route::post('clients', 'System\ClientController@store');
            Route::post('clients/update', 'System\ClientController@update');

            Route::delete('clients/{client}/{input_validate}', 'System\ClientController@destroy');
            // Route::delete('clients/{client}', 'System\ClientController@destroy');

            Route::post('clients/password/{client}', 'System\ClientController@password');
            Route::post('clients/locked_emission', 'System\ClientController@lockedEmission');
            Route::post('clients/locked_tenant', 'System\ClientController@lockedTenant');
            Route::post('clients/config_system_env', 'System\ClientController@config_system_env');

            Route::post('clients/active_tenant', 'System\ClientController@activeTenant');
            // Route::post('clients/locked_tenant', 'System\ClientController@lockedTenant'); //Linea repetida

            Route::post('clients/locked_user', 'System\ClientController@lockedUser');
            Route::post('clients/locked_item', 'System\ClientController@lockedItem');
            Route::post('clients/renew_plan', 'System\ClientController@renewPlan');

            Route::post('clients/set_billing_cycle', 'System\ClientController@startBillingCycle');

            Route::post('clients/locked-by-column', 'System\ClientController@lockedByColumn');

            Route::post('clients/upload', 'System\ClientController@upload');
            Route::post('clients/cert/{type}/{client_id}', 'System\ClientController@store_cert_file');
            Route::delete('clients/cert/{type}/{client_id}', 'System\ClientController@delete_cert_file');

            Route::get('client_payments/records/{client_id}', 'System\ClientPaymentController@records');
            Route::get('client_payments/client/{client_id}', 'System\ClientPaymentController@client');
            Route::get('client_payments/tables', 'System\ClientPaymentController@tables');
            Route::delete('client_payments/delete_file/{payment_id}', 'System\ClientPaymentController@delete_file_payment');
            Route::post('client_payments', 'System\ClientPaymentController@store');
            Route::post('client_payments/file/{payment_id}', 'System\ClientPaymentController@store_file_payment');
            Route::delete('client_payments/{client_payment}', 'System\ClientPaymentController@destroy');
            Route::get('client_payments/cancel_payment/{client_payment_id}', 'System\ClientPaymentController@cancel_payment');

            Route::get('client_account_status/records/{client_id}', 'System\AccountStatusController@records');
            Route::get('client_account_status/client/{client_id}', 'System\AccountStatusController@client');
            Route::get('client_account_status/tables', 'System\AccountStatusController@tables');

            //Planes
            Route::get('plans', 'System\PlanController@index')->name('system.plans.index');
            Route::get('plans/records', 'System\PlanController@records');
            Route::get('plans/tables', 'System\PlanController@tables');
            Route::get('plans/record/{plan}', 'System\PlanController@record');
            Route::post('plans', 'System\PlanController@store');
            Route::delete('plans/{plan}', 'System\PlanController@destroy');

            //Users
            Route::get('users/create', 'System\UserController@create')->name('system.users.create');
            Route::get('users/record', 'System\UserController@record');
            Route::get('users/columns', 'System\UserController@columns');
            Route::post('users/columns', 'System\UserController@columns');
            Route::post('users', 'System\UserController@store');

            Route::get('services/ruc/{number}', 'System\ServiceController@ruc');

            Route::get('certificates/record', 'System\CertificateController@record');
            Route::post('certificates/uploads', 'System\CertificateController@uploadFile');
            Route::post('certificates/saveSoapUser', 'System\CertificateController@saveSoapUser');
            Route::delete('certificates', 'System\CertificateController@destroy');
            Route::get('configurations/record', 'System\ConfigurationController@record');
            //xd
            Route::get('403', 'System\ErrorsController@index')->name('system.403.index');
            Route::post('/errors/update', 'System\ErrorsController@update')->name('errors.update');
            Route::get('configurations', 'System\ConfigurationController@index')->name('system.configuration.index');
            Route::post('configurations/login', 'System\ConfigurationController@storeLoginSettings');

            Route::post('configurations/bg', 'System\ConfigurationController@storeBgLogin');
            Route::post('configurations/other-configuration', 'System\ConfigurationController@storeOtherConfiguration');
            Route::post('configurations/bg_imagen', 'System\ConfigurationController@bg_imagen');

            Route::get('companies/record', 'System\CompanyController@record');
            Route::post('companies', 'System\CompanyController@store');

            // auto-update
            Route::get('auto-update', 'System\UpdateController@index')->name('system.update');
            Route::get('auto-update/branch', 'System\UpdateController@branch')->name('system.update.branch');
            Route::get('auto-update/pull/{branch}', 'System\UpdateController@pull')->name('system.update.pull');
            Route::get('auto-update/artisan/migrate', 'System\UpdateController@artisanMigrate')->name('system.update.artisan.migrate');
            Route::get('auto-update/artisan/migrate/tenant', 'System\UpdateController@artisanTenancyMigrate')->name('system.update.artisan.tenancy.migrate');
            Route::get('auto-update/artisan/clear', 'System\UpdateController@artisanClear')->name('system.update.artisan.clear');
            Route::get('auto-update/composer/install', 'System\UpdateController@composerInstall')->name('system.update.composer.install');
            Route::get('auto-update/keygen', 'System\UpdateController@keygen')->name('system.update.keygen');
            Route::get('auto-update/version', 'System\UpdateController@version')->name('system.update.version');
            Route::get('auto-update/changelog', 'System\UpdateController@changelog')->name('system.changelog');

            //Configuration

            Route::post('configurations', 'System\ConfigurationController@store');
            Route::post('configurations/digemid', 'System\ConfigurationController@update_digemid');
            Route::get('configurations/record', 'System\ConfigurationController@record');
            Route::get('information', 'System\ConfigurationController@InfoIndex')->name('system.information');
            Route::get('status/history', 'System\StatusController@history')->name('system.status');
            Route::get('status/memory', 'System\StatusController@memory')->name('system.status.memory');
            Route::get('status/cpu', 'System\StatusController@cpu')->name('system.status.cpu');
            Route::get('configurations/apiruc', 'System\ConfigurationController@apiruc');
            Route::get('configurations/apkurl', 'System\ConfigurationController@apkurl');

            Route::get('configurations/update-tenant-discount-type-base', 'System\ConfigurationController@updateTenantDiscountTypeBase');


            // backup
            Route::get('backup', 'System\BackupController@index')->name('system.backup');
            Route::post('backup/db', 'System\BackupController@db')->name('system.backup.db');
            Route::post('backup/files', 'System\BackupController@files')->name('system.backup.files');
            Route::post('backup/upload', 'System\BackupController@upload')->name('system.backup.upload');

            Route::get('backup/last-backup', 'System\BackupController@mostRecent');
            Route::get('backup/download/{filename}', 'System\BackupController@download');

            /*
            Route::get('ajuste_claves_mysql', function(){

                $sites = \Hyn\Tenancy\Models\Website::all();
                $passwords = [];
                foreach($sites as $site){
                    $contra =md5(sprintf(
                                     '%s.%d',
                                     \Config::get('app.key'),
                                     $site->id
                                 ));
                    $temp = [
                        'username'=>$site->uuid,
                        'password'=>$contra,
                        'query'=>"SET PASSWORD FOR '{$site->uuid}'@'%' = PASSWORD('$contra');"
                    ];
                    $passwords[] = $temp;
                    \DB::update( $temp['query'] );
                }
            });
            */
        });
    });
}
