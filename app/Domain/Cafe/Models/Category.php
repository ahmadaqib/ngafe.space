<?php

namespace App\Domain\Cafe\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'icon', 'sort_order'];
    public function cafes(): BelongsToMany { return $this->belongsToMany(Cafe::class)->withPivot(['source', 'confidence']); }
}
