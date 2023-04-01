<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TitleList;
use DB;

class SearchController extends Controller
{
    //

    public function index(Request $request)
    {
        if(null === $request->input('keyword') || $request->input('keyword') == null ){
            return response()->json(['message' => 'no keyword!']);
        }
        $keyword = $request->input('keyword');
        $data = TitleList::select('image_url','slug','title','rating')->where(DB::raw('lower(title)'),'like','%'.strtolower($keyword).'%')
        ->whereHas('latestChapters',function($q){
            return $q->where('image_list','!=',NULL);
        })
        ->limit(5)->get();
        return response()->json($data);
    }

    public function detail(Request $request)
    {
        if(null === $request->input('keyword') || $request->input('keyword') == null ){
            return response()->json(['message' => 'no keyword!']);
        }
        $keyword = $request->input('keyword') == "undefined" ? "" : $request->input('keyword');
        $data = tap(TitleList::with(['latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        },'detail' =>function($q){
            return $q->select('parent_id','category');
        }])
        ->whereHas('latestChapters',function($q){
            return $q->where('image_list','!=',NULL);
        })
        ->where(DB::raw('lower(title)'),'like','%'.strtolower($keyword).'%')->paginate(10))->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });;
        return response()->json($data);
    }

    public function filter(Request $request)
    {
        
        
        $data = TitleList::with(['latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug');
        }, 'detail' => function($q){
            return $q->select('parent_id','jenis','category');
        }])
        ->whereHas('detail',function($q) use ($request){
            if($request->input('status') != ""){
                $q = $q->where(DB::Raw('lower(status)'),'like','%'.strtolower($request->input('status')).'%');
            }

            if($request->input('type') != ""){
                $q = $q->where(DB::Raw('lower(jenis)'),'like','%'.strtolower($request->input('type')).'%');
            }

            if($request->input('category') != ""){
                $category = explode(',',$request->input('category'));
                foreach($category as $value){
                    $q->where(DB::Raw('lower(category)'),'like',strtolower('%'.str_replace('-',' ',$value).'%'));
                }
            }

            return $q;
        });
        if($request->input('score') != ""){
            $data = $data->whereBetween('rating', [$request->input('score'), (int)$request->input('score')+1]);
        }
        if($sort = $request->input('sort') != ""){
            $data = $data->orderBy('title','asc');
        }
        $data = tap($data->whereHas('latestChapters',function($q){
            return $q->where('image_list','!=',null);
        })
        ->paginate(10))->map(function($q) use ($request) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3)); 
            return $q;
        });
        return response()->json($data);
    }
}
