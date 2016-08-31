<?php

namespace MyController\SSOServer;

use Jasny\ValidationResult;
use Jasny\SSO\Server;
use MyController\SSOServer\Exceptions\SSOServerException;

class SSOServer extends Server
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Registered brokers
     * @var array
     */
    protected $brokers = [];

    /**
     * SSOServer constructor.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app)
    {
        parent::__construct([
            //通过参数`fail_exception`, 能控制`Jasny\SSO`发生错误时的处理方式
            //不为空 empty() 时 抛出异常`Jasny\SSO\Exception`, 否则会设置 HTTP response 并且 exit.
            'fail_exception' => true,
        ]);

        $this->app = $app;
    }

    /**
     * Get the API secret of a broker and other info
     *
     * @param string $brokerId
     * @return array | null
     */
    protected function getBrokerInfo($brokerId)
    {
        if (empty($this->brokers)) {
            $this->brokers = $this->app['config']->get('sso-server.brokers');
        }

        return isset($this->brokers[$brokerId]) ? $this->brokers[$brokerId] : null;
    }

    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     * @return ValidationResult
     */
    protected function authenticate($username, $password)
    {
        return $this->app['MyController\SSOServer\Contracts\UserAuthContract']
            ->authenticate($username, $password);
    }

    /**
     * Get the user information
     *
     * @param string $username
     * @return array | null
     */
    protected function getUserInfo($username)
    {
        return $this->app['MyController\SSOServer\Contracts\UserAuthContract']
            ->getUserInfo($username);
    }
}
