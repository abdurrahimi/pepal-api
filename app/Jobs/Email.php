<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\PHPMailer;
use App\Models\Email as Emails;
use App\Models\User;
use App\Models\Template;
use App\Models\Order;

class Email implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $to;
    public $tipe;
    public $order;

    public function __construct( $tipe,$to, $order = null)
    {
        $this->to = $to;
        $this->tipe = $tipe;
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = Emails::find(1);
        $mail = new PHPMailer();
        $mail->Encoding = "base64";
        $mail->SMTPAuth = true;
        $mail->Host = $config->host;
        $mail->Port = $config->port;
        $mail->Username = $config->username;
        $mail->Password = $config->password;
        $mail->SMTPSecure = 'TLS';
        $mail->isSMTP();
        $mail->IsHTML(true);
        $mail->CharSet = "UTF-8";
        $mail->From = $config->sender;
        $mail->addAddress($this->to);
        $user = User::where('email',$this->to)->first();
        $template = Template::where('type',$this->tipe)->first();

        if($this->tipe == 'AKTIVASI'){
            $mail->Subject="Aktivasi Akun | Saldobit";
            $actual_link = 'http://165.22.255.134';
            $url = $actual_link.'/api/activate?email='.$user->email.'&token='.$user->email_verified_token;
            $mail->Body= str_replace('[kode-aktivasi]','<a href="'.$url.'">'.$url.'</a>', $template->body);
        }

        if($this->tipe == 'OTP'){
            $mail->Subject="Kode OTP | Saldobit";
            $mail->Body= str_replace('[kode-otp]',$user->otp, $template->body);
        }
        
        if($this->tipe == 'FORGOT_PASSWORD'){
            $mail->Subject="Lupa Password Akun | Saldobit";
            $actual_link = 'http://165.22.255.134';
            $url = $actual_link.'/forgot-password?email='.$user->email.'&token='.$user->forgot_token;
            $mail->Body= str_replace('[kode-aktivasi]','<a href="'.$url.'">'.$url.'</a>', $template->body);
        }

        if($this->tipe == 'ORDER'){
            $mail->Subject="Informasi Order Anda | Saldobit";
            $order = Order::select('order.*','bank.bank as pembayaran','bank.norek','bank.nama')
            ->leftJoin('bank','bank.id','pembayaran_id')
            ->with([
                'user' => function($q){
                    return $q->select('id','name','email','phone');
                },
                'rate' => function($q){
                    return $q->select('id','rate');
                },
                'history'
            ])->where('order.id',$this->order)->first();

            $body = '<table
                        style="width:100%; border:1px solid black"
                    >
                            <tr>
                                <td style="border:1px solid black">Order Id</td>
                                <td style="border:1px solid black">Transaction</td>
                                <td style="border:1px solid black">Nominal</td>
                                <td style="border:1px solid black">Total Bayar</td>
                                <td style="border:1px solid black">Tanggal</td>
                                <td style="border:1px solid black">Pembayaran</td>
                                <td style="border:1px solid black">Status</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid black">'.$order->id.'</td>
                                <td style="border:1px solid black">'.$order->tipe == "paypal"
                                ? "<b>Top Up PayPal</b><br><i>".$order->target."</i>"
                                : "<b>Jasa Bayar</b><br><i>".$order->target."</i>"
                                .'</td>
                                <td style="border:1px solid black">'.$order->nominal.'</td>
                                <td style="border:1px solid black">'.$order->total.'</td>
                                <td style="border:1px solid black">'.$order->created_at.'</td>
                                <td style="border:1px solid black">'.$order->pembayaran.'</td>
                                <td style="border:1px solid black">'.$order->status.'</td>
                            </tr>
                    </table>';
            $mail->Body= str_replace('[order-detail]',$body, $template->body);
        }

        
        $mail->SMTPDebug = 1;
        $mail->Debugoutput = function($str, $level) {echo "debug level $level; message: $str"; echo "<br>";};
        
        if(!$mail->Send()) {
            echo $this->to;
            echo $mail->ErrorInfo;
            echo "Mail sending failed";
        } else {
            echo "Successfully sent";
        }
    }
}
