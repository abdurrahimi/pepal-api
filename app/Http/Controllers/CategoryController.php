<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Category;


class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = Category::select('*');
        if($request->input('search')['value'] != ""){
            return $data->where('category','=',$request->input('search')['value']);       
        }
        $data = $data->paginate($request->input('length'));
        return response()->json($data);
    }

    public function all()
    {
        $data = Category::all();
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
        $model = new Category;
        $model->category = $request->category;
        $model->save();
        return response()->json(['message' => 'success']);
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
        $model = Category::findOrFail($id);
        $model->category = $request->category;
        $model->save();
        return response()->json(['message' => 'success']);
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
        Category::find($id)->delete();
        return response()->json(['message' => 'success']);
    }
}
