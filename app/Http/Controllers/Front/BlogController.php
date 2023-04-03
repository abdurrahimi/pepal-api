<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;

class BlogController extends Controller
{
    //
    public function index(Request $request)
    {
        $data = Post::select('id','image','title','views','status','slug','content','created_at')->with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('post_category.id','category_id','post_id','category');
        }])->paginate($request->input('length'))->onEachSide(1);
        return response()->json($data);
    }

    public function detail(Request $request)
    {
        $data = Post::with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('post_category.id','category_id','post_id','category');
        }])->where('slug','=',$request->slug);
        return response()->json($data);
    }
}
