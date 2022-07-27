<?php

namespace Rutatiina\GoodsReturned\Services;

use Rutatiina\GoodsReturned\Models\GoodsReturnedItem;
use Rutatiina\GoodsReturned\Models\GoodsReturnedItemTax;

class GoodsReturnedItemService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        //print_r($data['items']); exit;

        //Save the items >> $data['items']
        foreach ($data['items'] as &$item)
        {
            $item['goods_delivered_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = GoodsReturnedItem::create($item);

        }
        unset($item);

    }

}
