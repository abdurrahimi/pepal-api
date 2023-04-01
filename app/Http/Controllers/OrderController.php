<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $data = Order::where(DB::Raw(1),'=',1);
        if(auth()->user()->roles !== 'admin'){
            $data = $data->where('member_id','=',auth()->user()->id);
        }
        
        if($request->input('search')['value'] != ""){
            $data = $data->where(function($q) use ($request){
                return $q->where('target','=',$request->input('search')['value'])
                            ->orWhere('tipe',$request->input('search')['value'])
                            ->orWhere('pembayaran','=',strtolower($request->input('search')['value']));
            });
        }
        $data = $data->paginate($request->input('length'));
        //return $data;

        return response()->json($data);
    }

    public function create(Request $request)
    {
        if(!$request->setuju){
            return response()->json(['message'=>'anda harus menyetujui syarat dan ketentuan'],400);
        }
        DB::beginTransaction();
        try{
            $order = new Order;
            $order->member_id = Auth::user()->id;
            $order->nominal = $request->nominal;
            $order->tipe = $request->order;
            $order->target = $request->akun;
            $order->total = $request->nominal * 16000; // get data dari kurs di db. ambil berdasarkan id yang dikirim
            $order->pembayaran = strtoupper($request->metode);
            $order->status = 'Waiting';
            $order->save();
            DB::commit();
            return response()->json([
                "message" => "pesanan anda akan segera di proses"
            ]);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                "message" => "terjadi error",
                "error" => $ex->getMessage(),
            ],500);
        }
    }
}
