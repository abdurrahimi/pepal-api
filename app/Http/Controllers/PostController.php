<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\Helper;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Support\Facades\Storage;

use DB;

class PostController extends Controller
{
    use Helper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //return Storage::url('file.jpg');
        $data = Post::select('id','image','title','views','status','slug')->with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('post_category.id','category_id','post_id','category');
        }])->paginate($request->input('length'));
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        DB::beginTransaction();
        try{

            $model = new Post;
            $model->title = $request->title;
            $model->slug = $request->slug;
            $model->content = $request->content;
            $model->is_featured = $request->is_featured;
            $model->date_published = $request->date_published;
            if (preg_match('/^data:image\/(\w+);base64,/', $request->image)) {
                $image = $this->storeImageLocal($request->image);
                $model->image = $image;
            }else{
                $model->image = $request->image;
            }
            $model->views = 0;
            $model->image_alt = $request->image_alt;
            $model->meta_desc = $request->meta_desc;
            $model->meta_title = $request->meta_title;
            $model->status = $request->status;
            $model->save();
            //return response()->json(["xxx" => $request->category],500);
            foreach($request->category as $value){
                $cat = new PostCategory;
                $cat->post_id = $model->id;
                $cat->category_id = $value['id'];
                $cat->save();
            }

            DB::commit();
            return response()->json([
                "message" => "success"
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
        $data = Post::with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('category_id as id','post_category.id as post_category_id','category_id','post_id','category');
        }])->where('id','=',$id)->first();
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
        $data = Post::with(['category' => function($q){
            return $q->leftJoin('category','post_category.category_id','category.id')->select('category_id as id','post_category.id as post_category_id','post_id','category');
        }])->where('id','=',$request->id);
        return response()->json($data);
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
        DB::beginTransaction();
        try{

            $model = Post::find($id);
            $model->title = $request->title;
            $model->slug = $request->slug;
            $model->content = $request->content;
            $model->date_published = $request->date_published;
            if (preg_match('/^data:image\/(\w+);base64,/', $request->image)) {
                $image = $this->storeImageLocal($request->image);
                $model->image = $image;
            }else{
                $model->image = $request->image;
            }
            $model->image_alt = $request->image_alt;
            $model->is_featured = $request->is_featured;
            $model->meta_desc = $request->meta_desc;
            $model->meta_title = $request->meta_title;
            $model->status = $request->status;
            $model->save();
            //return response()->json(["xxx" => $request->category],500);
            PostCategory::where('post_id',$id)->delete();
            foreach($request->category as $value){
                $cat = new PostCategory;
                $cat->post_id = $model->id;
                $cat->category_id = $value['id'];
                $cat->save();
            }

            DB::commit();
            return response()->json([
                "message" => "success"
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
        //
        Post::find($id)->delete();
        return response()->json(['message' => 'success']);
    }
}
