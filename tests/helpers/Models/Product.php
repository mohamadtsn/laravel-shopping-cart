<?php

namespace Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}