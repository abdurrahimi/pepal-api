<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Rate;
use App\Models\OrderHistory;
use DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $data = Order::select('order.*','bank.bank as pembayaran')
                ->leftJoin('bank','bank.id','pembayaran_id');
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

    public function show(Request $request, $id)
    {
        $data = Order::select('order.*','bank.bank as pembayaran')
                ->leftJoin('bank','bank.id','pembayaran_id')
                ->with([
                    'user' => function($q){
                        return $q->select('id','name','email','phone');
                    },
                    'rate' => function($q){
                        return $q->select('id','rate');
                    },
                    'history'
                ])->where('order.id',$id)->first();
        if(Auth::user()->roles != 'admin' && $data->user->id != Auth::user()->id){
            return response()->json(['message'=>'unauthorized'],401);
        }
        return response()->json($data);
    }

    public function create(Request $request)
    {
        if(!isset($request->rate) || $request->rate == ""){
            return response()->json(['message'=>'pesanan tidak valid'],400);
        }

        $rate = Rate::where("id",$request->rate)->first();
        if(empty($rate) || $rate->is_active !== 1){
            return response()->json(['message'=>'rate telah berubah, harap periksa kembali pesanan anda'],400);
        }

        if($request->tipe == 'paypal' && $request->nominal < 30){
            return response()->json(['message'=>'minimal pemesanan adalah $30'],400);
        }

        if($request->nominal > 2000){
            return response()->json(['message'=>'maksimal pemesanan adalah $2000'],400);
        }
        

        DB::beginTransaction();
        try{

            $order = new Order;
            $order->member_id = Auth::user()->id;
            $order->nominal = $request->nominal;
            $order->tipe = $request->order;
            if($request->tipe == 'bayar'){
                $order->jenis = $request->jenis ?? "";
            }
            $order->rate = $request->rate;
            $order->target = $request->akun;
            $order->total = $request->nominal * 16000; // get data dari kurs di db. ambil berdasarkan id yang dikirim
            $order->pembayaran_id = strtoupper($request->metode);
            $order->status = 'Waiting';
            $order->pesan = $request->pesan;
            $order->save();

            DB::commit();
            return response()->json([
                "message" => "pesanan anda akan segera di proses",
                "id" => $order->id
            ]);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                "message" => "terjadi error",
                "error" => $ex->getMessage(),
            ],500);
        }
    }

    public function storeCatatan(Request $request,$id)
    {
        DB::beginTransaction();
        try{
            $order = Order::find($id);
            $order->pesan = $request->pesan;
            $order->save();
            DB::commit();
            return response()->json([
                "message" => "catatan berhasil disimpan"
            ]);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                "message" => "terjadi error",
                "error" => $ex->getMessage(),
            ],500);
        }
    }

    public function storeHistory(Request $request,$id)
    {
        DB::beginTransaction();
        try{
            $history = new OrderHistory;
            $history->order_id = $id;
            $history->pesan = $request->pesan;
            $history->status = $request->status;
            $history->save();
            
            $order = Order::find($id);
            $order->status = $request->status;
            $order->save();

            DB::commit();
            return response()->json([
                "message" => "History berhasil disimpan"
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
