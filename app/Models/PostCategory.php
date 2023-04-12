<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    use HasFactory;
    protected $table = "post_category";

    public function category(){
        return $this->hasOne('App\Models\Category','id','category_id');
    }

    public function post(){
        return $this->belongsTo('App\Models\Post','post_id','id');
    }
}
