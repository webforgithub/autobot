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
use App\Models\Coinigy_Setting;

class Coinigy_SettingsController extends Controller {

    public $show_action = true;
    public $view_col = 'api_key';
    public $listing_cols = ['id', 'api_key', 'secrete_key', 'user_id'];

    public function __construct() {
        // Field Access of Listing Columns
        if (\Dwij\Laraadmin\Helpers\LAHelper::laravel_ver() == 5.3) {
            $this->middleware(function ($request, $next) {
                $this->listing_cols = ModuleFields::listingColumnAccessScan('Coinigy_Settings', $this->listing_cols);
                return $next($request);
            });
        } else {
            $this->listing_cols = ModuleFields::listingColumnAccessScan('Coinigy_Settings', $this->listing_cols);
        }
    }

    /**
     * Display a listing of the Coinigy_Settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $module = Module::get('Coinigy_Settings');

        if (Module::hasAccess($module->id)) {
            return View('la.coinigy_settings.index', [
                'show_actions' => $this->show_action,
                'listing_cols' => $this->listing_cols,
                'module' => $module
            ]);
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Show the form for creating a new coinigy_setting.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created coinigy_setting in database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        if (Module::hasAccess("Coinigy_Settings", "create")) {
            $request["user_id"] = Auth::user()->id;

            $rules = Module::validateRules("Coinigy_Settings", $request);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $insert_id = Module::insert("Coinigy_Settings", $request);

            return redirect()->route(config('laraadmin.adminRoute') . '.coinigy_settings.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Display the specified coinigy_setting.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        if (Module::hasAccess("Coinigy_Settings", "view")) {

            $coinigy_setting = Coinigy_Setting::find($id);
            if (isset($coinigy_setting->id)) {
                $module = Module::get('Coinigy_Settings');
                $module->row = $coinigy_setting;

                return view('la.coinigy_settings.show', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                            'no_header' => true,
                            'no_padding' => "no-padding"
                        ])->with('coinigy_setting', $coinigy_setting);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("coinigy_setting"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Show the form for editing the specified coinigy_setting.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        if (Module::hasAccess("Coinigy_Settings", "edit")) {
            $coinigy_setting = Coinigy_Setting::find($id);
            if (isset($coinigy_setting->id)) {

                $module = Module::get('Coinigy_Settings');

                $module->row = $coinigy_setting;

                return view('la.coinigy_settings.edit', [
                            'module' => $module,
                            'view_col' => $this->view_col,
                        ])->with('coinigy_setting', $coinigy_setting);
            } else {
                return view('errors.404', [
                    'record_id' => $id,
                    'record_name' => ucfirst("coinigy_setting"),
                ]);
            }
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Update the specified coinigy_setting in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        if (Module::hasAccess("Coinigy_Settings", "edit")) {
            $request["user_id"] = Auth::user()->id;

            $rules = Module::validateRules("Coinigy_Settings", $request, true);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
                ;
            }

            $insert_id = Module::updateRow("Coinigy_Settings", $request, $id);

            return redirect()->route(config('laraadmin.adminRoute') . '.coinigy_settings.index');
        } else {
            return redirect(config('laraadmin.adminRoute') . "/");
        }
    }

    /**
     * Remove the specified coinigy_setting from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        if (Module::hasAccess("Coinigy_Settings", "delete")) {
            Coinigy_Setting::find($id)->delete();

            // Redirecting to index() method
            return redirect()->route(config('laraadmin.adminRoute') . '.coinigy_settings.index');
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
        $values = DB::table('coinigy_settings')->select($this->listing_cols)->whereNull('deleted_at')->where('user_id', '=', Auth::user()->id);
        $out = Datatables::of($values)->make();
        $data = $out->getData();

        $fields_popup = ModuleFields::getModuleFields('Coinigy_Settings');

        for ($i = 0; $i < count($data->data); $i++) {
            for ($j = 0; $j < count($this->listing_cols); $j++) {
                $col = $this->listing_cols[$j];
                if ($fields_popup[$col] != null && starts_with($fields_popup[$col]->popup_vals, "@")) {
                    $data->data[$i][$j] = ModuleFields::getFieldValue($fields_popup[$col], $data->data[$i][$j]);
                }
                if ($col == $this->view_col) {
                    $data->data[$i][$j] = '<a href="' . url(config('laraadmin.adminRoute') . '/coinigy_settings/' . $data->data[$i][0]) . '">' . $data->data[$i][$j] . '</a>';
                }
                // else if($col == "author") {
                //    $data->data[$i][$j];
                // }
            }

            if ($this->show_action) {
                $output = '';
                if (Module::hasAccess("Coinigy_Settings", "edit")) {
                    $output .= '<a href="' . url(config('laraadmin.adminRoute') . '/coinigy_settings/' . $data->data[$i][0] . '/edit') . '" class="btn btn-warning btn-xs" style="display:inline;padding:2px 5px 3px 5px;"><i class="fa fa-edit"></i></a>';
                }

                if (Module::hasAccess("Coinigy_Settings", "delete")) {
                    $output .= Form::open(['route' => [config('laraadmin.adminRoute') . '.coinigy_settings.destroy', $data->data[$i][0]], 'method' => 'delete', 'style' => 'display:inline']);
                    $output .= ' <button class="btn btn-danger btn-xs" type="submit"><i class="fa fa-times"></i></button>';
                    $output .= Form::close();
                }
                $data->data[$i][] = (string) $output;
            }
        }
        $out->setData($data);
        return $out;
    }

}
