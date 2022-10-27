<?php

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

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
     * @return string
     */
    function generateWalletId()
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $wallet_id = substr(str_shuffle($chars), 0, 4);
        if (Wallet::where('wallet_id', $wallet_id)->exists()) {
            return generateWalletId();
        }
        return 'WALL-'.$wallet_id;
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
