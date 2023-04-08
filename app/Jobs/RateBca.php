<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Rate;
use Goutte\Client;
use DB;

class RateBca implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new Client();
        $crawler = $client->request('GET', 'https://www.bca.co.id/id/informasi/kurs');
        $list = $crawler->filter('tr')->each(function ($node) {
            //echo $node->attr('code')."\n<br>";
            if($node->attr('code') === 'USD'){
                $node->filter('td > p')->each(function($el){
                    //echo $el->attr('rate-type') . "<br>";
                    if($el->attr('rate-type') === 'ERate-buy'){
                        $bcaRate = (int)str_replace('.','',$el->text());
                        $rate = ($bcaRate + 600) +(50 - ($bcaRate%50));
                        echo $rate;
                        $check = Rate::where('is_active',1)->first();
                        if(empty($check) ){
                            $model = new Rate;
                            $model->rate = $rate;
                            $model->is_active = 1;
                            $model->original = $bcaRate;
                            $model->save();
                        }
                        if(!empty($check) && $check->original != $bcaRate){
                            $old = Rate::find($check->id);
                            $old->is_active = 0;
                            $old->save();
                            
                            $model = new Rate;
                            $model->rate = $rate;
                            $model->is_active = 1;
                            $model->original = $bcaRate;
                            $model->save();
                        }
                    }
                });
            }
        });
    }
}
