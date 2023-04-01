<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SiteSetting as Setting;

class SiteSetting implements ShouldQueue
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
        //
        
        shell_exec("chmod u+x ../front/.script/deploy.sh");
        $data = Setting::first();

        $myfile = fopen("../front/.env", "w") or die("Unable to open file!");
        $txt = "baseUrl=$data->api_url\n";
        $txt .= "title=$data->title\n";
        $txt .= "icon=$data->icon\n";
        $txt .= "logo=$data->logo\n";
        $txt .= "google_ads_code=$data->google_ads_code\n";
        $txt .= "meta_desc=$data->meta_desc\n";
        $txt .= "meta_tag=$data->meta_tag\n";
        $txt .= "twitter=$data->twitter\n";
        $txt .= "discord=$data->discord\n";
        $txt .= "instagram=$data->instagram\n";
        $txt .= "keyword=$data->keyword\n";
        $txt .= "description=$data->description\n";
        fwrite($myfile, $txt);
        fclose($myfile);
        shell_exec("../front/.script/deploy.sh");
    }
}
