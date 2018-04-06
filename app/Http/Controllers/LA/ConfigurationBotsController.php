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
use App\Models\ConfigurationBot;

class ConfigurationBotsController extends Controller {

    public $show_action = true;
    public $view_col = 'trade_type';
    public $listing_cols = ['id', 'alert_email', 'alert_mobile_no', 'user_id', 'buy_sale_volume', 'trade_type', 'trade_symbol', 'current_price', 'is_percentage', 'buy_price', 'sell_price'];
    
    public $APIKey = '6N9ih2aoSMqAkoHVvs8M4SMy2GwCyQooqLZWmgu8dwJAyIYkB36LhjCpQqTOvGCp';
    public $ScreateKey = 'bnbsT9gIzPuSHLhaqMt7j4eE914Y6hvFPrO8VVz768kC3lI8j6GGWMFdFtnoukpb';
    
    public function __construct() {
        $myKeys = DB::table('binance_settings')->select(['api_key', 'secrete_key'])->whereNull('deleted_at')->where('user_id', '=', Auth::user()->id)->first();
        
        if($myKeys && count($myKeys) > 0) {
            $this->APIKey = $myKeys->api_key;
            $this->ScreateKey = $myKeys->secrete_key;
        }
        
        // Field Access of Listing Columns
        if (\Dwij\Laraadmin\Helpers\LAHelper::laravel_ver() == 5.3) {
            $this->middleware(function ($request, $next) {
                $this->listing_cols = ModuleFields::listingColumnAccessScan('ConfigurationBots', $this->listing_cols);
                return $next($request);
            });
        } else {
            $this->listing_cols = ModuleFields::listingColumnAccessScan('ConfigurationBots', $this->listing_cols);
        }
    }

    /**
     * Display a listing of the ConfigurationBots.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $module = Module::get('ConfigurationBots');

        if (Module::hasAccess($module->id)) {
            $api = new \Binance\API($this->APIKey, $this->ScreateKey);
            $symbols = $api->prices();
            
            return View('la.configurationbots.index', [
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
     * Show the form for creating a new configurationbot.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created configurationbot in database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        if (Module::hasAccess("ConfigurationBots", "create")) {

            $request["user_id"] = Auth::user()->id;

            $rules = Module::validateRules("ConfigurationBots", $request);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $insert_id = Module::insert("ConfigurationBots", $request);

            return redirect()->route(config('laraadmin.adminRoute') . '.configurationbots.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Display the specified configurationbot.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        if (Module::hasAccess("ConfigurationBots", "view")) {

            $configurationbot = ConfigurationBot::find($id);
            if (isset($configurationbot->id)) {
                $module = Module::get('ConfigurationBots');
                $module->row = $configurationbot;

                return view('la.configurationbots.show', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                            'no_header' => true,
                            'no_padding' => "no-padding"
                        ])->with('configurationbot', $configurationbot);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("configurationbot"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Show the form for editing the specified configurationbot.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        if (Module::hasAccess("ConfigurationBots", "edit")) {
            $configurationbot = ConfigurationBot::find($id);
            if (isset($configurationbot->id)) {
                $module = Module::get('ConfigurationBots');

                $module->row = $configurationbot;
                
                $api = new \Binance\API($this->APIKey, $this->ScreateKey);
                $symbols = $api->prices();

                return view('la.configurationbots.edit', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                            'symbols' => $symbols
                        ])->with('configurationbot', $configurationbot);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("configurationbot"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Update the specified configurationbot in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        if (Module::hasAccess("ConfigurationBots", "edit")) {

            $request["user_id"] = Auth::user()->id;

            $rules = Module::validateRules("ConfigurationBots", $request, true);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
                ;
            }

            $insert_id = Module::updateRow("ConfigurationBots", $request, $id);

            return redirect()->route(config('laraadmin.adminRoute') . '.configurationbots.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Remove the specified configurationbot from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        if (Module::hasAccess("ConfigurationBots", "delete")) {
            ConfigurationBot::find($id)->delete();

            // Redirecting to index() method
            return redirect()->route(config('laraadmin.adminRoute') . '.configurationbots.index');
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
        $values = DB::table('configurationbots')->select($this->listing_cols)->whereNull('deleted_at');
        $out = Datatables::of($values)->make();
        $data = $out->getData();

        $fields_popup = ModuleFields::getModuleFields('ConfigurationBots');

        for ($i = 0; $i < count($data->data); $i++) {
            for ($j = 0; $j < count($this->listing_cols); $j++) {
                $col = $this->listing_cols[$j];
                if ($fields_popup[$col] != null && starts_with($fields_popup[$col]->popup_vals, "@")) {
                    $data->data[$i][$j] = ModuleFields::getFieldValue($fields_popup[$col], $data->data[$i][$j]);
                }
                if ($col == $this->view_col) {
                    $data->data[$i][$j] = '<a href="' . url(config('laraadmin.adminRoute') . '/configurationbots/' . $data->data[$i][0]) . '">' . $data->data[$i][$j] . '</a>';
                }
                // else if($col == "author") {
                //    $data->data[$i][$j];
                // }
            }

            if ($this->show_action) {
                $output = '';
                if (Module::hasAccess("ConfigurationBots", "edit")) {
                    $output .= '<a href="' . url(config('laraadmin.adminRoute') . '/configurationbots/' . $data->data[$i][0] . '/edit') . '" class="btn btn-warning btn-xs" style="display:inline;padding:2px 5px 3px 5px;"><i class="fa fa-edit"></i></a>';
                }

                if (Module::hasAccess("ConfigurationBots", "delete")) {
                    $output .= Form::open(['route' => [config('laraadmin.adminRoute') . '.configurationbots.destroy', $data->data[$i][0]], 'method' => 'delete', 'style' => 'display:inline']);
                    $output .= ' <button class="btn btn-danger btn-xs" type="submit"><i class="fa fa-times"></i></button>';
                    $output .= Form::close();
                }
                $data->data[$i][] = (string) $output;
            }
        }
        $out->setData($data);
        return $out;
    }
    
    public  function prediction() {
        
    }
}
