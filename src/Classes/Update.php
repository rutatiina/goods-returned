<?php

namespace Rutatiina\GoodsReturned\Classes;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Rutatiina\GoodsReturned\Models\GoodsReturned;
use Rutatiina\GoodsReturned\Models\GoodsReturnedItem;
use Rutatiina\GoodsReturned\Models\GoodsReturnedLedger;
use Rutatiina\GoodsReturned\Traits\Init as TxnTraitsInit;
use Rutatiina\GoodsReturned\Traits\Inventory as TxnTraitsInventory;
use Rutatiina\GoodsReturned\Traits\InventoryReverse as TxnTraitsInventoryReverse;
use Rutatiina\GoodsReturned\Traits\TxnItemsContactsIdsLedgers as TxnTraitsTxnItemsContactsIdsLedgers;
use Rutatiina\GoodsReturned\Traits\TxnTypeBasedSpecifics as TxnTraitsTxnTypeBasedSpecifics;
use Rutatiina\GoodsReturned\Traits\Validate as TxnTraitsValidate;
use Rutatiina\GoodsReturned\Traits\AccountBalanceUpdate as TxnTraitsAccountBalanceUpdate;
use Rutatiina\GoodsReturned\Traits\ContactBalanceUpdate as TxnTraitsContactBalanceUpdate;
use Rutatiina\GoodsReturned\Traits\Approve as TxnTraitsApprove;

class Update
{
    use TxnTraitsInit;
    use TxnTraitsInventory;
    use TxnTraitsInventoryReverse;
    use TxnTraitsTxnItemsContactsIdsLedgers;
    use TxnTraitsTxnTypeBasedSpecifics;
    use TxnTraitsValidate;
    use TxnTraitsAccountBalanceUpdate;
    use TxnTraitsContactBalanceUpdate;
    use TxnTraitsApprove;

    public function __construct()
    {
    }

    public function run()
    {
        //print_r($this->txnInsertData); exit;

        $verifyWebData = $this->validate();
        if ($verifyWebData === false) return false;

        $Txn = GoodsReturned::with('items', 'ledgers', 'debit_account', 'credit_account')->find($this->txn['id']);

        if (!$Txn)
        {
            $this->errors[] = 'Transaction not found';
            return false;
        }

        if ($Txn->status == 'approved')
        {
            $this->errors[] = 'Approved Transaction cannot be not be edited';
            return false;
        }

        $this->txn['original'] = $Txn->toArray();

        //check if inventory is affected and if its available
        $inventoryAvailability = $this->inventoryAvailability();
        if ($inventoryAvailability === false) return false;

        //Log::info($this->txn);
        //var_dump($this->txn); exit;
        //print_r($this->txn); exit;
        //echo json_encode($this->txn); exit;


        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            //Delete affected relations
            $Txn->ledgers()->delete();
            $Txn->items()->delete();

            // >> reverse all the inventory and balance effects
            //inventory checks and inventory balance update if needed
            $this->inventoryReverse();

            //Update the account balances
            $this->accountBalanceUpdate(true);

            //Update the contact balances
            $this->contactBalanceUpdate(true);
            // << reverse all the inventory and balance effects

            $txnId = $Txn->id;

            //print_r($this->txn); exit; //$this->txn, $this->txn['items'], $this->txn[number], $this->txn[ledgers], $this->txn[recurring]

            //print_r($this->txn); exit;
            $Txn->created_by = $this->txn['created_by'];
            $Txn->number = $this->txn['number'];
            $Txn->date = $this->txn['date'];
            $Txn->debit_financial_account_code = $this->txn['debit_financial_account_code'];
            $Txn->credit_financial_account_code = $this->txn['credit_financial_account_code'];
            $Txn->contact_id = $this->txn['contact_id'];
            $Txn->contact_name = $this->txn['contact_name'];
            $Txn->contact_address = $this->txn['contact_address'];
            $Txn->reference = $this->txn['reference'];
            $Txn->base_currency = $this->txn['base_currency'];
            $Txn->quote_currency = $this->txn['quote_currency'];
            $Txn->exchange_rate = $this->txn['exchange_rate'];
            $Txn->taxable_amount = $this->txn['taxable_amount'];
            $Txn->total = $this->txn['total'];
            $Txn->balance = $this->txn['balance'];
            $Txn->branch_id = $this->txn['branch_id'];
            $Txn->store_id = $this->txn['store_id'];
            $Txn->due_date = $this->txn['due_date'];
            $Txn->expiry_date = $this->txn['expiry_date'];
            $Txn->terms_and_conditions = $this->txn['terms_and_conditions'];
            $Txn->external_ref = $this->txn['external_ref'];
            $Txn->payment_mode = $this->txn['payment_mode'];
            $Txn->payment_terms = $this->txn['payment_terms'];
            $Txn->status = $this->txn['status'];
            $Txn->save();


            foreach ($this->txn['items'] as &$item)
            {
                unset($item['taxes']);
                $item['goods_returned_id'] = $txnId;
                GoodsReturnedItem::create($item);
            }

            unset($item);

            //print_r($this->txn['items']); exit;

            foreach ($this->txn['ledgers'] as &$ledger)
            {
                $ledger['goods_returned_id'] = $txnId;
                GoodsReturnedLedger::create($ledger);
            }
            unset($ledger);


            $this->approve();

            DB::connection('tenant')->commit();

            return (object)[
                'id' => $txnId,
            ];

        }
        catch (\Exception $e)
        {
            DB::connection('tenant')->rollBack();
            //print_r($e); exit;
            if (App::environment('local'))
            {
                $this->errors[] = 'Error: Failed to save transaction to database.';
                $this->errors[] = 'File: ' . $e->getFile();
                $this->errors[] = 'Line: ' . $e->getLine();
                $this->errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                $this->errors[] = 'Fatal Internal Error: Failed to save transaction to database. Please contact Admin';
            }

            return false;
        }

    }

}
