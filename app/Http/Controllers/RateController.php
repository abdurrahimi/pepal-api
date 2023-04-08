<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rate;
use Goutte\Client;
use App\Models\Order;
use DB;

class RateController extends Controller
{
    //
    public function index()
    {
        $data = Rate::select('id','rate')->where('is_active',1)->first();
        return response()->json($data);
    }

    public function getRate()
    {
        $update = Order::where('status','=','waiting')->where(DB::Raw('DATE_ADD(created_at, INTERVAL 24 HOUR)'),"<",date('Y-m-d H:i:s'))->update([
            "status" => "Canceled"
        ]);
        
    }
}
