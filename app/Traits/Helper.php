<?php
namespace App\Traits;
use Goutte\Client;
use App\Models\Cloudflare;
use Illuminate\Support\Facades\Storage;
/**
 *
 */
trait Helper
{
    function storeImageLocal($base64_image){
        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $data = base64_decode($data);
        $ext = explode('/', mime_content_type($base64_image))[1];
        $name = time() . "." . $ext;
        Storage::disk('public')->put($name, $data);
        return "/files/image/".$name;
    }
}