<?php

namespace MyController\SSOServer\Contracts;

use MyController\SSOServer\ValidationResult;

 /*
 |-------------------------------------------------------------
 | 您需要自己实现 UserAuthContract 接口, 并将 UserAuthContract的具体实现类 绑定至 UserAuthContract
 | 在 App\Providers\AppServiceProvider 的 register() 里增加:
 | $this->app->bind('MyController\SSOServer\Contracts\UserAuthContract', 'UserAuthContract的具体实现类');
 |-------------------------------------------------------------
 */
interface UserAuthContract
{
    /**
     * Authenticate using user credentials
     *
     * @param string $account
     * @param string $password
     * @return ValidationResult
     */
    public function authenticate($account, $password);

    /**
     * Get the user information
     *
     * @param string $account
     * @return array | null
     */
    public function getUserInfo($account);
}
