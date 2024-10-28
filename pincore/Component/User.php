<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component;

use Firebase\JWT\Key;
use Illuminate\Database\Eloquent\Builder;
use Pinoox\Model\TokenModel;
use Pinoox\Model\UserModel;
use Firebase\JWT\JWT;
use Pinoox\Portal\Hash;
use Pinoox\Portal\Lang;

class User
{
    const COOKIE = 'cookie';
    const SESSION = 'session';
    const JWT = 'jwt';
    public static $login_key = null;
    private static $app = null;
    private static $msg = null;
    private static $type = self::COOKIE;
    private static $lifetime = 86400;
    private static $token = null;
    private static $token_key = null;
    private static $user = null;
    private static $updateLifetime = true;
    private static $updateTokenKey = false;

    private static $user_session_key = 'pinoox_user';
    private static $secret_key = 'BAF55D93DF7A2B3AA64722AA85448424AAB5CF4214AD2899CD9440BEC9B44894';

    public static function updateLifetime($status)
    {
        self::$updateLifetime = $status;
    }

    public static function updateTokenKey($status)
    {
        self::$updateTokenKey = $status;
    }

    public static function type($value)
    {
        self::$type = $value;
        self::reset();
    }

    public static function reset()
    {
        self::$token = null;
        self::$user = null;
    }

    public static function login($username, $password, $isActive = true)
    {
        self::$msg = null;

        if (self::isLoggedIn()) {
            self::$msg = Lang::get('~user.already_logged_in');;
            return false;
        }

        $user = UserModel::where(function (Builder $query) use ($username) {
            $query->where('email', $username)->orWhere('username', $username);
        });


        if ($isActive) {
            $user->where('status', UserModel::ACTIVE);
        }

        $user = $user->first();
        if (empty($user)) {
            self::$msg = Lang::get('~user.username_or_password_is_wrong');
            return false;
        }

        $user->makeVisible('password');
        if (!Hash::check($password, $user->password)) {
            self::$msg = Lang::get('~user.username_or_password_is_wrong');
            return false;
        }

        self::setToken($user);
        return true;
    }

    public static function isLoggedIn()
    {
        $token = self::getToken();
        return !empty($token);
    }

    public static function getToken($field = null)
    {
        if (empty(self::$token)) {
            $token_key = self::getTokenKey();
            if ($token_key)
                self::$token = Token::get($token_key);
        }

        if (!empty($field)) {
            return (isset(self::$token[$field])) ? self::$token[$field] : null;
        } else {
            return self::$token;
        }
    }

    public static function getTokenKey()
    {
        if (!empty(self::$token_key)) return self::$token_key;
        switch (self::$type) {
            case self::COOKIE:
                self::$token_key = Cookie::get(self::$user_session_key);
                break;
            case self::JWT:
                self::$token_key = self::authToken();
                break;
            case self::SESSION:
                self::$token_key = Session::get(self::$user_session_key);
                break;
        }
        return self::$token_key;
    }

    public static function authToken($token = null)
    {
        if (is_null($token)) {
            $header = apache_request_headers();
            $token = @$header['Authorization'];
            $token = empty($token) ? @$header['authorization'] : $token;
            if (empty($token))
                return false;
        }
        try {
            $payload = JWT::decode($token, new Key(self::$secret_key, 'HS256'));
            $payloadArray = (array)$payload;
            $key = key($payloadArray);
            $token_key = $payloadArray[$key];

            return $token_key;

        } catch (\Exception $e) {
        }

        return false;
    }

    public static function setUserSessionKey($key)
    {
        self::$user_session_key = $key;
    }

    public static function setToken(UserModel $user, $newKey = false)
    {
        Token::lifeTime(30, 'day');

        $user->makeHidden('password');
        $user_id = $user->user_id;
        $token_key = self::getTokenKey();
        if ($newKey) $token_key = null;
        $token_key = Token::generate($user->toArray(), self::$user_session_key, $user_id, $token_key);
        self::setClientToken($token_key);
    }

    private static function setClientToken($token_key)
    {
        self::$token_key = $token_key;
        self::$login_key = $token_key;
        switch (self::$type) {
            case self::COOKIE:
                Cookie::set(self::$user_session_key, $token_key, 999999999);
                break;
            case self::JWT:
                self::lifeTime(30, 'day');
                $payloadArray = [
                    self::$user_session_key => $token_key,
                ];
                self::$login_key = JWT::encode($payloadArray, self::$secret_key, 'HS256');
                break;
            case self::SESSION:
                Session::lifeTime(999999999);
                Session::set(self::$user_session_key, $token_key);
                break;
        }
    }

    public static function lifeTime($lifeTime, $unitTime = null)
    {
        self::$lifetime = $lifeTime;
        Token::lifeTime($lifeTime, $unitTime);
    }

    public static function getTokenData($field = null)
    {
        $data = self::getToken('token_data');


        if (!empty($field)) {
            return (isset($data[$field])) ? $data[$field] : null;
        } else {
            return $data;
        }
    }

    public static function getMessage()
    {
        return self::$msg;
    }

    public static function get($field = null)
    {
        $token = self::getToken();
        $user_id = @$token['user_id'];
        if ($user_id && empty(self::$user)) {
            $user = UserModel::where('user_id', $user_id)
                ->first();
            if ($user && $user->status == UserModel::ACTIVE) {
                $user->makeHidden('password');
                self::$user = $user->toArray();
                if (self::$updateTokenKey) {
                    $token_key = Token::changeKey($token['token_key'], true, false);
                    self::setClientToken($token_key);
                }
            } else {
                self::logout(null, false);
            }
        }

        if (!empty($field)) {
            return (isset(self::$user[$field])) ? self::$user[$field] : null;
        } else {
            return self::$user;
        }
    }

    public static function logout(): void
    {
        if (self::isLoggedIn()) {
            self::removeToken();
        }
    }

    private static function removeToken()
    {
        $token_key = self::getTokenKey();
        if (empty($token_key)) return;
        Token::delete($token_key);
        if (!TokenModel::where('token_key', $token_key)->first()) {
            switch (self::$type) {
                case self::COOKIE:
                    Cookie::destroy(self::$user_session_key);
                    break;
                case self::SESSION:
                    Session::remove(self::$user_session_key);
                    if (Session::has())
                        Session::regenerateId(true);
                    break;
            }
        }
    }

    public static function append($key, $val = null)
    {
        if (!self::isLoggedIn()) return;
        $token = self::getToken();
        $token_key = $token['token_key'];
        $token_data = $token['token_data'];
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $token_data[$k] = $v;
            }
        } else {
            $token_data[$key] = $val;
        }

        Token::setData($token_key, $token_data);
    }

    public static function set($data)
    {
        if (!self::isLoggedIn()) return;

        $token = self::getToken();
        $token_key = $token['token_key'];

        Token::setData($token_key, $data);
    }

}
    
