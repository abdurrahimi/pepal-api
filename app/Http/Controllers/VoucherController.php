<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;
use DB;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $data = Voucher::paginate($request->input('length'));
        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $check = Voucher::where('kode',$request->kode)->count();
        if($check > 0){
            return response()->json(['message' => 'Kode voucher sudah ada'],400);
        }
        
        DB::beginTransaction();
        try{
            $model = new Voucher;
            $model->kode = $request->kode;
            $model->tipe = $request->tipe;
            $model->tipe_order = $request->tipe_order;
            $model->jumlah = $request->jumlah;
            $model->min_harga = $request->min_harga;
            $model->max_harga = $request->max_harga;
            $model->max_discount = $request->max_discount;
            $model->max_penggunaan = $request->max_penggunaan;
            $model->active_start = $request->active_start;
            $model->active_end = $request->active_end;
            $model->save();
            DB::commit();
            return response()->json([
                "message" => "success",
            ]);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                "message" => "terjadi error",
                "error" => $ex->getMessage(),
            ],500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = Voucher::find($id);
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $check = Voucher::where('kode',$request->kode)->where('id','!=',$id)->count();
        if($check > 0){
            return response()->json(['message' => 'Kode voucher sudah ada'],400);
        }

        DB::beginTransaction();
        try{
            $model = Voucher::find($id);
            $model->kode = $request->kode;
            $model->tipe = $request->tipe;
            $model->jumlah = $request->jumlah;
            $model->min_harga = $request->min_harga;
            $model->max_harga = $request->max_harga;
            $model->max_discount = $request->max_discount;
            $model->max_penggunaan = $request->max_penggunaan;
            $model->active_start = $request->active_start;
            $model->active_end = $request->active_end;
            $model->save();
            DB::commit();
            return response()->json([
                "message" => "success",
            ]);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                "message" => "terjadi error",
                "error" => $ex->getMessage(),
            ],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try{
            $model = Voucher::find($id);
            $model->delete();
            DB::commit();
            return response()->json([
                "message" => "success",
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
