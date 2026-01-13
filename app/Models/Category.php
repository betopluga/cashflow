<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Get the transactions for this category.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
