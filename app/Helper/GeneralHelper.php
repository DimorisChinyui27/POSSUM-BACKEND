<?php

use App\Models\Question;
use App\Models\Topic;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserTopic;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use robertogallea\LaravelPython\Services\LaravelPython;
use Spatie\Valuestore\Valuestore;
use Symfony\Component\Process\Process;

/**
 * get value from the json file
 * @param string $key
 * @return array|string|null
 */
function getSettingsOf(string $key): array|string|null
{
    $settings = Valuestore::make(config_path('settings.json'));
    return $settings->get($key);
}

if (!function_exists('generateUserName')) {
    /**
     * generate a username
     * @param $username
     * @return string
     */
    function generateUsername($username): string
    {
        $username = explode('@', $username)[0];
        $username = substr(Str::slug($username), 0, 20);
        if ($user = User::whereUsername($username)->first()) {
            $username = $user->username . $user->id;
            return generateUsername($username);
        } else {
            return $username;
        }
    }
}
if (!function_exists('generateWalletId')) {
    /**
     * generate unique wallet id
     * @return string
     */
    function generateWalletId(): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $wallet_id = substr(str_shuffle($chars), 0, 4);
        if (Wallet::where('wallet_id', $wallet_id)->exists()) {
            return generateWalletId();
        }
        return 'WALL-' . $wallet_id;
    }
}

if (!function_exists('generateTransactionId')) {
    /**
     * generate a unique transaction id of each transaction
     * @return string
     */
    function generateTransactionId(): string
    {
        $chars = '0123456789';
        $transaction_id = substr(str_shuffle($chars), 0, 10);
        if (Transaction::whereTransactionId($transaction_id)->exists()) {
            return generateTransactionId();
        }
        return $transaction_id;
    }
}

function videoFormat()
{
    return [
        'video/x-flv',
        'video/mp4',
        'application/x-mpegURL',
        'video/MP2T',
        'video/3gpp',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-ms-wmv'
    ];
}

if (!function_exists('getQuestions')) {
    /**
     *
     * @return mixed
     */
    function getQuestions(): mixed
    {
        if (!empty(Auth::user()->topics)) {
            $topics = Auth::user()->topics->pluck('id');
            if ($topics->isNotEmpty()) {
                $topics = Topic::withCount('questions')->orderBy('questions_count', 'desc')
                    ->limit(1)->get()->pluck('id');
            }
            return Question::join('questions_topics', 'questions_topics.question_id', '=', 'questions.id')
                ->orderByRaw("questions_topics.id = ? desc", $topics)->top();
        } else {
            return Question::orderByDesc('created_at');
        }
    }
}

if (!function_exists('updateRanking')) {
    function updateRanking($usersTopic, $playerId, $success)
    {
        $process = new LaravelPython();
        $usersRanking =  $process->run(base_path() . '/processes/ranking/runRanking.py', array(
            json_encode($usersTopic),
            (string)$playerId,
            $success ? 1:0
        ));
        $usersRanking = json_decode($usersRanking, true);
        foreach ($usersRanking as $userRanking) {
            UserTopic::whereTopicId($usersTopic[0]['topic_id'])
                ->where('user_id', $userRanking['user_id'])
                ->update($userRanking);
        }
    }
}
