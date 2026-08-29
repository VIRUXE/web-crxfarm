<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Chassis extends Model
{
    protected $table = 'chassis';

    protected $fillable = ['name'];

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'listing_chassis');
    }
}
