<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TitleList;
use App\Models\ContinueReading;
use App\Models\Views;
use Illuminate\Support\Facades\Auth;

class ReadController extends Controller
{
    //

    public function index($manga,$chapter)
    {
        $data = TitleList::select('id','title','slug')->with(['chapter' => function ($q) {
            return $q->select('id','parent_id','slug','chapter');
        },
        'currentChapter' => function($q) use ($chapter) {
            return $q->where('slug',$chapter);
        }])->where('slug',$manga)
        ->first();
       // dd($data);
        Views::create([
            'parent_id' =>$data->currentChapter->parent_id
        ]);

        if (Auth::check()) {
            $check = ContinueReading::where('slug',$manga)
                    ->where('chapter_slug',$chapter)
                    ->where('user_id',Auth::user()->id)->first();
            if(empty($check)){
                $read = new ContinueReading;
                $read->user_id = Auth::user()->id;
            }else{
                $read = ContinueReading::find($check->id);
            }
            $read->slug = $manga;
            $read->chapter_slug = $chapter;
            $read->save();
        }
        return response()->json($data);
    }
}
