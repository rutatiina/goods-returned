<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('goods-returned')->group(function () {

        Route::post('routes', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController@routes')->name('goods-returned.routes');
        //Route::get('summary', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController@summary');
        Route::post('export-to-excel', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController@approve')->name('goods-returned.approve');
        //Route::post('contact-estimates', 'Rutatiina\GoodsReturned\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController@copy');
        Route::delete('delete', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController@delete')->name('goods-returned.delete');
        Route::delete('cancel', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController@cancel')->name('goods-returned.cancel');

    });

    Route::resource('goods-returned/settings', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedSettingsController');
    Route::resource('goods-returned', 'Rutatiina\GoodsReturned\Http\Controllers\GoodsReturnedController');

});
