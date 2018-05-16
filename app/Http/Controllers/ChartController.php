<?php

namespace App\Http\Controllers;

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
use MathPHP\Statistics\Average;

class ChartController extends Controller {

    use OHLC;

    public $APIKey = '6N9ih2aoSMqAkoHVvs8M4SMy2GwCyQooqLZWmgu8dwJAyIYkB36LhjCpQqTOvGCp';
    public $ScreateKey = 'bnbsT9gIzPuSHLhaqMt7j4eE914Y6hvFPrO8VVz768kC3lI8j6GGWMFdFtnoukpb';
    public $WebSocketKey = '';

    public function __construct() {
        @ini_set('trader.real_precision', '8');
        @date_default_timezone_set('Europe/Amsterdam');
    }

    public function index() {
        $api = new \Binance\API($this->APIKey, $this->ScreateKey);

        $symbols = DB::table("exchange_pairs")
                ->join("exchanges", "exchange_pairs.exchange_id", "=", "exchanges.id")
                ->where("exchanges.coinigy_exch_code", "=", "BINA")
                ->where("exchanges.use", "=", "0")
                ->select("exchange_pair as key", "exchange_pair as col")
                ->get();

        $myRecentData = $api->candlesticks($symbols[0]->col, "3m");

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

            return View('chart', [
                'symbols' => $symbols,
                'macdData' => $macdData,
                'totalrecentData' => count($myRecentData),
                'recentData' => $myRecentData,
                'totalMACDPoints' => $totalMACDPoints
            ]);
        }

