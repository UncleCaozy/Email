<?php

namespace App\Console\Commands;

use App\Models\UserEmailTemp;
use Illuminate\Console\Command;

class EmailSend extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emailSend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '多进程执行批量发送邮件';

    const EMAIL_TPL      = 'pt_1224';               //本次批量发送邮件的模版
    const EMAIL_TPL_NAME = 'CSGO十周年邮件';              //本次批量发送邮件的需求名
    const DING_AT_USER   = ['18268062307'];                //本次批量邮件需求负责人

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }


    public function handle() {
        $redis = app('redis.connection');
        while (true) {
            $emailUser = $redis->rPop(EmailUserInit::EMAIL_USER_LIST_REDIS_KEY);
            if ($emailUser) {
                $emailUser = json_decode($emailUser, true);
                $email     = trim($emailUser['email']);
                $vars      = new \stdClass(); //必须为对象，否则发送邮件接口会报错：net.sf.json.JSONArray cannot be cast to net.sf.json.JSONObject
                try {
                    $lock_key = 'user_email_' . $email;
                    if ($redis->setnx($lock_key, 1)) {
                        $redis->expire($lock_key, 86400);
                        $res = $this->send_mail(self::EMAIL_TPL, [$email], $vars);
                        $res = json_decode($res, true);

                        echo $email . "---------------" . serialize($res) . "\n";
                        if ($res['message'] == 'success') {
                            UserEmailTemp::query()->where('id', $emailUser['id'])->increment('is_send', 1);
                        }
                    } else {
                        echo $email . "---------------is locked" . "\n";
                    }

                } catch (\Throwable $e) {
                    echo $email . "---------------" . $e->getMessage() . "\n";
                }
                if (intval($emailUser['id']) === 994030){
                    $this->sendDingTalk();
                }
            } else {
                echo "all done!\n";
                $redis->flushdb();
                exit();
            }
        }
    }

    public function send_mail($template, $emails, $vars) {
        //您需要登录SendCloud创建API_USER，使用API_USER和API_KEY才可以进行邮件的发送。
        $config = config('mail.send_cloud');
        $url    = $config['url'];
        $param  = [
            'api_user'             => $config['api_user'],
            'api_key'              => $config['api_key'],
            'from'                 => $config['from'],
            'fromname'             => $config['fromname'],
            'substitution_vars'    => json_encode(['to' => $emails, 'sub' => $vars]),
            'template_invoke_name' => $template,
        ];

        $data    = http_build_query($param);
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data,
            ],
        ];
        $context = stream_context_create($options);
        return file_get_contents($url, FILE_TEXT, $context);
    }


    function request_by_curl($remote_server, $post_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            ['Content-Type: application/json;charset=utf-8']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function sendDingTalk() {
        $webhook       = "https://oapi.dingtalk.com/robot/send?access_token=571fc9b5ba1bf9124edd65dc96193ea358ba348bfd8860e07ae58c8d2a1450e5";
        $all           = UserEmailTemp::query()->count('id');
        $send_count    = UserEmailTemp::query()->where('is_send', 1)->count('id');
        $message       = "批量邮件需求发送完成\n邮件需求：【" . self::EMAIL_TPL_NAME . "】\n目标总数【{$all}】\n发送总数【{$send_count}】\n成功数量【{$send_count}】\n";
        $atMobile      = self::DING_AT_USER;
        $isAtAll       = false;
        $data          = [
            'msgtype' => 'text',
            'text'    =>
                ['content' => $message],
            'at'      => [
                'atMobiles' => $atMobile,
                'isAtAll'   => $isAtAll,
            ],

        ];
        $data_string   = json_encode($data);
        $this->request_by_curl($webhook, $data_string);
    }
}
