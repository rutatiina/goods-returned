<?php

namespace Rutatiina\GoodsReturned\Services;

use Illuminate\Support\Facades\Validator;
use Rutatiina\Contact\Models\Contact;
use Rutatiina\GoodsReturned\Models\GoodsReturnedSetting;
use Rutatiina\Item\Models\Item;

class GoodsReturnedValidateService
{
    public static $errors = [];

    public static function run($requestInstance)
    {
        //$request = request(); //used for the flash when validation fails
        $user = auth()->user();


        // >> data validation >>------------------------------------------------------------

        //validate the data
        $customMessages = [
            //'total.in' => "Item total is invalid:\nItem total = item rate x item quantity",

            'items.*.taxes.*.code.required' => "Tax code is required",
            'items.*.taxes.*.total.required' => "Tax total is required",
            //'items.*.taxes.*.exclusive.required' => "Tax exclusive amount is required",
        ];

        $rules = [
            'contact_id' => 'required|numeric',
            'date' => 'required|date',
            'base_currency' => 'required',
            'due_date' => 'date|nullable',
            'salesperson_contact_id' => 'numeric|nullable',
            'memo' => 'string|nullable',

            'items' => 'required|array',
            'items.*.name' => 'required_without:item_id',
            'items.*.rate' => 'required|numeric',
            'items.*.quantity' => 'required|numeric|gt:0',
            //'items.*.total' => 'required|numeric|in:' . $itemTotal, //todo custom validator to check this
            'items.*.units' => 'numeric|nullable',
            'items.*.taxes' => 'array|nullable',

            'items.*.taxes.*.code' => 'required',
            'items.*.taxes.*.total' => 'required|numeric',
            //'items.*.taxes.*.exclusive' => 'required|numeric',
        ];

        $validator = Validator::make($requestInstance->all(), $rules, $customMessages);

        if ($validator->fails())
        {
            self::$errors = $validator->errors()->all();
            return false;
        }

        // << data validation <<------------------------------------------------------------

        $settings = GoodsReturnedSetting::firstOrFail();
        //Log::info($this->settings);

        $contact = Contact::findOrFail($requestInstance->contact_id);


        $data['id'] = $requestInstance->input('id', null); //for updating the id will always be posted
        $data['user_id'] = $user->id;
        $data['tenant_id'] = $user->tenant->id;
        $data['created_by'] = $user->name;
        $data['app'] = 'web';
        $data['document_name'] = $settings->document_name;
        $data['number_prefix'] = $settings->number_prefix;
        $data['number'] = $requestInstance->input('number');
        $data['number_length'] = $settings->minimum_number_length;
        $data['number_postfix'] = $settings->number_postfix;
        $data['date'] = $requestInstance->input('date');
        $data['contact_id'] = $requestInstance->contact_id;
        $data['contact_name'] = $contact->name;
        $data['contact_address'] = trim($contact->shipping_address_street1 . ' ' . $contact->shipping_address_street2);
        $data['reference'] = $requestInstance->input('reference', null);
        $data['base_currency'] =  $requestInstance->input('base_currency');
        $data['quote_currency'] =  $requestInstance->input('quote_currency', $data['base_currency']);
        $data['exchange_rate'] = $requestInstance->input('exchange_rate', 1);
        $data['salesperson_contact_id'] = $requestInstance->input('salesperson_contact_id', null);
        $data['branch_id'] = $requestInstance->input('branch_id', null);
        $data['store_id'] = $requestInstance->input('store_id', null);
        $data['due_date'] = $requestInstance->input('due_date', null);
        $data['terms_and_conditions'] = $requestInstance->input('terms_and_conditions', null);
        $data['contact_notes'] = $requestInstance->input('contact_notes', null);
        $data['status'] = strtolower($requestInstance->input('status', null));
        $data['balances_where_updated'] = 0;

        //Formulate the DB ready items array
        $data['items'] = [];
        foreach ($requestInstance->items as $key => $item)
        {
            //get the item
            $itemModel = Item::find($item['item_id']);

            $data['items'][] = [
                'tenant_id' => $data['tenant_id'],
                'created_by' => $data['created_by'],
                'contact_id' => $item['contact_id'],
                'item_id' => $item['item_id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'units' => ($item['quantity']*$itemModel['units']), //$requestInstance->input('items.'.$key.'.units', null),
                'batch' => $requestInstance->input('items.'.$key.'.batch', null),
                'expiry' => $requestInstance->input('items.'.$key.'.expiry', null),
                'inventory_tracking' => $itemModel->inventory_tracking,
            ];

        }

        //Return the array of txns
        //print_r($data); exit;

        return $data;

    }

}
