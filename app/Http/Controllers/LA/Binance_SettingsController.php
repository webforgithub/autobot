<?php

/**
 * Controller genrated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace App\Http\Controllers\LA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;
use DB;
use Validator;
use Datatables;
use Collective\Html\FormFacade as Form;
use Dwij\Laraadmin\Models\Module;
use Dwij\Laraadmin\Models\ModuleFields;
use App\Models\Binance_Setting;
use App\Models\Order;

class Binance_SettingsController extends Controller {

    public $show_action = true;
    public $view_col = 'user_id';
    public $listing_cols = ['id', 'api_key', 'secrete_key', 'user_id'];
    public $order_header_cols = ['Type', 'Side', 'Symbol', 'Order Id', 'Client OrderId', 'Transact Time', 'Price', 'Orig. Qty', 'Executed Qty', 'Ord. Status', 'Time In Force', 'Created At', 'Updated At', 'Deleted At'];
    public $order_listing_cols = ['type', 'side', 'symbol', 'orderId', 'clientOrderId', 'transactTime', 'price', 'origQty', 'executedQty', 'status', 'timeInForce', 'created_at', 'updated_at', 'deleted_at'];
    public $APIKey = '6N9ih2aoSMqAkoHVvs8M4SMy2GwCyQooqLZWmgu8dwJAyIYkB36LhjCpQqTOvGCp';
    public $ScreateKey = 'bnbsT9gIzPuSHLhaqMt7j4eE914Y6hvFPrO8VVz768kC3lI8j6GGWMFdFtnoukpb';
    public $WebSocketKey = '';

    public function __construct() {
        // Field Access of Listing Columns
        $myKeys = DB::table('binance_settings')->select($this->listing_cols)->whereNull('deleted_at')->where('user_id', '=', Auth::user()->id)->first();

        if ($myKeys && count($myKeys) > 0) {
            $this->APIKey = $myKeys->api_key;
            $this->ScreateKey = $myKeys->secrete_key;
        }

        if (\Dwij\Laraadmin\Helpers\LAHelper::laravel_ver() == 5.3) {
            $this->middleware(function ($request, $next) {
                $this->listing_cols = ModuleFields::listingColumnAccessScan('Binance_Settings', $this->listing_cols);
                return $next($request);
            });
        } else {
            $this->listing_cols = ModuleFields::listingColumnAccessScan('Binance_Settings', $this->listing_cols);
        }
    }

    /**
     * Display a listing of the Binance_Settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $module = Module::get('Binance_Settings');
        unset($this->listing_cols["user_id"]);

        if (Module::hasAccess($module->id)) {
            return View('la.binance_settings.index', [
                'show_actions' => $this->show_action,
                'listing_cols' => $this->listing_cols,
                'module' => $module
            ]);
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Show the form for creating a new binance_setting.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created binance_setting in database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        if (Module::hasAccess("Binance_Settings", "create")) {

            $request["user_id"] = Auth::user()->id;
            $rules = Module::validateRules("Binance_Settings", $request);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $insert_id = Module::insert("Binance_Settings", $request);

            return redirect()->route(config('laraadmin.adminRoute') . '.binance_settings.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Display the specified binance_setting.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        if (Module::hasAccess("Binance_Settings", "view")) {

            $binance_setting = Binance_Setting::find($id);
            if (isset($binance_setting->id)) {
                $module = Module::get('Binance_Settings');
                $module->row = $binance_setting;

                return view('la.binance_settings.show', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                            'no_header' => true,
                            'no_padding' => "no-padding"
                        ])->with('binance_setting', $binance_setting);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("binance_setting"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Show the form for editing the specified binance_setting.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        if (Module::hasAccess("Binance_Settings", "edit")) {

            $binance_setting = Binance_Setting::find($id);
            if (isset($binance_setting->id)) {
                $module = Module::get('Binance_Settings');

                $module->row = $binance_setting;

                return view('la.binance_settings.edit', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                        ])->with('binance_setting', $binance_setting);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("binance_setting"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Update the specified binance_setting in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        if (Module::hasAccess("Binance_Settings", "edit")) {

            $request["user_id"] = Auth::user()->id;

            $rules = Module::validateRules("Binance_Settings", $request, true);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
                ;
            }

            $insert_id = Module::updateRow("Binance_Settings", $request, $id);

            return redirect()->route(config('laraadmin.adminRoute') . '.binance_settings.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Remove the specified binance_setting from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        if (Module::hasAccess("Binance_Settings", "delete")) {
            Binance_Setting::find($id)->delete();

            // Redirecting to index() method
            return redirect()->route(config('laraadmin.adminRoute') . '.binance_settings.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Datatable Ajax fetch
     *
     * @return
     */
    public function dtajax() {

        $values = DB::table('binance_settings')->select($this->listing_cols)->whereNull('deleted_at')->where('user_id', '=', Auth::user()->id);
        //$values = DB::table('binance_settings')->select($this->listing_cols)->whereNull('deleted_at');
        $out = Datatables::of($values)->make();
        $data = $out->getData();

        $fields_popup = ModuleFields::getModuleFields('Binance_Settings');

        for ($i = 0; $i < count($data->data); $i++) {
            for ($j = 0; $j < count($this->listing_cols); $j++) {
                $col = $this->listing_cols[$j];
                if ($fields_popup[$col] != null && starts_with($fields_popup[$col]->popup_vals, "@")) {
                    $data->data[$i][$j] = ModuleFields::getFieldValue($fields_popup[$col], $data->data[$i][$j]);
                }
                if ($col == $this->view_col) {
                    $data->data[$i][$j] = '<a href="' . url(config('laraadmin.adminRoute') . '/binance_settings/' . $data->data[$i][0]) . '">' . $data->data[$i][$j] . '</a>';
                }
                // else if($col == "author") {
                //    $data->data[$i][$j];
                // }
            }

            if ($this->show_action) {
                $output = '';
                if (Module::hasAccess("Binance_Settings", "edit")) {
                    $output .= '<a href="' . url(config('laraadmin.adminRoute') . '/binance_settings/' . $data->data[$i][0] . '/edit') . '" class="btn btn-warning btn-xs" style="display:inline;padding:2px 5px 3px 5px;"><i class="fa fa-edit"></i></a>';
                }

                if (Module::hasAccess("Binance_Settings", "delete")) {
                    $output .= Form::open(['route' => [config('laraadmin.adminRoute') . '.binance_settings.destroy', $data->data[$i][0]], 'method' => 'delete', 'style' => 'display:inline']);
                    $output .= ' <button class="btn btn-danger btn-xs" type="submit"><i class="fa fa-times"></i></button>';
                    $output .= Form::close();
                }
                $data->data[$i][] = (string) $output;
            }
        }
        $out->setData($data);
        return $out;
    }

    public function MarketData() {
        $api = new \Binance\API($this->APIKey, $this->ScreateKey);

        $ticker = $api->prices();
        return View('la.binance_settings.marketdata', [
            'listing_cols' => ['Symbols/Currency', 'Current Price'],
            'ticker' => $ticker
        ]);
    }

    public function PlaceBuyOrder(Request $request) {
        $api = new \Binance\API($this->APIKey, $this->ScreateKey, ['useServerTime' => true]);

        $isBuy = $request->get('isBuy');
        $orderType = $request->get('orderType');
        $txtVolume = $request->get('txtVolume');
        $txtPrice = $request->get('txtPrice');
        $txtTokenPair = $request->get('txtBuyTokenPair');

        if ($orderType == 'Limit') {
            $order = $api->buy($txtTokenPair, $txtVolume, $txtPrice);
        } else {
            $order = $api->marketBuy($txtTokenPair, $txtVolume);
        }

        if (!isset($order['code'])) {
            try {
                $order["user_id"] = Auth::user()->id;
                $tmpOrder = Order::create($order);
                return response()->json(['status' => 'success', 'message' => 'Order placeed successfully', 'data' => $order], 200);  //i'm not getting any
            } catch (Exception $ex) {
                return response()->json(['status' => 'error', 'message' => $ex->getMessage(), 'data' => $order], 422);  //i'm not getting any
            }
        }
        return response()->json(['status' => 'error', 'message' => $order['msg']], 422);  //i'm not getting any        
    }

    public function PlaceSellOrder(Request $request) {
        $api = new \Binance\API($this->APIKey, $this->ScreateKey, ['useServerTime' => true]);

        $isBuy = $request->get('isBuy');
        $orderType = $request->get('orderType');
        $txtVolume = $request->get('txtVolume');
        $txtPrice = $request->get('txtPrice');
        $txtTokenPair = $request->get('txtSellTokenPair');

        if ($orderType == 'Limit') {
            $order = $api->sell($txtTokenPair, $txtVolume, $txtPrice);
        } else {
            $order = $api->marketSell($txtTokenPair, $txtVolume);
        }

        if (!isset($order['code'])) {
            try {
                $order["user_id"] = Auth::user()->id;
                $tmpOrder = Order::create($order);
                return response()->json(['status' => 'success', 'message' => 'Order placeed successfully', 'data' => $order], 200);  //i'm not getting any
            } catch (Exception $ex) {
                return response()->json(['status' => 'error', 'message' => $ex->getMessage(), 'data' => $order], 422);  //i'm not getting any
            }
        }
        return response()->json(['status' => 'error', 'message' => $order['msg']], 422);  //i'm not getting any        
    }

    public function GetProfile() {
        $api = new \Binance\API($this->APIKey, $this->ScreateKey, ['useServerTime' => true]);
        $myAccount = $api->account();

        return View('la.binance_settings.myaccount', [
            'listing_cols' => ['Symbols/Currency', 'Balance Token'],
            'myAccount' => $myAccount
        ]);
    }

    public function GetOrders() {
        return View('la.binance_settings.order', [
            'show_actions' => false,
            'listing_cols' => $this->order_header_cols,
            'module' => null
        ]);
    }
    
    public function orderDtAjax() {
        
        $values = DB::table('order_history')->select($this->order_listing_cols)->whereNull('deleted_at')->where('user_id', '=', Auth::user()->id);
        $out = Datatables::of($values)->make();
        $data = $out->getData();
        $out->setData($data);
        return $out;
    } 
}
