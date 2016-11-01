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
     *
     * @var array
     */
    protected $brokers = [];

    /**
     * 缓存 SSOBroker的sso_session 与 SSOServer的linkedId 的关联的 有效时长, 单位分钟
     *
     * @var int
     */
    protected $cacheLifeTime = 600;

    /**
     * 通过 SSOBroker的sso_session 从缓存获取到其关联的 linkedId, linkedId 是某个UA的 session_id
     *
     * @var string
     */
    protected $linkedId = '';

    /**
     * SSOServer constructor.
     *
     * @override
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->options = [
            //通过参数`fail_exception`, 能控制`Jasny\SSO`发生错误时的处理方式
            //不为空 empty() 时 抛出异常`Jasny\SSO\Exception`, 否则会设置 HTTP response 并且 exit.
            'fail_exception' => true,
        ];

        $this->app = $app;

        $this->cache = $this->createCacheAdapter();
    }

    /**
     * 获取当前类实例, 为 MyController\SSOServer\Facades\SSOServerFacade 的特殊需求服务
     *
     * @return $this
     */
    public function getInstance()
    {
        return $this;
    }

    /**
     * Create a cache to store the broker session id.
     *
     * @override
     */
    protected function createCacheAdapter()
    {
        //使用 Laravel 架构里的 Illuminate\Contracts\Cache 系统
        $this->cacheLifeTime = $this->app['config']->get('sso-server.cacheLifeTime');
        return $this->app['cache'];
    }



    /**
     * Start the session for broker requests to the SSO server
     *
     * @override
     */
    public function startBrokerSession()
    {
        if (isset($this->brokerId)) return;

        if (!isset($_GET['sso_session'])) {
            return $this->fail("Broker didn't send a session key", 400);
        }

        $sid = $_GET['sso_session'];

        $linkedId = $this->cache->get($sid);

        if (!$linkedId) {
            return $this->fail("The broker session id isn't attached to a user session", 403);
        }

        $this->linkedId = $linkedId;

        $this->brokerId = $this->validateBrokerSessionId($sid);
    }



    /**
     * Start the session when a user visits the SSO server
     * @override
     */
    protected function startUserSession()
    {
        // 使用 Lumen的SESSION方案 代替 PHP的原生SESSION方案, 这样才能支持分布式运行
        /**
         |----------------------------------------------------------------------
         |   启用 Lumen的SESSION 需要在 `/bootstrap/app.php` 里开启
         |
         |   $app->middleware([
         |       Illuminate\Cookie\Middleware\EncryptCookies::class,
         |       Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
         |       Illuminate\Session\Middleware\StartSession::class,
         |     //Illuminate\View\Middleware\ShareErrorsFromSession::class,
         |     //Laravel\Lumen\Http\Middleware\VerifyCsrfToken::class,
         |   ]);
         |----------------------------------------------------------------------
         |
         */
    }



    /**
     * Attach a user session to a broker session
     *
     * @override
     */
    public function attach()
    {
        $this->detectReturnType();

        if (empty($_REQUEST['broker'])) return $this->fail("No broker specified", 400);
        if (empty($_REQUEST['token'])) return $this->fail("No token specified", 400);

        if (!$this->returnType) return $this->fail("No return url specified", 400);

        $checksum = $this->generateAttachChecksum($_REQUEST['broker'], $_REQUEST['token']);

        if (empty($_REQUEST['checksum']) || $checksum != $_REQUEST['checksum']) {
            return $this->fail("Invalid checksum", 400);
        }

        $this->startUserSession();
        $sid = $this->generateSessionId($_REQUEST['broker'], $_REQUEST['token']);

        $this->cache->put($sid, session()->getId(), $this->cacheLifeTime);
        $this->outputAttachSuccess();
    }




    /**
     * Set session data
     *
     * @param string $key
     * @param string $value
     */
    protected function setSessionData($key, $value)
    {
        $key = $this->linkedId . '-' . $key;

        if (!isset($value)) {
            $this->cache->forget($key);
            return;
        }

        $this->cache->put($key, $value, $this->cacheLifeTime);
    }

    /**
     * Get session data
     *
     * @param type $key
     * @return null|string
     */
    protected function getSessionData($key)
    {
        $key = $this->linkedId . '-' . $key;

        return $this->cache->get($key, null);
    }




    /**
     * Get the API secret of a broker and other info
     *
     * @implement
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
     * @implement
     * @param string $account
     * @param string $password
     * @return ValidationResult
     */
    protected function authenticate($account, $password)
    {
        return $this->app['MyController\SSOServer\Contracts\UserAuthContract']
            ->authenticate($account, $password);
    }

    /**
     * Get the user information
     *
     * @implement
     * @param string $account
     * @return array | null
     */
    protected function getUserInfo($account)
    {
        return $this->app['MyController\SSOServer\Contracts\UserAuthContract']
            ->getUserInfo($account);
    }
}
