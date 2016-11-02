<?php

namespace MyController\SSOServer\Traits;

use MyController\SSOServer\ValidationResult;

trait UserAuthTrait
{
    /**
     * System users
     *
     * PS: Normally you'd fetch the user info from a database, rather then declaring them in the code.
     *
     * @var array
     */
    private static $users = array (
        'jackie' => [
            'fullname' => 'Jackie Black',
            'email' => 'jackie.black@example.com',
            'password' => '$2y$10$lVUeiphXLAm4pz6l7lF9i.6IelAqRxV4gCBu8GBGhCpaRb6o0qzUO' // jackie123
        ],
        'john' => [
            'fullname' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => '$2y$10$RU85KDMhbh8pDhpvzL6C5.kD3qWpzXARZBzJ5oJ2mFoW7Ren.apC2' // john123
        ],
    );

    /**
     * Authenticate using user credentials
     *
     * @param string $account
     * @param string $password
     * @return ValidationResult
     */
    public function authenticate($account, $password)
    {
        if (!isset($account)) {
            return ValidationResult::error("account isn't set")->setReturnData([
                'account' => $account,
                'uid' => $account,
            ]);
        }

        if (!isset($password)) {
            return ValidationResult::error("password isn't set")->setReturnData([
                'account' => $account,
                'uid' => $account,
            ]);
        }

        if (!isset(self::$users[$account]) || !password_verify($password, self::$users[$account]['password'])) {
            return ValidationResult::error("Invalid credentials")->setReturnData([
                'account' => $account,
                'uid' => $account,
            ]);
        }

        return ValidationResult::success()->setReturnData([
            'account' => $account,
            'uid' => $account,
        ]);
    }

    /**
     * Get the user information
     *
     * @param string $account
     * @return array | null
     */
    public function getUserInfo($account)
    {
        if (!isset(self::$users[$account])) return null;

        $user = compact('account') + self::$users[$account];
        unset($user['password']);

        return $user;
    }

}