        return View('chart', [
            'symbols' => $symbols,
            'macdData' => array(),
            'recentData' => array(),
            'totalrecentData' => 0,
            'totalMACDPoints' => 0
        ]);
    }

    public function getChartOLD($symbol) {
        @ini_set('trader.real_precision', '10');
        @date_default_timezone_set('Europe/Amsterdam');

        $api = new \Binance\API($this->APIKey, $this->ScreateKey);

        /** https://cryptobee.itbutton.com/data_import/binance/IOTABTC * */
        $myRecentData = $api->candlesticks(strtoupper($symbol), "3m");

        if (count($myRecentData) > 0) {
            array_walk($myRecentData, function(&$item, $key) {
                $timeZoneOffSet = 3.30 * 3600;
                $item["closeTimeL"] = $item["closeTime"];
                $item["openTimeL"] = $item["openTime"];
                $item["closeTime"] = date("Y-m-d H:i:s", (($item["closeTime"] + $timeZoneOffSet) / 1000));
                $item["openTime"] = date("Y-m-d H:i:s", (($item["openTime"] + $timeZoneOffSet) / 1000));
//                $item["keyTime"] = date("Y-m-d H:i:s", (($key + $timeZoneOffSet) / 1000));
                $item["keyTime"] = date("Y-m-d", (($key + $timeZoneOffSet) / 1000));
                $item["timestamp"] = ($key);
            });

            $closeArray = array_values(array_map(function($sub) {
                        return $sub['close'];
                    }, $myRecentData));

            trader_set_compat(TRADER_MA_TYPE_EMA);
            //$macdData = trader_macd($myRecentData[6]['close'], 12, 26, 9);
            //$macdData = trader_macd($closeArray, 12, 26, 9);
            $macdData = trader_macdext($closeArray, 12, TRADER_MA_TYPE_EMA, 26, TRADER_MA_TYPE_EMA, 9, TRADER_MA_TYPE_EMA);

            $macd = array_values(array_map(function($sub) {
                        return number_format($sub * 100000, 4);
                    }, $macdData[0]));
            $signal = array_values(array_map(function($sub) {
                        return number_format($sub * 100000, 4);
                    }, $macdData[1]));
            $hist = array_values(array_map(function($sub) {
                        return number_format($sub * 100000, 4);
                    }, $macdData[2]));

//            $macd = new \App\Traits\MACD();
//            $macdData = $macd->run(["close" => $closeArray], 12, 26, 9);                     
//            $short_ema = $macdData['val'];
//            $long_ema =  $macdData['val2'];
//            $hist = [];            
//            $macd = array_values(array_map(function($sub) {return number_format($sub, 10); }, $short_ema));
//            $signal = array_values(array_map(function($sub) {return number_format($sub, 10); }, $long_ema));
//            $hist = array_values(array_map(function($sub) {return number_format($sub, 10); }, $signal_period));

            $macdData = [0 => $macd, 1 => $signal, 2 => $hist];
            $totalMACDPoints = count($macdData[0]);

            return response()->json([
                        'symbol' => $symbol,
                        'macdData' => $macdData,
                        'totalrecentData' => count($myRecentData),
                        'recentData' => $myRecentData,
                        'totalMACDPoints' => $totalMACDPoints
            ]);
            /** ->setEncodingOptions(JSON_NUMERIC_CHECK)  * */
        } else {
            return response()->json([
                        'symbol' => $symbol,
                        'totalrecentData' => 0,
                        'macdData' => array(),
                        'recentData' => array(),
                        'totalMACDPoints' => 0
            ]);
        }
    }

    protected function subtractTwoArray($ema_fast, $ema_slow) {
        $ret = array();
        foreach ($ema_fast as $key => $value) {
            $ret[$key] = number_format($ema_fast[$key] - $ema_slow[$key], 10);
        }
        return $ret;
    }

    /**
     * Get the MACD Chart based in user selection and MACD settings
     * 
     * @param type $symbol
     * @return type
     */
    public function getChart($symbol) {
//        @ini_set('precision', '10');
//        @ini_set('trader.real_precision', '10');
//        @date_default_timezone_set('Europe/Amsterdam');

        $api = new \Binance\API($this->APIKey, $this->ScreateKey);

        /**
         * https://cryptobee.itbutton.com/data_import/binance/IOTABTC 
         * http://jsonviewer.stack.hu/#http://cryptobee.itbutton.com/data_import/binance/IOTABTC
         *
         ***/
        $myRecentData = $api->candlesticks(strtoupper($symbol), "3m");
        $totalRecentData = count($myRecentData);
        
        if ($totalRecentData > 0) {
            $ema_short_period = 12;
            $ema_long_period = 26;
            $signal_period = 9;
            
            if( Auth::check()) {
                $symbols = DB::table('configuremacdbots')->select("*")
                        ->where('userid', '=', Auth::user()->id)
                        ->where('symbol', '=', $symbol)
                        ->whereNull('deleted_at')
                        ->get();
                if(count($symbols) > 0) {
                    $ema_short_period = $symbols[0]->ema_short_period;
                    $ema_long_period = $symbols[0]->ema_long_period;
                    $signal_period = $symbols[0]->signal_period;
                }
            }
            
            
            array_walk($myRecentData, function(&$item, $key) {
                $timeZoneOffSet = 3.30 * 3600;
                $item["closeTimeL"] = $item["closeTime"];
                $item["openTimeL"] = $item["openTime"];
                $item["closeTime"] = date("Y-m-d H:i:s", (($item["closeTime"] + $timeZoneOffSet) / 1000));
                $item["openTime"] = date("Y-m-d H:i:s", (($item["openTime"] + $timeZoneOffSet) / 1000));
                $item["keyTime"] = date("Y-m-d", (($key + $timeZoneOffSet) / 1000));
                $item["timestamp"] = ($key);
            });

            $closeArray = array_values(array_map(function($sub) {
                        return $sub['close'];
                    }, $myRecentData));
                    
//          $closeArray = array(22.27,22.19,22.08,22.17,22.18,22.13,22.23,22.43,22.24,22.29,22.15,22.39,22.38,22.61,23.36,24.05,23.75,23.83,23.95,23.63,23.82,23.87,23.65,23.19,23.10,23.33,22.68,23.10,22.40,22.17);            
            $macdData = [];
            $ema_fast = Average::exponentialMovingAverage($closeArray, $ema_short_period); /** 12 **/
            $ema_slow = Average::exponentialMovingAverage($closeArray, $ema_long_period); /** 26 **/
            $macd = $signal = $hist = array();
            
            $macd = $this->subtractTwoArray($ema_fast, $ema_slow);
            $signal = Average::exponentialMovingAverage($macd, $signal_period);       /** 9 **/
            $hist = $this->subtractTwoArray($macd, $signal);
            
            $keys = array_keys($myRecentData);
            
            for($i = 0; $i < $totalRecentData; $i++) {
                $myRecentData[$keys[$i]]['macd'] = number_format($macd[$i] * 1, 10);
                $myRecentData[$keys[$i]]['macds'] = number_format($signal[$i] * 1, 10);
                $myRecentData[$keys[$i]]['macdh'] = number_format($hist[$i] * 1, 10);
                
                if ($macd[$i] > $signal[$i] && $macd[$i - 1] <= $signal[$i - 1]) {
                    /* If the MACD crosses the signal line upward  */
                    $advice = "BUY";
                } else if ($macd[$i] < $signal[$i] && $macd[$i - 1] >= $signal[$i - 1]) {
                    /* The other way around */
                    $advice = "SELL";                   
                } else {
                    /* Do nothing if not crossed */
                    $advice = "HOLD";
                }
                $myRecentData[$keys[$i]]['advice'] = $advice;
            }
            
            return response()->json([
                        'symbol' => $symbol,
                        'ema_short_period' => $ema_short_period,
                        'ema_long_period' => $ema_long_period,
                        'signal_period' => $signal_period,
                        'totalrecentData' => $totalRecentData,
                        'recentData' => $myRecentData,
                
            ]);
        } else {
            return response()->json([
                        'symbol' => $symbol,
                        'totalrecentData' => 0,
                        'recentData' => array(),
            ]);
        }
    }
}