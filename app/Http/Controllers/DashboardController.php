<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

use DB;

class DashboardController extends Controller
{
    public function index()
    {

        $totalOrder = Order::select(DB::Raw('tipe,status, COALESCE(count(*),0) as total'));
/*         $totalOrderMonth = Order::select(DB::Raw('tipe,status, COALESCE(count(*),0) as total'))->whereRaw('MONTH(created_at) = MONTH(now())');
        $totalOrderWeek = Order::select(DB::Raw('tipe,status, COALESCE(count(*),0) as total'))->whereRaw('YEARWEEK(`created_at`, 1) = YEARWEEK(CURDATE(), 1)'); */

        $transaksi = Order::select(DB::Raw('status,tipe, COALESCE(sum(nominal),0) as total'));
        $transaksiMonth = Order::select(DB::Raw('tipe, COALESCE(sum(nominal),0) as total'))->whereRaw('MONTH(created_at) = MONTH(now()) and YEAR(created_at) = YEAR(now())');
        $transaksiWeek = Order::select(DB::Raw('tipe, COALESCE(sum(nominal),0) as total'))->whereRaw('YEARWEEK(`created_at`, 1) = YEARWEEK(CURDATE(), 1)');

        $latestOrder = Order::select('order.*','bank.bank as pembayaran')
        ->leftJoin('bank','bank.id','pembayaran_id');

        if(Auth::user()->roles != 'admin'){
            $totalOrder = $totalOrder->where('member_id','=',Auth::user()->id);

            $transaksi = $transaksi->where('member_id','=',Auth::user()->id);
            $transaksiMonth = $transaksiMonth->where('member_id','=',Auth::user()->id);
            $transaksiWeek = $transaksiWeek->where('member_id','=',Auth::user()->id);

            $latestOrder = $latestOrder->where('member_id','=',Auth::user()->id);
        }

        $totalOrder = $totalOrder->groupBy(DB::Raw('status,tipe'))->get();

        $transaksi = $transaksi->groupBy(DB::Raw('status,tipe'))->get();
        $transaksiMonth = $transaksiMonth->groupBy(DB::Raw('tipe'))->get();
        $transaksiWeek = $transaksiWeek->groupBy(DB::Raw('tipe'))->get();

        $latestOrder = $latestOrder->orderBy('created_at','desc')->limit(10)->get();

        $data = [
            "total" => $totalOrder,
            "transaksi" =>   [
                "all" => $transaksi,
                "week" => $transaksiWeek,
                "month" => $transaksiMonth,
            ],
            "latest" => $latestOrder,
        ];



        return response()->json($data);
    }
}
