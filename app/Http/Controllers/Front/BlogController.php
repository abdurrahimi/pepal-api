<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostCategory;
use DB;

class BlogController extends Controller
{
    //
    public function index(Request $request)
    {
        $data = Post::select('id','image','image_alt','title','views','status','slug','content','created_at')->with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('post_category.id','category_id','post_id','category');
        }])->where('status',1)->paginate($request->input('length'))->onEachSide(1);

        return response()->json($data);
    }

    public function detail(Request $request)
    {
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        Post::where('slug',$request->slug)->update(['views' => DB::Raw('views+1')]);
        $data = Post::with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('post_category.id','category_id','post_id','category');
        }])->where('slug','=',$request->slug)->where('status',1)->first();

        $featured = Post::where('is_featured',1)->select('title','image','image_alt','slug')->limit(4)->get();

        $categories = PostCategory::with('category')->withCount('post')->groupBy('category_id')->orderBy('post_count','desc')->get();

        $popular = Post::select('title','image','image_alt','slug')->orderBy('views','desc')->limit(4)->get();

        $currentCategory = array();
        foreach($data->category as $cat) {
            $currentCategory[] = $cat->category_id;
        }

        $categoryRelated = PostCategory::select(DB::Raw('distinct(post_id) post_id'))->whereIn('category_id',$currentCategory)->where('post_id','!=', $data->id)->get();

        $realtedId = array();
        foreach($categoryRelated as $cat) {
            $realtedId[] = $cat->post_id;
        }

        $related = Post::select('id','image','image_alt','title','views','status','slug','content','created_at')->whereIn('id',$realtedId)->limit(3)->get();

        return response()->json([
            'body' => $data,
            'featured' => $featured,
            'categories' => $categories,
            'popular' => $popular,
            'related' => $related,
        ]);
    }
}
