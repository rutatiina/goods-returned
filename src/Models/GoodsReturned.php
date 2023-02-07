<?php

namespace Rutatiina\GoodsReturned\Models;

use Bkwld\Cloner\Cloneable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rutatiina\Inventory\Scopes\StatusEditedScope;

class GoodsReturned extends Model
{
    use SoftDeletes;
    use LogsActivity;
    use Cloneable;

    protected static $logName = 'Txn';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_goods_returned';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'contact_id' => 'integer',
        'canceled' => 'integer',
    ];

    protected $cloneable_relations = ['items', 'comments'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    
    protected $appends = [
        'number_string',
        'total_in_words',
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
        static::addGlobalScope(new StatusEditedScope);

        self::deleting(function($txn) { // before delete() method call this
             $txn->items()->each(function($row) {
                $row->delete();
             });
             $txn->comments()->each(function($row) {
                $row->delete();
             });
        });

        self::restored(function($txn) {
             $txn->items()->each(function($row) {
                $row->restore();
             });
             $txn->comments()->each(function($row) {
                $row->restore();
             });
        });
    }
    
    /**
     * Scope a query to only include approved records users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    
    /**
     * Scope a query to only include not canceled records
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotCancelled($query)
    {
        return $query->where(function($q) {
            $q->where('canceled', 0);
            $q->orWhereNull('canceled');
        });
    }

    public function rgGetAttributes()
    {
        $attributes = [];
        $describeTable =  DB::connection('tenant')->select('describe ' . $this->getTable());

        foreach ($describeTable  as $row) {

            if (in_array($row->Field, ['id', 'created_at', 'updated_at', 'deleted_at', 'tenant_id', 'user_id'])) continue;

            if (in_array($row->Field, ['currencies', 'taxes'])) {
                $attributes[$row->Field] = [];
                continue;
            }

            if ($row->Default == '[]') {
                $attributes[$row->Field] = [];
            } else {
                $attributes[$row->Field] = $row->Default ?? ''; //$row->Default; //null affects laravel validation
            }
        }

        //add the relationships
        //$attributes['type'] = 'goods-issued-return';
        $attributes['debit_account'] = [];
        $attributes['credit_account'] = [];
        $attributes['items'] = [];
        $attributes['ledgers'] = [];
        $attributes['comments'] = [];
        $attributes['debit_contact'] = [];
        $attributes['credit_contact'] = [];
        $attributes['recurring'] = [];

        return $attributes;
    }

    public function getContactAddressArrayAttribute()
    {
        return preg_split("/\r\n|\n|\r/", $this->contact_address);
    }

    public function getNumberStringAttribute()
    {
        return $this->number_prefix.(str_pad(($this->number), $this->number_length, "0", STR_PAD_LEFT)).$this->number_postfix;
    }

    public function getTotalInWordsAttribute()
    {
        $f = new \NumberFormatter( locale_get_default(), \NumberFormatter::SPELLOUT );
        return ucfirst($f->format($this->total));
    }

    public function items()
    {
        return $this->hasMany('Rutatiina\GoodsReturned\Models\GoodsReturnedItem', 'goods_returned_id')->orderBy('id', 'asc');
    }

    public function comments()
    {
        return $this->hasMany('Rutatiina\GoodsReturned\Models\GoodsReturnedComment', 'goods_returned_id')->latest();
    }

    public function contact()
    {
        return $this->hasOne('Rutatiina\Contact\Models\Contact', 'id', 'contact_id');
    }

}
