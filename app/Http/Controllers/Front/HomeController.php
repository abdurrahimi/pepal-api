<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TitleList;
use App\Models\Category;
use App\Models\Detail;
use App\Models\Chapter;
use App\Models\Slider;
use App\Models\Komentar;
use DB;

class HomeController extends Controller
{
    //

    public function getSliderItem()
    {
        $slideItem = Slider::select('parent_id')->get();
        $idList = array();
        foreach($slideItem as $value){
            $idList[] = $value->parent_id;
        }
        $data = TitleList::select('id','slug','rating','image_url','title')->with(['detail'=>function($q){
            return $q->select('jenis',DB::raw('SUBSTRING(`sinopsis`, 1, 80) sinopsis'),'parent_id','category');
        },'latestChapter' => function($q){
           return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        },'firstChapter'=> function($q){
           return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        }])->has('latestChapter')->whereIn('id',$idList)->limit(10)->get();
        return response()->json($data);
    }

    public function getGenre()
    {
        $data = Category::all();
        return response()->json($data);
    }

    public function getTrending()
    {
        $data = Detail::select('parent_id','jenis')->with(['title' => function($q){
            return $q->select('id','title','slug','rating','image_url');
        },'latestChapter','firstChapter'=> function($q){
           return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        }])->whereHas('latestChapter',function($q){
            return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
         })->orderBy('jumlah_pembaca','desc')->limit(10)->get();
        return response()->json($data);
    }

    public function getRecomended()
    {
        $data = TitleList::with(['latestChapter'=>function($q){
            $q->select('parent_id','chapter','slug');
        },'detail'=>function($q){
            return $q->select('parent_id','category','jenis',);
        },'firstChapter'=> function($q){
           return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        }])->whereHas('latestChapter',function($q){
            return $q->where('image_list','!=',NULL);
         })->inRandomOrder()->limit(10)->get();
        return response()->json($data);
    }

    public function getLatest()
    {
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $data = Chapter::select('parent_id',DB::raw('MAX(CAST(REGEXP_SUBSTR(chapter,"[0-9]+") AS unsigned)) chapter'))
            ->where('image_list','!=',NULL)
            ->join('title_list','title_list.id','chapter.parent_id')
            ->where('title_list.deleted_at','=',null)
            ->groupBy('chapter.parent_id')
            ->orderBy('chapter.created_at','desc')
            ->limit(10)
            ->get();
        $ids = array();
        foreach($data as $value){
            $ids[] = $value->parent_id;
        }

        $data = TitleList::with(['latestChapters'=>function($q){
            $q->select('parent_id','chapter','slug');
        },'detail'=>function($q){
            return $q->select('parent_id','category','jenis',);
        },'firstChapter'=> function($q){
            return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        }])->whereHas('latestChapters',function($q){
            return $q->where('image_list','!=',NULL);
        })->whereIn('id',$ids)->limit(10)->get()->map(function($q) {
            $q->setRelation('latestChapters', $q->latestChapters->take(3));
            return $q;
        });

        return Response()->json($data);
    }

    public function getCompleted()
    {
        $data = Detail::select('parent_id','status','jenis','category')->with(['title' => function($q){
            return $q->select('id','title','slug','rating','image_url');
        },'latestChapter'=> function($q){
           return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        },'firstChapter'=> function($q){
           return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
        }])->whereHas('latestChapter',function($q){
            return $q->where('image_list','!=',NULL);
        })->has('firstChapter')->where('status',' Tamat')->limit(24)->get();
        return response()->json($data);
    }

    public function mostViewed()
    {
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $today = TitleList::with(['latestChapter'=> function($q){
               return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
            },'detail'=>function($q){
                return $q->select('parent_id','jenis','category');
            }])
            ->select('title_list.id','title','rating','image_url','slug',DB::Raw('coalesce(vw.count,0) count'))
            ->leftJoin(DB::Raw('( select id,parent_id,count(id) count from views where date(created_at) = CURDATE() group by parent_id) vw'),'title_list.id','vw.parent_id')
            ->whereHas('latestChapter', function($q){
                return $q->where('image_list','!=',NULL);
            })
            ->orderBy('count','desc')
            ->limit(10)->get();

        $week = TitleList::with(['latestChapter'=> function($q){
               return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
            },'detail'=>function($q){
                return $q->select('parent_id','jenis','category');
            }])
            ->select('title_list.id','title','rating','image_url','slug',DB::Raw('coalesce(vw.count,0) count'))
            ->leftJoin(DB::Raw('( select id,parent_id,count(id) count from views where YEARWEEK(created_at) = YEARWEEK(CURDATE()) group by parent_id) vw'),'title_list.id','vw.parent_id')
            ->whereHas('latestChapter', function($q){
                return $q->where('image_list','!=',NULL);
            })
            ->orderBy('count','desc')
            ->limit(10)->get();

        $month = TitleList::with(['latestChapter'=> function($q){
               return $q->select('parent_id','chapter','slug')->where('image_list','!=',NULL);
            },'detail'=>function($q){
                return $q->select('parent_id','jenis','category');
            }])
            ->select('title_list.id','title','rating','image_url','slug',DB::Raw('coalesce(vw.count,0) count'))
            ->leftJoin(DB::Raw('( select id,parent_id,count(id) count from views where YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)= MONTH(CURDATE()) group by parent_id) vw'),'title_list.id','vw.parent_id')
            ->whereHas('latestChapter', function($q){
                return $q->where('image_list','!=',NULL);
            })
            ->orderBy('count','desc')
            ->limit(10)->get();

        return response()->json(['data' => ['today' => $today, 'week' => $week, 'month' => $month]]);
    }

    public function latestComment()
    {
        $latest = Komentar::select('komentar','title','komik_slug','chapter_slug','user_id','komentar.created_at as tanggal')
                ->with(['user' => function($q){
                    return $q->select('id','name','profile_pict');
                }])
                ->where('komentar.parent_id','=', NULL)
                ->join('title_list','title_list.slug','komentar.komik_slug')
                //->join('chapter','chapter.slug','komentar.chapter_slug')
                //->where('chapter.parent_id','=','title_list.id')
                ->where('spam','=',NULL)
                ->where('spoil','!=',1)
                ->orderBy('komentar.created_at','desc')
                ->limit(10)
                ->get();
        $top =  Komentar::select('komentar','title','komik_slug','chapter_slug','user_id','komentar.created_at as tanggal')
                ->where('komentar.parent_id','=', NULL)->withCount(['like'=>function($q){
                    return $q->where('status','like');
                }])
                ->with(['user' => function($q){
                    return $q->select('id','name','profile_pict');
                }])
                ->join('title_list','title_list.slug','komentar.komik_slug')
                //->join('chapter','chapter.slug','komentar.chapter_slug')
                //->where('chapter.parent_id','=','title_list.id')
                ->where('spam','=',NULL)
                ->where('spoil','!=',1)
                ->orderBy('like_count','desc')
                ->limit(10)
                ->get();
        return response()->json(['top' => $top, 'latest' => $latest]);
    }

    public function topComment()
    {
        return response()->json(['message'=>"okok"]);
    }
}
