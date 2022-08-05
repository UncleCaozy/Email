<?php

namespace App\Console\Commands;

use App\Models\UserEmailTemp;
use Illuminate\Console\Command;

class EmailUserInit extends Command {

    const EMAIL_USER_LIST_REDIS_KEY = 'user_email_list';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emailUserInit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '批量邮件用户入队列，单进程执行';

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
        UserEmailTemp::query()->where('is_send', 0)->where('id','>',30000)
            ->chunk(10000, function ($users) use ($redis) {
                foreach ($users as $user) {
                    $res = $redis->lpush(self::EMAIL_USER_LIST_REDIS_KEY, json_encode($user->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    echo "init email user lPush redis $res\n";
                }
            });
    }
}
