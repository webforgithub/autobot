<?php

/* ================== Homepage ================== */
Route::get('/', 'HomeController@index');
Route::get('/home', 'HomeController@index');
Route::auth();

/* ================== Access Uploaded Files ================== */

/*
  |--------------------------------------------------------------------------
  | Admin Application Routes
  |--------------------------------------------------------------------------
 */

$as = "";
if (\Dwij\Laraadmin\Helpers\LAHelper::laravel_ver() == 5.3) {
    $as = config('laraadmin.adminRoute') . '.';

    // Routes for Laravel 5.3
    Route::get('/logout', 'Auth\LoginController@logout');
}

Route::group(['as' => $as, 'middleware' => ['auth', 'permission:ADMIN_PANEL']], function () {

    /* ================== Dashboard ================== */

    Route::get(config('laraadmin.adminRoute'), 'LA\DashboardController@index');
    Route::get(config('laraadmin.adminRoute') . '/dashboard', 'LA\DashboardController@index');

    /* ================== Users ================== */
    Route::resource(config('laraadmin.adminRoute') . '/users', 'LA\UsersController');
    Route::get(config('laraadmin.adminRoute') . '/user_dt_ajax', 'LA\UsersController@dtajax');


    /* ================== Roles ================== */
    Route::resource(config('laraadmin.adminRoute') . '/roles', 'LA\RolesController');
    Route::get(config('laraadmin.adminRoute') . '/role_dt_ajax', 'LA\RolesController@dtajax');
    Route::post(config('laraadmin.adminRoute') . '/save_module_role_permissions/{id}', 'LA\RolesController@save_module_role_permissions');

    /* ================== Permissions ================== */
    Route::resource(config('laraadmin.adminRoute') . '/permissions', 'LA\PermissionsController');
    Route::get(config('laraadmin.adminRoute') . '/permission_dt_ajax', 'LA\PermissionsController@dtajax');
    Route::post(config('laraadmin.adminRoute') . '/save_permissions/{id}', 'LA\PermissionsController@save_permissions');

    /* ================== Departments ================== */
    Route::resource(config('laraadmin.adminRoute') . '/departments', 'LA\DepartmentsController');
    Route::get(config('laraadmin.adminRoute') . '/department_dt_ajax', 'LA\DepartmentsController@dtajax');

    /* ================== Employees ================== */
    Route::resource(config('laraadmin.adminRoute') . '/employees', 'LA\EmployeesController');
    Route::get(config('laraadmin.adminRoute') . '/employee_dt_ajax', 'LA\EmployeesController@dtajax');
    Route::post(config('laraadmin.adminRoute') . '/change_password/{id}', 'LA\EmployeesController@change_password');

    /* ================== Organizations ================== */
    Route::resource(config('laraadmin.adminRoute') . '/organizations', 'LA\OrganizationsController');
    Route::get(config('laraadmin.adminRoute') . '/organization_dt_ajax', 'LA\OrganizationsController@dtajax');

    /* ================== Backups ================== */
    Route::resource(config('laraadmin.adminRoute') . '/backups', 'LA\BackupsController');
    Route::get(config('laraadmin.adminRoute') . '/backup_dt_ajax', 'LA\BackupsController@dtajax');
    Route::post(config('laraadmin.adminRoute') . '/create_backup_ajax', 'LA\BackupsController@create_backup_ajax');
    Route::get(config('laraadmin.adminRoute') . '/downloadBackup/{id}', 'LA\BackupsController@downloadBackup');

    /* ================== Binance_Settings ================== */
    Route::resource(config('laraadmin.adminRoute') . '/binance_settings', 'LA\Binance_SettingsController');
    Route::get(config('laraadmin.adminRoute') . '/binance_setting_dt_ajax', 'LA\Binance_SettingsController@dtajax');
    Route::get(config('laraadmin.adminRoute') . '/binance_order_dt_ajax', 'LA\Binance_SettingsController@orderDtAjax');
    Route::get(config('laraadmin.adminRoute') . '/binance/market', 'LA\Binance_SettingsController@MarketData');
    Route::get(config('laraadmin.adminRoute') . '/binance/account', 'LA\Binance_SettingsController@GetProfile');
    Route::post(config('laraadmin.adminRoute') . '/binance/buy-order', 'LA\Binance_SettingsController@PlaceBuyOrder');
    Route::post(config('laraadmin.adminRoute') . '/binance/sell-order', 'LA\Binance_SettingsController@PlaceSellOrder');
    Route::get(config('laraadmin.adminRoute') . '/binance/my-order', 'LA\Binance_SettingsController@GetOrders');

    /* ================== Coinigy_Settings ================== */
    Route::resource(config('laraadmin.adminRoute') . '/coinigy_settings', 'LA\Coinigy_SettingsController');
    Route::get(config('laraadmin.adminRoute') . '/coinigy_setting_dt_ajax', 'LA\Coinigy_SettingsController@dtajax');

    /* ================== ConfigurationBots ================== */
    Route::resource(config('laraadmin.adminRoute') . '/configurationbots', 'LA\ConfigurationBotsController');
    Route::get(config('laraadmin.adminRoute') . '/configurationbot_dt_ajax', 'LA\ConfigurationBotsController@dtajax');
    Route::get(config('laraadmin.adminRoute') . '/bot/prediction', 'LA\ConfigurationBotsController@prediction');

    /* ================== ConfigureMACDBots ================== */
    Route::resource(config('laraadmin.adminRoute') . '/configuremacdbots', 'LA\ConfigureMACDBotsController');
    Route::get(config('laraadmin.adminRoute') . '/configuremacdbot_dt_ajax', 'LA\ConfigureMACDBotsController@dtajax');
    Route::get(config('laraadmin.adminRoute') . '/macd-chart', 'LA\ConfigureMACDBotsController@getMACDChart');
    
    Route::get(config('laraadmin.adminRoute') . '/get-chart/{symbol}', 'LA\ConfigureMACDBotsController@getChart');
});
