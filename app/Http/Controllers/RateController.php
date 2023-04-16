<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rate;
use Goutte\Client;
use App\Models\Order;
use App\Models\Template;
use App\Jobs\Email;
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
        Email::dispatchSync('AKTIVASI','x@x.x');
    }

    public function list(Request $request)
    {
        $column = [
            'rate',
            'original',
            'is_active',
            'created_at',
            'updated_at'
        ];
        
        $data = Rate::select('*');

        if($request->input('search')['value'] != ""){
            $data->where('rate','like','%'.$request->input('search')['value'].'%');
            $data->orWhere('original','like','%'.$request->input('search')['value'].'%');
            $data->orWhere('is_active','like','%'.$request->input('search')['value'].'%');
            $data->orWhere('created_at','like','%'.$request->input('search')['value'].'%');
            $data->orWhere('updated_at','like','%'.$request->input('search')['value'].'%');
        }

        if($request->input('order')[0]['column'] !== ""){
            $data = $data->orderBy($column[$request->input('order')[0]['column']],$request->input('order')[0]['dir']);
        }else{
            $data = $data->orderBy('updated_at','desc');
        }

        

        $data = $data->paginate($request->input('limit'));
        return response()->json($data);
    }
    
    public function update(Request $request,$id)
    {
        if($request->status == 1){
            $old = Rate::where('is_active','=',1)->update(['is_active' => 0]);
        }

        $data = Rate::find($id);
        $data->original = $request->original;
        $data->rate = $request->rate;
        $data->is_active = $request->status;
        $data->save();

    }
}
