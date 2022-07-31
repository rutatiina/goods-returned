<?php

namespace Rutatiina\GoodsReturned\Services;

use Rutatiina\Inventory\Models\Inventory;
use Rutatiina\FinancialAccounting\Services\AccountBalanceUpdateService;
use Rutatiina\FinancialAccounting\Services\ContactBalanceUpdateService;

trait GoodsReturnedApprovalService
{
    public static function run($data)
    {
        if ($data['status'] != 'approved')
        {
            //can only update balances if status is approved
            return false;
        }
        
        //Update the inventory summary
        foreach ($data['items'] as &$item)
        {
            $inventory = Inventory::firstOrCreate([
                'tenant_id' => $item['tenant_id'], 
                'project_id' => @$data['project_id'], 
                'date' => $data['date'],
                'item_id' => $item['item_id'],
                'batch' => $item['batch'],
            ]);

            //increase the 
            $inventory->decrement('units_issued', $item['units']);
            $inventory->increment('units_returned', $item['units']);
            $inventory->increment('units_available', $item['units']);

        }

        //inventory checks and inventory balance update if needed
        //$this->inventory(); //currently inventory update for estimates is disabled -< todo update the inventory here

        return true;
    }

}
