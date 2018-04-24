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
use App\Models\ConfigureMACDBot;
use App\Traits\OHLC;

class ConfigureMACDBotsController extends Controller {

    use OHLC;

    public $show_action = true;
    public $view_col = 'symbol';
    //public $listing_cols = ['id', 'symbol', 'userid', 'alert_email', 'alert_mobile', 'volume', 'totalorder', 'period', 'period_length', 'min_periods', 'ema_short_period', 'ema_long_period', 'signal_period', 'up_trend_threshold', 'down_trend_threshold', 'overbought_periods', 'overbought_rsi', 'use_all_fund'];
    public $listing_cols = ['id', 'symbol', 'userid', 'alert_email', 'alert_mobile', 'volume', 'totalorder', 'ema_short_period', 'ema_long_period', 'signal_period'];
    public $APIKey = '6N9ih2aoSMqAkoHVvs8M4SMy2GwCyQooqLZWmgu8dwJAyIYkB36LhjCpQqTOvGCp';
    public $ScreateKey = 'bnbsT9gIzPuSHLhaqMt7j4eE914Y6hvFPrO8VVz768kC3lI8j6GGWMFdFtnoukpb';
    public $WebSocketKey = '';

    public function __construct() {

        // Field Access of Listing Columns
        if (\Dwij\Laraadmin\Helpers\LAHelper::laravel_ver() == 5.3) {
            $this->middleware(function ($request, $next) {
                $this->listing_cols = ModuleFields::listingColumnAccessScan('ConfigureMACDBots', $this->listing_cols);
                return $next($request);
            });
        } else {
            $this->listing_cols = ModuleFields::listingColumnAccessScan('ConfigureMACDBots', $this->listing_cols);
        }

        $myKeys = DB::table('binance_settings')->select('*')->whereNull('deleted_at')->where('user_id', '=', Auth::user()->id)->first();

        if ($myKeys && count($myKeys) > 0) {
            $this->APIKey = $myKeys->api_key;
            $this->ScreateKey = $myKeys->secrete_key;
        }
    }

