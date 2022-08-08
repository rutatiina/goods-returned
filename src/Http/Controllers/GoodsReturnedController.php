<?php

namespace Rutatiina\GoodsReturned\Http\Controllers;

use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Rutatiina\Contact\Traits\ContactTrait;
use Rutatiina\GoodsReturned\Models\GoodsReturned;
use Rutatiina\GoodsReturned\Traits\Item as TxnItem;

use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\GoodsReturned\Services\GoodsReturnedService;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;

class GoodsReturnedController extends Controller
{
    //use TenantTrait;
    use ContactTrait;
    use FinancialAccountingTrait;
    use TxnItem; // >> get the item attributes template << !!important

    private  $txnEntreeSlug = 'goods-returned-note';

    public function __construct()
    {
        // $this->middleware('permission:goods-returned.view');
		// $this->middleware('permission:goods-returned.create', ['only' => ['create','store']]);
		// $this->middleware('permission:goods-returned.update', ['only' => ['edit','update']]);
		// $this->middleware('permission:goods-returned.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $per_page = ($request->per_page) ? $request->per_page : 20;

        $txns = GoodsReturned::with('items')->latest()->paginate($per_page);

        return [
            'tableData' => $txns
        ];
    }

    private function nextNumber()
    {
        $txn = GoodsReturned::latest()->first();
        $settings = GoodsReturnedService::settings();

        return $settings->number_prefix.(str_pad((optional($txn)->number+1), $settings->minimum_number_length, "0", STR_PAD_LEFT)).$settings->number_postfix;
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new GoodsReturned())->rgGetAttributes();

        $txnAttributes['number'] = $this->nextNumber();

        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['isRecurring'] = false;
        $txnAttributes['recurring'] = [
            'date_range' => [],
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
        ];
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [$this->itemCreate()];

        return [
            'pageTitle' => 'Create Goods Returned Note', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/goods-returned', #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function store(Request $request)
    {
        $storeService = GoodsReturnedService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => GoodsReturnedService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods returned note saved'],
            'number' => 0,
            'callback' => URL::route('goods-returned.show', [$storeService->id], false)
        ];
    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txn = GoodsReturned::findOrFail($id);
        $txn->load('contact', 'items');
        $txn->setAppends([
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txnAttributes = GoodsReturnedService::edit($id);

        return [
            'pageTitle' => 'Edit Goods returned note', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/goods-returned/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function update(Request $request)
    {
        //print_r($request->all()); exit;

        $storeService = GoodsReturnedService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => GoodsReturnedService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods returned note updated'],
            'number' => 0,
            'callback' => URL::route('goods-returned.show', [$storeService->id], false)
        ];
    }

    public function destroy($id)
	{
        $destroy = GoodsReturnedService::destroy($id);

        if ($destroy)
        {
            return [
                'status' => true,
                'messages' => ['Goods returned note deleted'],
                'callback' => URL::route('goods-returned.index', [], false)
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => GoodsReturnedService::$errors
            ];
        }
    }

	#-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $approve = GoodsReturnedService::approve($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => GoodsReturnedService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods returned note Approved'],
        ];
    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txnAttributes = GoodsReturnedService::copy($id);

        return [
            'pageTitle' => 'Copy Goods delivered note', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/goods-returned', #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function exportToExcel(Request $request)
	{
        $txns = collect([]);

        $txns->push([
            'DATE',
            'DOCUMENT#',
            'REFERENCE',
            'CUSTOMER',
            'STATUS',
            'EXPIRY DATE',
            'TOTAL',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id) {
            $txn = Transaction::transaction($id);

            $txns->push([
                $txn->date,
                $txn->number,
                $txn->reference,
                $txn->contact_name,
                $txn->status,
                $txn->expiry_date,
                $txn->total,
                $txn->base_currency,
            ]);
        }

        $export = $txns->downloadExcel(
            'maccounts-goods-returned-export-'.date('Y-m-d-H-m-s').'.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }

}
