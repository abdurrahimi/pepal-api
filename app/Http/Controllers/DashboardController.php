<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use Carbon\Carbon;

use DB;

class DashboardController extends Controller
{
    public function index()
    {

        $totalOrder = Order::select(DB::Raw('tipe,status, COALESCE(count(*),0) as total'));
        $transaksi = Order::select(DB::Raw('status,tipe, COALESCE(sum(nominal),0) as total'));
        $transaksiMonth = Order::select(DB::Raw('tipe, COALESCE(sum(nominal),0) as total'))->whereRaw('MONTH(created_at) = MONTH(now()) and YEAR(created_at) = YEAR(now())');
        $transaksiWeek = Order::select(DB::Raw('tipe, COALESCE(sum(nominal),0) as total'))->whereRaw('YEARWEEK(`created_at`, 1) = YEARWEEK(CURDATE(), 1)');

        $latestOrder = Order::with('user')->select('order.*','bank.bank as pembayaran')
        ->leftJoin('bank','bank.id','pembayaran_id');

       /*  $orderCountPerDate = Order::select(DB::Raw('count(id)'))->groupBy() */

        if(Auth::user()->roles != 'admin'){
            $totalOrder = $totalOrder->where('member_id','=',Auth::user()->id);

            $transaksi = $transaksi->where('member_id','=',Auth::user()->id);
            $transaksiMonth = $transaksiMonth->where('member_id','=',Auth::user()->id);
            $transaksiWeek = $transaksiWeek->where('member_id','=',Auth::user()->id);

            $latestOrder = $latestOrder->where('member_id','=',Auth::user()->id);
        }

        $tahun = date('Y'); //Mengambil tahun saat ini
        $bulan = date('m'); //Mengambil bulan saat ini
        $tanggal = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

        $topup = [];
        $bayar = [];
        $date = [];
        for ($i=1; $i < $tanggal+1; $i++) { 
            
            $date[] = $i;
            $t = Order::select(DB::Raw('created_at, coalesce(count(id),0) total'))
                        ->where(DB::Raw('YEAR(created_at)'),'=',$tahun)
                        ->where(DB::Raw('month(created_at)'),'=',$bulan)
                        ->where(DB::Raw('day(created_at)'),'=',$i)
                        ->where('tipe','paypal');
            $b = Order::select(DB::Raw('created_at, coalesce(count(id),0) total'))
                        ->where(DB::Raw('YEAR(created_at)'),'=',$tahun)
                        ->where(DB::Raw('month(created_at)'),'=',$bulan)
                        ->where(DB::Raw('day(created_at)'),'=',$i)
                        ->where('tipe','bayar');
            if(Auth::user()->roles != 'admin'){
                $t = $t->where('member_id','=',Auth::user()->id);
                $b = $b->where('member_id','=',Auth::user()->id);
            }

            $topup[] = $t->groupBy('created_at')->first()->total ?? 0;
            $bayar[] = $b->groupBy('created_at')->first()->total ?? 0;
            if($i > date('d')) break;
                        
        }

        $totalOrder = $totalOrder->groupBy(DB::Raw('status,tipe'))->get();

        $transaksi = $transaksi->groupBy(DB::Raw('status,tipe'))->get();
        $transaksiMonth = $transaksiMonth->groupBy(DB::Raw('tipe'))->get();
        $transaksiWeek = $transaksiWeek->groupBy(DB::Raw('tipe'))->get();

        $latestOrder = $latestOrder->orderBy('created_at','desc')->limit(5)->get();

        $data = [
            "total" => $totalOrder,
            "transaksi" =>   [
                "all" => $transaksi,
                "week" => $transaksiWeek,
                "month" => $transaksiMonth,
            ],
            "latest" => $latestOrder,
            "daily" => [
                "topup" => $topup,
                "bayar" => $bayar,
                "date" => $date,
            ]
        ];



        return response()->json($data);
    }
}
