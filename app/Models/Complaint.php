<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Complaint extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'manager_id',
        'content',
        'phone',
        'image',
        'is_readed'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'manager_id' => 'integer',
        'customer_id' => 'integer',
        'is_readed' => 'boolean',
    ];

    /**
     * Get the full URL for the complation image
     *
     * @return string|null
     */
    public function getImageAttribute()
    {
        return $this->attributes['image'] ? asset($this->attributes['image']) : null;
    }

    /**
     * Get the is_readed attribute correctly casted
     *
     * @param  mixed  $value
     * @return bool
     */
    public function getIsReadedAttribute($value)
    {
        return (bool)$value;
    }


    public function scopeMyComplaint(Builder $query, ?bool $myComplaint = null): Builder
    {
        return $query->when($myComplaint === true, function ($q) {
            return $q->Where('manager_id', Auth::id());
        });
    }

    /**
     * Scope a query to filter complaints based on their read status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool|null  $isReaded Filter by read status.
     *                              - true: only read complaints
     *                              - false: only unread complaints
     *                              - null: no filter applied (returns all)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReadStatus(Builder $query, ?bool $isReaded = null): Builder
    {
        return $query->when($isReaded !== null, function ($q) use ($isReaded) {
            return $q->where('is_readed', $isReaded);
        });
    }

    /**
     * Get the manager that owns the Complaint
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the customer that owns the Complaint
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
