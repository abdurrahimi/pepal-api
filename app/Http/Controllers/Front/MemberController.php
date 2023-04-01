<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ReadingList;
use App\Models\User;
use App\Models\Komentar;
use App\Models\ContinueReading;
use App\Models\UserLike;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Report;
use DB;

class MemberController extends Controller
{
    //

    public function __construct()
    {
        $this->middleware('auth:api',['except' => ['countKomentar','showKomentar']]);
    }

    public function changeProfile(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'password_change' => 'required|boolean',
            'old_password' => 'required_if:password_change:true',
            'password' => 'required_if:password_change:true|confirmed'
        ]);

        $user = User::findOrFail(Auth::user()->id);
        $user->name = $request->name;
        if($request->password_change){
            $user->password = Hash::make($request->password);
        }
        $user->save();
        return response()->json(['status' => 'success']);
    }


    public function addReadingList(Request $request)
    {
        $this->validate($request, [
            'slug' => 'required',
            'type' => 'required|in:reading,onhold,plan,dropped,completed',
        ]);

        $check = ReadingList::where('user_id',Auth::user()->id)
                ->where('slug',$request->slug)->first();
        if(empty($check)){
            $read = new ReadingList;
            $read->user_id = Auth::user()->id;
            $read->slug = $request->slug;
        }else{
            $read = ReadingList::find($check->id);
        }
        $read->type = $request->type;
        $read->save();
        return response()->json(['status' => 'success']);

    }

    public function showReadingList($category='')
    {
        $data = ReadingList::where('user_id',Auth::user()->id)->with(['manga' => function($q){
            return $q->select('id','title','image_url','slug');
        },'manga.detail' => function($q){
            return $q->select('parent_id','category');
        },'manga.latestChapters'=>function($q){
           return $q->select('parent_id','chapter','slug');
        }])
        ->whereHas('manga.latestChapters',function($q){
            return $q->where('image_list','!=',NULL);
        });
        if($category != ''){
            $data->where('type',$category);
        }
        $data = tap($data->paginate(10))->map(function($q) {
            $q->manga->setRelation("latestChapters",$q->manga->latestChapters->take(3));
            return $q;
        });
        return response()->json($data);
    }

    public function removeReadingList(Request $req)
    {
        return ReadingList::where('slug',$req->slug)->where('user_id',Auth::user()->id)->delete();
    }

    public function showContinueReading()
    {
        $data = ContinueReading::where('user_id',Auth::user()->id)->with(['manga' => function($q){
            return $q->select('id','title','image_url','slug');
        },'manga.detail' => function($q){
            return $q->select('parent_id','category');
        },'chapter' => function($q){
            return $q->select('slug','chapter');
        }])->paginate(10);
        return response()->json($data);
    }

    public function removeContinueReading($id)
    {
        return ContinueReading::where('id',$id)->where('user_id',Auth::user()->id)->delete();
    }

    public function countKomentar(Request $request)
    {
        $data = Komentar::where('parent_id','=',NULL)
            ->where('komik_slug',$request->komik_slug)
            ->where('chapter_slug', $request->chapter_slug)
            ->count();
        return response()->json($data);
    }

    public function showKomentar(Request $request)
    {
        $user_id = Auth::check() ? Auth::user()->id :"";
        $data = Komentar::select('*',DB::raw('IF(spam = 1, "komentar ini telah dihapus karena SPAM",komentar) as komentar'))
            ->where('parent_id','=',NULL)
            ->where('komik_slug',$request->komik_slug)
            ->where('chapter_slug', $request->chapter_slug)
            ->with(['subComment'=>function($q) use ($user_id){
                return $q->withCount(['like as likes'=>function($q){
                    return $q->where('status','like');
                },'like as liked' => function($q)  use ($user_id) {
                    return $q->where('user_id',$user_id)->where('status','like');
                },'like as dislikes'=>function($q){
                    return $q->where('status','dislike');
                },'like as disliked' => function($q)  use ($user_id) {
                    return $q->where('user_id',$user_id)->where('status','dislike');
                }]);
            },'user' => function($q){
                return $q->select('id','name','profile_pict');
            },'subComment.user' =>function($q){
                return $q->select('id','name','profile_pict');
            },'report' => function($q){
                return $q->select('id')->where('report_by', Auth::user()->id);
            }])
            ->withCount(['like as likes'=>function($q){
                return $q->where('status','like');
            },'like as liked' => function($q) use ($user_id){
                return $q->where('user_id',$user_id)->where('status','like');
            },'like as dislikes'=>function($q){
                return $q->where('status','dislike');
            },'like as disliked' => function($q)  use ($user_id) {
                return $q->where('user_id',$user_id)->where('status','dislike');
            }])
            ->paginate(10);
        return response()->json($data);
    }

    public function createKomentar(Request $request)
    {
        $this->validate($request, [
            'komik_slug' => 'required',
            'chapter_slug' => 'required',
            'komentar' => 'required|string'
        ]);
        $kom = new Komentar;
        $kom->parent_id = $request->parent_id;
        $kom->user_id = Auth::user()->id;
        $kom->komik_slug = $request->komik_slug;
        $kom->chapter_slug = $request->chapter_slug;
        $kom->komentar = $request->komentar;
        $kom->spoil = $request->is_spoil == true ? 1 : 0;
        $kom->save();
    }

    public function respondComment(Request $request,$status)
    {
        switch($status){
            case 'like' :
                $check = UserLike::where('user_id',Auth::user()->id)->where('parent_id',$request->comment_id)->first();
                if(!empty($check)){
                    $model = UserLike::find($check->id);
                    if($check->status == 'like'){
                        $model->delete();
                        return response()->json(['success']);
                        break;
                    }
                }else{
                    $model = new UserLike;
                    $model->user_id = Auth::user()->id;
                    $model->parent_id = $request->comment_id;
                }
                $model->status = 'like';
                $model->save();
                break;
            case 'dislike' :
                $check = UserLike::where('user_id',Auth::user()->id)->where('parent_id',$request->comment_id)->first();
                if(!empty($check)){
                    $model = UserLike::find($check->id);
                    if($check->status == 'dislike'){
                        $model->delete();
                        return response()->json(['success']);
                        break;
                    }
                }else{
                    $model = new UserLike;
                    $model->user_id = Auth::user()->id;
                    $model->parent_id = $request->comment_id;
                }
                $model->status = 'dislike';
                $model->save();
                break;
            default:
                return response()->json(['not available']);
                break;
        }
    }

    public function reportComment(Request $request)
    {

        $validator = $this->validate($request, [
            'komentar_id' => 'required',
            'type' => [
                'required',
                Rule::in(['SPAM', 'SPOIL']),
            ]
        ]);
        $check = Report::where('parent_id',$request->komentar_id)->where('report_by',Auth::user()->id)->where('type',$request->type)->first();
        if(!empty($check)){
            return response()->json(["message" => "you've already reported this comment as ".$request->type],400);
        }
        $model = new Report;
        $model->parent_id = $request->komentar_id;
        $model->type = $request->type;
        $model->report_by = Auth::user()->id;
        $model->save();
        return response()->json(["message" => "success"]);
    }
}
