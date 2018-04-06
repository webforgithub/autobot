<?php

/**
 * Created by PhpStorm.
 * User: joeldg
 * Date: 6/26/17
 * Time: 4:03 PM
 */

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait Orders {

    public $quantity = 0;
    public $increasing = 0;
    public $decreasing = 0;
    public $buyPrice = 0;
    public $sellPrice = 0;
    public $stepSize = 0;

    public function ValidateOrder($api, $symbol, $amount, $quantity = 0) {
        $valid = true;

        $filters = $this->getFilters($api, $symbol);

        # Order book prices
        #lastBid, lastAsk = Orders.get_order_book(symbol)
        $orderBook = $this->getOrderBook($api, $symbol);
        $lastPrice = $this->getTicker($api, $symbol);

        $minPrice = (float) $filters['PRICE_FILTER']['minPrice'];
        $minQty = (float) $filters['LOT_SIZE']['minQty'];
        $minNotional = (float) $filters['MIN_NOTIONAL']['minNotional'];
        $this->quantity = (float) $quantity;

        # stepSize defines the intervals that a quantity/icebergQty can be increased/decreased by.
        $stepSize = (float) $filters['LOT_SIZE']['stepSize'];

        # tickSize defines the intervals that a price/stopPrice can be increased/decreased by
        $tickSize = (float) $filters['PRICE_FILTER']['tickSize'];

        # If option increasing default tickSize greater than        
        $this->increasing = $tickSize;

        # If option decreasing default tickSize greater than
        $this->decreasing = $tickSize;

        # Just for validation
        $orderBook["lastBid"] = $orderBook["lastBid"] + $this->increasing;

        # Set static
        # If quantity or amount is zero, minNotional increase 10%
        $this->quantity = ($minNotional / $orderBook["lastBid"]);
        $this->quantity = $this->quantity + ($this->quantity * 10 / 100);
        $notional = $minNotional;

        if ($amount > 0) {
            # Calculate amount to quantity
            $quantity = ($amount / $orderBook["lastBid"]);
        }

        if ($quantity > 0) {
            # Format quantity step
            $this->quantity = $this->quantity;
        }

        $this->quantity = $this->formatStep($this->quantity, $stepSize);
        $notional = $orderBook["lastBid"] * (float) $this->quantity;

        # Set Globals
        $this->stepSize = $stepSize;

        # minQty = minimum order quantity
        if ($this->quantity < $minQty) {
            #print('Invalid quantity, minQty: %.8f (u: %.8f)' % (minQty, quantity))
            print('Invalid quantity, minQty: minQty (u: quantity)');
            $valid = false;
        }

        if ($lastPrice < $minPrice) {
            #print('Invalid price, minPrice: %.8f (u: %.8f)' % (minPrice, lastPrice))
            print('Invalid price, minPrice: minPrice (u: lastPrice)');
            $valid = false;
        }

        # minNotional = minimum order value (price * quantity)
        if ($notional < $minNotional) {
            #print('Invalid notional, minNotional: %.8f (u: %.8f)' % (minNotional, notional))
            print('Invalid notional, minNotional: minNotional (u: notional)');
            $valid = false;
        }

        return $valid;
    }

    public function getOrderBook($api, $symbol) {
        $orders = $api->depth($symbol, 5);

//        $lastBid = (float) $orders['bids'][0][0]; #last buy price (bid)
//        $lastAsk = (float) $orders['asks'][0][0]; #last sell price (ask)
        $bids = array_keys($orders['bids']);
        $asks = array_keys($orders['asks']);

        $lastBid = number_format((float) array_shift($bids), 8, '.', ' ');
        ; #last buy price (bid)
        $lastAsk = number_format((float) array_shift($asks), 8, '.', ' '); #last sell price (ask)

        return array("lastBid" => $lastBid, "lastAsk" => $lastAsk);
    }

    public function getTicker($api, $symbol) {
        $ticker = $api->prevDay($symbol);

        return (float) $ticker['lastPrice'];
    }

    public function getFilters($api, $symbol) {
        $info = $api->exchangeInfo();
        $returnValue = array("PRICE_FILTER" => array(), "LOT_SIZE" => array(), "MIN_NOTIONAL" => array());

        foreach ($info["symbols"] as $item) {
            if ($item["symbol"] == $symbol) {
                foreach ($item["filters"] as $key => $iItem) {
                    if ($iItem["filterType"] == "PRICE_FILTER") {
                        $returnValue["PRICE_FILTER"]["minPrice"] = $iItem["minPrice"];
                        $returnValue["PRICE_FILTER"]["maxPrice"] = $iItem["maxPrice"];
                        $returnValue["PRICE_FILTER"]["tickSize"] = $iItem["tickSize"];
                    } else if ($iItem["filterType"] == "LOT_SIZE") {
                        $returnValue["LOT_SIZE"]["minQty"] = $iItem["minQty"];
                        $returnValue["LOT_SIZE"]["maxQty"] = $iItem["maxQty"];
                        $returnValue["LOT_SIZE"]["stepSize"] = $iItem["stepSize"];
                    } else if ($iItem["filterType"] == "MIN_NOTIONAL") {
                        $returnValue["MIN_NOTIONAL"]["minNotional"] = $iItem["minNotional"];
                    }
                }
                break;
            }
        }
        return $returnValue;
    }

    public function formatStep($quantity, $stepSize) {
        return (float) ($stepSize * floor((float) ($quantity) / $stepSize));
    }

}
