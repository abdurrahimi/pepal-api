<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use DB;

class BlogController extends Controller
{
    //
    public function index(Request $request)
    {
        $data = Post::select('id','image','title','views','status','slug','content','created_at')->with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('post_category.id','category_id','post_id','category');
        }])->where('status',1)->paginate($request->input('length'))->onEachSide(1);
        return response()->json($data);
    }

    public function detail(Request $request)
    {
        Post::where('slug',$request->slug)->update(['views' => DB::Raw('views+1')]);
        $data = Post::with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('post_category.id','category_id','post_id','category');
        }])->where('slug','=',$request->slug)->where('status',1)->first();
        return response()->json($data);
    }
}
