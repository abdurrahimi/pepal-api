<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = "order";

    public function user(){
        return $this->hasOne('App\Models\User','id','member_id');
    }

    public function rate(){
        return $this->hasOne('App\Models\Rate','id','rate');
    }

    public function history(){
        return $this->hasMany('App\Models\OrderHistory','order_id','id');
    }
}
