<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TitleList;
use DB;

class AzController extends Controller
{
    //

    public function index($query='')
    {
        $data = TitleList::with(['latestChapters'=> function($q){
            return $q->select('parent_id','chapter','slug');
        },'detail' => function($q){
            return $q->select('parent_id','category','jenis');
        }])->whereHas("latestChapters",function($q){
            $q->where('image_list','!=',NULL);
        });
        if(strtolower($query) !== '' && strtolower($query) !== 'all'){
            if(strlen($query) > 1)
                $data = $data->where(DB::raw('UPPER(title)'),'NOT RLIKE',DB::raw("'^[A-Z]'"));
            else
                $data = $data->where(DB::raw('lower(title)'),'LIKE',$query.'%');
        }
        $data = tap($data->orderBy('title','asc')->paginate(10)->onEachSide(1))->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });
        return response()->json(['data' => $data]);
    }
}