    /**
     * Display a listing of the ConfigureMACDBots.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $module = Module::get('ConfigureMACDBots');

        if (Module::hasAccess($module->id)) {
            $symbols = DB::table("exchange_pairs")
                    ->join("exchanges", "exchange_pairs.exchange_id", "=", "exchanges.id")
                    ->where("exchanges.coinigy_exch_code", "=", "BINA")
                    ->where("exchanges.use", "=", "0")
                    ->select("exchange_pair as key", "exchange_pair as col")
                    ->get();

//            $api = new \Binance\API($this->APIKey, $this->ScreateKey);
//            $symbols = $api->prices();

            return View('la.configuremacdbots.index', [
                'show_actions' => $this->show_action,
                'listing_cols' => $this->listing_cols,
                'module' => $module,
                'symbols' => $symbols
            ]);
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Show the form for creating a new configuremacdbot.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created configuremacdbot in database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        if (Module::hasAccess("ConfigureMACDBots", "create")) {

            $request["userid"] = Auth::user()->id;

            $rules = Module::validateRules("ConfigureMACDBots", $request);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $insert_id = Module::insert("ConfigureMACDBots", $request);

            return redirect()->route(config('laraadmin.adminRoute') . '.configuremacdbots.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Display the specified configuremacdbot.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        if (Module::hasAccess("ConfigureMACDBots", "view")) {

            $configuremacdbot = ConfigureMACDBot::find($id);
            if (isset($configuremacdbot->id)) {
                $module = Module::get('ConfigureMACDBots');
                $module->row = $configuremacdbot;

                return view('la.configuremacdbots.show', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                            'no_header' => true,
                            'no_padding' => "no-padding"
                        ])->with('configuremacdbot', $configuremacdbot);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("configuremacdbot"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Show the form for editing the specified configuremacdbot.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        if (Module::hasAccess("ConfigureMACDBots", "edit")) {
            $configuremacdbot = ConfigureMACDBot::find($id);
            if (isset($configuremacdbot->id)) {
                $module = Module::get('ConfigureMACDBots');

                $module->row = $configuremacdbot;

                $symbols = DB::table("exchange_pairs")
                        ->join("exchanges", "exchange_pairs.exchange_id", "=", "exchanges.id")
                        ->where("exchanges.coinigy_exch_code", "=", "BINA")
                        ->where("exchanges.use", "=", "0")
                        ->select("exchange_pair as key", "exchange_pair as col")
                        ->get();

//                $api = new \Binance\API($this->APIKey, $this->ScreateKey);
//                $symbols = $api->prices();

                return view('la.configuremacdbots.edit', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                            'symbols' => $symbols,
                        ])->with('configuremacdbot', $configuremacdbot);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("configuremacdbot"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Update the specified configuremacdbot in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        if (Module::hasAccess("ConfigureMACDBots", "edit")) {

            $request["userid"] = Auth::user()->id;

            $rules = Module::validateRules("ConfigureMACDBots", $request, true);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $insert_id = Module::updateRow("ConfigureMACDBots", $request, $id);

            return redirect()->route(config('laraadmin.adminRoute') . '.configuremacdbots.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Remove the specified configuremacdbot from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        if (Module::hasAccess("ConfigureMACDBots", "delete")) {
            ConfigureMACDBot::find($id)->delete();

            // Redirecting to index() method
            return redirect()->route(config('laraadmin.adminRoute') . '.configuremacdbots.index');
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
        
        if(\Entrust::hasRole('SUPER_ADMIN')) {        
            $values = DB::table('configuremacdbots')->select($this->listing_cols)->whereNull('deleted_at');
        } else {
            $values = DB::table('configuremacdbots')->select($this->listing_cols)->whereNull('deleted_at')->where('userid', '=', Auth::user()->id);
        }
        $out = Datatables::of($values)->make();
        $data = $out->getData();

        $fields_popup = ModuleFields::getModuleFields('ConfigureMACDBots');

        for ($i = 0; $i < count($data->data); $i++) {
            for ($j = 0; $j < count($this->listing_cols); $j++) {
                $col = $this->listing_cols[$j];
                if ($fields_popup[$col] != null && starts_with($fields_popup[$col]->popup_vals, "@")) {
                    $data->data[$i][$j] = ModuleFields::getFieldValue($fields_popup[$col], $data->data[$i][$j]);
                }
                if ($col == $this->view_col) {
                    $data->data[$i][$j] = '<a href="' . url(config('laraadmin.adminRoute') . '/configuremacdbots/' . $data->data[$i][0]) . '">' . $data->data[$i][$j] . '</a>';
                }
                // else if($col == "author") {
                //    $data->data[$i][$j];
                // }
            }

            if ($this->show_action) {
                $output = '';
                if (Module::hasAccess("ConfigureMACDBots", "edit")) {
                    $output .= '<a href="' . url(config('laraadmin.adminRoute') . '/configuremacdbots/' . $data->data[$i][0] . '/edit') . '" class="btn btn-warning btn-xs" style="display:inline;padding:2px 5px 3px 5px;"><i class="fa fa-edit"></i></a>';
                }

                if (Module::hasAccess("ConfigureMACDBots", "delete")) {
                    $output .= Form::open(['route' => [config('laraadmin.adminRoute') . '.configuremacdbots.destroy', $data->data[$i][0]], 'method' => 'delete', 'style' => 'display:inline']);
                    $output .= ' <button class="btn btn-danger btn-xs" type="submit"><i class="fa fa-times"></i></button>';
                    $output .= Form::close();
                }
                $data->data[$i][] = (string) $output;
            }
        }
        $out->setData($data);
        return $out;
    }

    public function getMACDChart() {
        $symbols = DB::table("exchange_pairs")
                ->join("exchanges", "exchange_pairs.exchange_id", "=", "exchanges.id")
                ->where("exchanges.coinigy_exch_code", "=", "BINA")
                ->where("exchanges.use", "=", "0")
                ->select("exchange_pair as key", "exchange_pair as col")
                ->get();

        $api = new \Binance\API($this->APIKey, $this->ScreateKey);
        if (count($symbols) > 0) {
            $myRecentData = $api->candlesticks($symbols[0]->col, "3m", 100);

            if (count($myRecentData) > 0) {
                array_walk($myRecentData, function(&$item, $key) {
                    $timeZoneOffSet = 3.30 * 3600;
                    $item["closeTime"] = date("Y-m-d H:i:s", (($item["closeTime"] + $timeZoneOffSet) / 1000));
                    $item["openTime"] = date("Y-m-d H:i:s", (($item["openTime"] + $timeZoneOffSet) / 1000));
                    $item["keyTime"] = date("Y-m-d H:i:s", (($key + $timeZoneOffSet) / 1000));
                    $item["timestamp"] = ($key + $timeZoneOffSet);
                });

                $closeArray = array_values(array_map(function($sub) {
                            return $sub['close'];
                        }, $myRecentData));
                $macdData = trader_macd($closeArray, 12, 26, 9);
                $totalMACDPoints = count($macdData[0]);

                return View('la.configuremacdbots.macdchart', [
                    'symbols' => $symbols,
                    'macdData' => $macdData,
                    'totalrecentData' => count($myRecentData),
                    'recentData' => $myRecentData,
                    'totalMACDPoints' => $totalMACDPoints
                ]);
            }
        }
        return View('la.configuremacdbots.macdchart', [
            'symbols' => $symbols,
            'macdData' => array(),
            'recentData' => array(),
            'totalrecentData' => 0,
            'totalMACDPoints' => 0
        ]);
    }

    public function getChart($symbol) {
        //$myRecentData = $this->getRecentData($symbol);
        $api = new \Binance\API($this->APIKey, $this->ScreateKey);
        //$myRecentData = $api->candlesticks($symbol, "5m", 196, time());
        $myRecentData = $api->candlesticks($symbol, "3m", 100);

        if (count($myRecentData) > 0) {
            array_walk($myRecentData, function(&$item, $key) {
                $timeZoneOffSet = 3.30 * 3600;
                $item["closeTime"] = date("Y-m-d H:i:s", (($item["closeTime"] + $timeZoneOffSet) / 1000));
                $item["openTime"] = date("Y-m-d H:i:s", (($item["openTime"] + $timeZoneOffSet) / 1000));
                $item["keyTime"] = date("Y-m-d H:i:s", (($key + $timeZoneOffSet) / 1000));
                $item["timestamp"] = ($key);
            });

            $closeArray = array_values(array_map(function($sub) {
                        return $sub['close'];
                    }, $myRecentData));

            //$macdData = trader_macd($myRecentData[6]['close'], 12, 26, 9);
            $macdData = trader_macd($closeArray, 12, 26, 9);
            $totalMACDPoints = count($macdData[0]);

            return response()->json([
                        'symbol' => $symbol,
                        'macdData' => $macdData,
                        'totalrecentData' => count($myRecentData),
                        'recentData' => $myRecentData,
                        'totalMACDPoints' => $totalMACDPoints
                    ])->setEncodingOptions(JSON_NUMERIC_CHECK);
        } else {
            return response()->json([
                        'symbol' => $symbol,
                        'totalrecentData' => 0,
                        'macdData' => array(),
                        'recentData' => array(),
                        'totalMACDPoints' => 0
                    ])->setEncodingOptions(JSON_NUMERIC_CHECK);
        }
    }

}
