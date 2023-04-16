<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Rate;
use App\Models\OrderHistory;
use App\Models\Voucher;
use App\Models\User;
use App\Traits\Helper;
use App\Jobs\Email;
use DB;

class OrderController extends Controller
{
    use Helper;

    public function index(Request $request)
    {
        $column = [
            'id',
            'tipe',
            'nominal',
            'total',
            'created_at',
            'pembayaran_id',
            'status'
        ];

        $data = Order::select('order.*','bank.bank as pembayaran')
                ->leftJoin('bank','bank.id','pembayaran_id');
        if(auth()->user()->roles !== 'admin'){
            $data = $data->where('member_id','=',auth()->user()->id);
        }

        if($request->input('search')['value'] != ""){
            $data = $data->where(function($q) use ($request){
                return $q->where(DB::Raw('lower(target)'),'=',$request->input('search')['value'])
                            ->orWhere('tipe','=',$request->input('search')['value'])
                            ->orWhere(DB::Raw('lower(status)'),'=',$request->input('search')['value'])
                            ->orWhere('order.id','=', $request->input('search')['value'])
                            ->orWhere(DB::Raw('lower(bank.bank)'),'=',strtolower($request->input('search')['value']));
            });
        }

        if($request->input('order')[0]['column'] !== ""){
            $data = $data->orderBy($column[$request->input('order')[0]['column']] , $request->input('order')[0]['dir']);
        }

        $data = $data->paginate($request->input('length'));
        //return $data;

        return response()->json($data);
    }

    public function show(Request $request, $id)
    {
        $data = Order::select('order.*','bank.bank as pembayaran','bank.norek','bank.nama')
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

        if($request->applied){
            $voucher = Voucher::where('kode',$request->kupon)->where('active_start','<=',DB::Raw('now()'))->where('active_end','>=',DB::raw('now()'))->first();
            if(empty($voucher)){
                return response()->json([
                    'valid' => false,
                    'message' => 'voucher tidak valid'
                ],422);
            }

            $check = Order::where('member_id', Auth::user()->id)->where('voucher_id', $voucher->id)->count();
            if($check > $voucher->max_penggunaan){
                return response()->json([
                    'valid' => false,
                    'message' => 'voucher hanya dapat digunakan sebanyak '.$voucher->max_penggunaan.' kali'
                ],422);
            }

            if($voucher->tipe_order != 'all' && $voucher->tipe_order != $request->order){
                return response()->json([
                    'valid' => false,
                    'message' => 'Voucher tidak dapat digunakan untuk order tipe ini'
                ],422);
            }

            if($voucher->min_harga > ($request->nominal * $rate->rate)){
                return response()->json([
                    'valid' => false,
                    'message' => 'Minimal order untuk voucher ini adalah '.$voucher->min_harga
                ],422);
            }
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

            $discount = 0;
            if($request->applied){
                $discount = $voucher->jumlah;
            }

            $fee = 0;
            if($request->order == 'bayar'){
                //((data.rate.rate * data.nominal) - data.discount) * (4 / 100) > 30000
                $fee = 30000;
                $countFee = ($rate->rate * $request->nominal) - (4 / 100);
                if($countFee > $fee){
                    $fee = $countFee;
                }
            }

            if($request->order == 'paypal'){
                $order->total = ($request->nominal * $rate->rate) - $discount; // get data dari kurs di db. ambil berdasarkan id yang dikirim
            }

            if($request->order == 'bayar'){
                if($request->nominal <50){
                    $order->total = ($request->nominal * ($rate->rate + 500)) - $discount; // get data dari kurs di db. ambil berdasarkan id yang dikirim
                }else{
                    $order->total = ($request->nominal * ($rate->rate)) - $discount;
                }
            }


            $order->pembayaran_id = strtoupper($request->metode);
            $order->status = 'Waiting';
            if($request->applied){
                $order->diskon = $voucher->jumlah;
                $order->voucher_id = $voucher->id;
            }
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

            $user = User::find($order->member_id);
            Email::dispatch('ORDER',$user->email, $id)->onQueue('email');
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

    public function uploadBukti(Request $request,$id)
    {
        try{
            $image = $this->storeImageLocal($request->bukti);

            $order = Order::find($id);
            $order->bukti = $image;
            $order->save();
            DB::commit();

            return response()->json([
                "message" => "bukti berhasil disimpan"
            ]);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                "message" => "terjadi error",
                "error" => $ex->getMessage(),
            ],500);
        }
    }

    public function applyVoucher(Request $request)
    {
        $voucher = Voucher::where('kode',$request->kupon)->where('active_start','<=',DB::Raw('now()'))->where('active_end','>=',DB::raw('now()'))->first();
        if(empty($voucher)){
            return response()->json([
                'valid' => false,
                'message' => 'voucher tidak valid'
            ],422);
        }

        $check = Order::where('member_id', Auth::user()->id)->where('voucher_id', $voucher->id)->count();
        if($check > $voucher->max_penggunaan){
            return response()->json([
                'valid' => false,
                'message' => 'voucher hanya dapat digunakan sebanyak '.$voucher->max_penggunaan.' kali'
            ],422);
        }

        if($voucher->tipe_order != 'all' && $voucher->tipe_order != $request->order){
            return response()->json([
                'valid' => false,
                'message' => 'Voucher tidak dapat digunakan untuk order tipe ini'
            ],422);
        }

        $rate = Rate::where('is_active',1)->first();
        if($voucher->min_harga > ($request->nominal * $rate->rate)){
            return response()->json([
                'valid' => false,
                'message' => 'Minimal order untuk voucher ini adalah '.$voucher->min_harga
            ],422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Voucher berhasil digunakan',
            'voucher' => $voucher
        ]);

    }
}
