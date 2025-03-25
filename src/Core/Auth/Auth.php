<?php
namespace JiFramework\Core\Auth;

use JiFramework\Config\Config;
use JiFramework\Core\Database\QueryBuilder;

class Auth
{
    /**
     * The QueryBuilder instance.
     *
     * @var QueryBuilder
     */
    protected $db;

    /**
     * Session keys for admin and user.
     */
    protected $adminSessionKey;
    protected $userSessionKey;

    /**
     * Remember me cookie names.
     */
    protected $adminRememberCookie;
    protected $userRememberCookie;

    /**
     * The tokens table name.
     *
     * @var string
     */
    protected $tokensTable;

    /**
     * Create a new Auth instance.
     */
    public function __construct()
    {
        $this->db = new QueryBuilder();

        // Set session keys from Config constants
        $this->adminSessionKey = Config::ADMIN_SESSION_KEY;
        $this->userSessionKey = Config::USER_SESSION_KEY;

        // Set remember me cookie name from Config constant
        $this->adminRememberCookie = Config::ADMIN_REMEMBER_COOKIE;
        $this->userRememberCookie = Config::USER_REMEMBER_COOKIE;

        // Set tokens table name (default to 'tokens' if not set)
        $this->tokensTable = 'tokens';

        // Check remember me tokens
        $this->checkRememberMe();
    }

    /**
     * Log in an admin.
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return bool
     */
    public function adminLogin($email, $password, $remember = false)
    {
        $admin = $this->db->table('admin')
                          ->where('email', $email)
                          ->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION[$this->adminSessionKey] = $admin['id'];

            if ($remember) {
                $this->setRememberMeToken($admin['id'], 'admin');
            }

            return true;
        }

        return false;
    }

    /**
     * Log in a user.
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return bool
     */
    public function userLogin($email, $password, $remember = false)
    {
        $user = $this->db->table('users')
                         ->where('email', $email)
                         ->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION[$this->userSessionKey] = $user['id'];

            if ($remember) {
                $this->setRememberMeToken($user['id'], 'user');
            }

            return true;
        }

        return false;
    }

    /**
     * Log out the admin.
     *
     * @return void
     */
    public function adminLogout()
    {   
        // Delete remember me token
        $this->db->table($this->tokensTable)
                 ->where('user_id', $_SESSION[$this->adminSessionKey])
                 ->where('type', 'admin')
                 ->delete();

        // Unset session
        unset($_SESSION[$this->adminSessionKey]);

        // Unset cookie
        setcookie($this->adminRememberCookie, '', time() - 3600, '/');
    }

    /**
     * Log out the user.
     *
     * @return void
     */
    public function userLogout()
    {
        // Delete remember me token 
        $this->db->table($this->tokensTable)
                 ->where('user_id', $_SESSION[$this->userSessionKey])
                 ->where('type', 'user')
                 ->delete();

        // Unset session
        unset($_SESSION[$this->userSessionKey]);

        // Unset cookie
        setcookie($this->userRememberCookie, '', time() - 3600, '/');
    }

    /**
     * Check if an admin is logged in.
     *
     * @return bool
     */
    public function isAdminLoggedIn()
    {
        return isset($_SESSION[$this->adminSessionKey]);
    }

    /**
     * Check if a user is logged in.
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return isset($_SESSION[$this->userSessionKey]);
    }

    /**
     * Get the currently authenticated admin ID.
     * 
     * @return int|null
     */
    public function getAdminId() {
        return $this->isAdminLoggedIn() ? $_SESSION[$this->adminSessionKey] : null;
    }

    /**
     * Get the currently authenticated user ID.
     *
     * @return int|null
     */
    public function getUserId() {
        return $this->isUserLoggedIn() ? $_SESSION[$this->userSessionKey] : null;
    }

    /**
     * Get the currently authenticated admin.
     *
     * @return array|null
     */
    public function getAdmin()
    {
        if ($this->isAdminLoggedIn()) {
            return $this->db->table('admin')
                            ->where('id', $_SESSION[$this->adminSessionKey])
                            ->fetch();
        }

        return null;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return array|null
     */
    public function getUser()
    {
        if ($this->isUserLoggedIn()) {
            return $this->db->table('users')
                            ->where('id', $_SESSION[$this->userSessionKey])
                            ->fetch();
        }

        return null;
    }

    /**
     * Set the remember me token.
     *
     * @param int $userId
     * @param string $type 'admin' or 'user'
     * @return void
     */
    protected function setRememberMeToken($userId, $type)
    {
        $token = bin2hex(random_bytes(32));
        $expire = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days from now

        $this->db->table($this->tokensTable)
                 ->insert([
                     'user_id'        => $userId,
                     'token'          => $token,
                     'expire_datetime'=> $expire,
                     'type'           => $type,
                 ]);

        if ($type === 'admin') {
            setcookie($this->adminRememberCookie, $token, time() + (86400 * 30), '/', '', true, true);
        } else {
            setcookie($this->userRememberCookie, $token, time() + (86400 * 30), '/', '', true, true);
        }
    }

    /**
     * Check the remember me token and log in the user/admin if valid.
     *
     * @return void
     */
    protected function checkRememberMe()
    {
        if (!$this->isAdminLoggedIn() && isset($_COOKIE[$this->adminRememberCookie])) {
            $token = $_COOKIE[$this->adminRememberCookie];
            $record = $this->db->table($this->tokensTable)
                               ->where('token', $token)
                               ->where('type', 'admin')
                               ->where('expire_datetime', '>', date('Y-m-d H:i:s'))
                               ->fetch();

            if ($record) {
                $_SESSION[$this->adminSessionKey] = $record['user_id'];
            }
        }

        if (!$this->isUserLoggedIn() && isset($_COOKIE[$this->userRememberCookie])) {
            $token = $_COOKIE[$this->userRememberCookie];
            $record = $this->db->table($this->tokensTable)
                               ->where('token', $token)
                               ->where('type', 'user')
                               ->where('expire_datetime', '>', date('Y-m-d H:i:s'))
                               ->fetch();

            if ($record) {
                $_SESSION[$this->userSessionKey] = $record['user_id'];
            }
        }
    }

    /**
     * Clear expired tokens.
     *
     * @return void
     */
    public function clearExpiredTokens()
    {
        $this->db->table($this->tokensTable)
                 ->where('expire_datetime', '<', date('Y-m-d H:i:s'))
                 ->delete();
    }
}


