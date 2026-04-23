<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ImageManagement extends Model
{
    use HasFactory;
    protected $table = 'images';
    protected $guarded = [];
    public function attachable()
    {
        return $this->morphTo();
    }

}
