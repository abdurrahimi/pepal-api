<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rate;

class RateController extends Controller
{
    //
    public function index()
    {
        $data = Rate::select('id','rate')->where('is_active',1)->first();
        return response()->json($data);
    }
}
