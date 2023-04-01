<?php

namespace App\Http\Controllers\Front;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TitleList;
use App\Models\ReadingList;
use App\Models\Rating;
use DB;

class DetailController extends Controller
{
    //
    public function index($slug)
    {
        $data = TitleList::with(['detail','chapter' => function($q) {
            return $q->where('image_list','!=',NULL)->orderByRaw('CAST(REGEXP_SUBSTR(chapter,"[0-9]+") AS unsigned) desc');
        },'firstChapter'=> function($q){
           return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        }])->where('slug',$slug)->has('chapter')->first();
        if($data !== null){
            $data->reading_list = "";
        }
        if (Auth::check()) {
            //return "OK";
            $checkReading = ReadingList::where('slug',$slug)->where('user_id',Auth::user()->id)->first();
            if(!empty($checkReading)){
                $data->reading_list = $checkReading->type;
            }
        }

        return response()->json($data);
    }

    public function random(){
        $data = TitleList::with([
            'detail',
            'chapter' => function($q) {
                return $q->where('image_list','!=',NULL)->orderByRaw('CAST(REGEXP_SUBSTR(chapter,"[0-9]+") AS unsigned) desc');
            },
            'firstChapter'=> function($q){
                return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
            }
        ])
        ->whereHas('chapter',function($q){
            return $q->where('image_list','!=',NULL);
        })
        ->inRandomOrder()->limit(1)->first();

        $data->reading_list = "";
        if (Auth::check()) {
            //return "OK";
            $checkReading = ReadingList::where('slug',$data->slug)->where('user_id',Auth::user()->id)->first();
            if(!empty($checkReading)){
                $data->reading_list = $checkReading->type;
            }
        }

        return response()->json($data);
    }

    public function getRating($id){
        $data = Rating::select(DB::Raw('cast(avg(rating) as decimal(10,1)) rating'),DB::Raw('count(*) total'))->where('parent_id',$id)->first();
        $user = 0;
        if(Auth::check()){
            $user = Auth::user()->id;
        }
        $myRating = Rating::select('rating')->where('parent_id',$id)->where('rated_by',$user)->first();
        return response()->json(['rating' => $data,'my' => $myRating]);
    }


    public function rating(Request $request){
        if(!Auth::check()){
            return response()->json(['message' => 'not atuhorized'],402);
        }
        $validator = Validator::make($request->all(), [
            'komik_id' => 'required',
            'rating' => 'required|in:1,5,10',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $rating = Rating::updateOrCreate(
            ['parent_id' => $request->komik_id, 'rated_by' => Auth::user()->id],
            [
                'rating' => $request->rating,
            ]
        );

        return $rating;
    }
}
