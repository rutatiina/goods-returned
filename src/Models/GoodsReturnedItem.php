<?php

namespace Rutatiina\GoodsReturned\Models;

use Illuminate\Database\Eloquent\Model;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReturnedItem extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_goods_returned_items';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $appends = [
        // 'inventory_tracking',
    ];

    protected $casts = [
        'item_id' => 'integer',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);
    }

    public function item()
    {
        return $this->belongsTo('Rutatiina\Item\Models\Item', 'item_id');
    }

    // public function getInventoryTrackingAttribute()
    // {
    //     return optional($this->item)->inventory_tracking;
    // }

}
