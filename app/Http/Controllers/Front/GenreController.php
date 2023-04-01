<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Detail;
use App\Models\Category;
use App\Models\Chapter;
use DB;

class GenreController extends Controller
{
    //

    public function index($genre)
    {
        $data = tap(Detail::select('id','parent_id','category','jenis')->with(['title'=>function($q){
            return $q->select('id','slug','title','image_url');
        },'latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug');
        }])
        ->whereHas("latestChapters",function($q){
            return $q->where('image_list','!=',NULL);
        })
        ->whereRaw("lower(category) like (?)","%".strtolower($genre)."%")->has('latestChapters')->paginate(10)->onEachSide(1))->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });
        $category = Category::where('value',$genre)->first();
        return response()->json(['data' => $data, 'category' => $category]);
    }

    public function completed()
    {
        $data = tap(Detail::select('id','parent_id','category','jenis')->with(['title'=>function($q){
            return $q->select('id','slug','title','image_url');
        },'latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug');
        }])
        ->whereHas("latestChapters",function($q){
            return $q->where('image_list','!=',NULL);
        })
        ->where("status","like","%Tamat%")->paginate(10))->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });
        return response()->json(['data' => $data]);
    }

    public function latest()
    {
        $list = Chapter::select('parent_id',DB::Raw('max(created_at)'))->where('image_list','!=',NULL)->orderBy(DB::Raw('max(created_at)','desc'))->groupBy('parent_id')->limit(50)->get();
        $ids = [];
        foreach($list as $value){

            $ids[] = $value->parent_id;
        }
        $implode = implode(',', $ids);
        $data = tap(Detail::select('id','parent_id','category','jenis')->whereIn('parent_id',$ids)->with(['title'=>function($q){
            return $q->select('id','slug','title','image_url');
        },'latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug');
        }])
        ->whereHas("latestChapters",function($q){
            return $q->where('image_list','!=',NULL);
        })
        ->orderByRaw("FIELD(id, $implode)")
        ->paginate(10))->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });
        return response()->json(['data' => $data]);
    }

    public function newRelease()
    {
        $data = tap(Detail::select('id','parent_id','category','jenis')->with(['title'=>function($q){
            return $q->select('id','slug','title','image_url');
        },'latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug');
        }])
        ->whereHas("latestChapters",function($q){
            return $q->where('image_list','!=',NULL)->orWhere('image_list','!=','');
        })
        ->orderBy('created_at','desc')
        ->paginate(10))->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });
        return response()->json(['data' => $data]);
    }

    public function mostViewed()
    {
        $data = tap(Detail::select('id','parent_id','category','jenis')->with(['title'=>function($q){
            return $q->select('id','slug','title','image_url');
        },'latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug');
        }])
        ->whereHas("latestChapters",function($q){
            return $q->where('image_list','!=',NULL)->orWhere('image_list','!=','');
        })
        ->paginate(10))->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });
        return response()->json(['data' => $data]);
    }

    public function getByType(Request $request, $type)
    {
        $data = Detail::select('title_list.id','category','jenis','slug','title','image_url','rating')->with([/* 'title'=>function($q) use ($request){
            $q = $q->select('id','slug','title','image_url','rating');

            return $q;
        }, */'latestChapters' => function($q){
            return $q->select('parent_id','chapter','slug');
        }])
        ->join("title_list","title_list.id","=","detail.parent_id")
        ->whereHas("latestChapters",function($q){
            return $q->where('image_list','!=',NULL);
        })

        ->whereRaw("lower(jenis) like (?)","%".strtolower($type)."%")
        ->has('latestChapters');

        if($request->input('sort') == "score"){
            $data->orderBy('rating','desc');
        }

        if($request->input('sort') == "name-az"){
            $data->orderBy('title','asc');
        }

        if($request->input('sort') == "release-date"){
            $data->orderBy('title_list.created_at','asc');
        }

        if($request->input('sort') == "latest-update"){
            $data->orderBy('title_list.created_at','desc');
        }

        $data = tap($data->paginate(10)->onEachSide(1))->map(function($q) {
            $q = $q->setRelation('latestChapters', $q->latestChapters->take(3));
            //$q = $q->setRelation('title', $q->title->orderBy('rating','desc')->get());
            return $q;
        });
        $category = Category::where('value',$type)->first();
        return response()->json(['data' => $data, 'category' => $category]);
    }

}
