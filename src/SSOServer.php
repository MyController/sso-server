<?php

namespace MyController\SSOServer;

use MyController\SSOServer\ValidationResult;
use MyController\SSOServer\Exceptions\SSOServerException;

/**
 * Class SSOServer for Lumen
 *
 * PS: Based on https://github.com/jasny/sso
 *
 * @package MyController\SSOServer
 */
class SSOServer
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
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->options = [
            //通过参数`fail_exception`, 能控制发生错误时的处理方式, 不为空 empty() 时 抛出异常, 否则会设置 HTTP response 并且 exit.
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
     * Validate the broker session id
     *
     * @param string $sid session id
     * @return string  the broker id
     */
    protected function validateBrokerSessionId($sid)
    {
        $matches = null;

        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $_GET['sso_session'], $matches)) {
            return $this->fail("Invalid session id");
        }

        $brokerId = $matches[1];
        $token = $matches[2];

        if ($this->generateSessionId($brokerId, $token) != $sid) {
            return $this->fail("Checksum failed: Client IP address may have changed", 403);
        }

        return $brokerId;
    }

    /**
     * Start the session when a user visits the SSO server
     * @override
     */
    protected function startUserSession()
    {
        // 使用 Lumen的SESSION方案 代替 PHP的原生SESSION方案, 这样才能支持分布式运行
        /*
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
     * Generate session id from session token
     *
     * @param string $brokerId
     * @param string $token
     * @return string
     */
    protected function generateSessionId($brokerId, $token)
    {
        $broker = $this->getBrokerInfo($brokerId);

        if (!isset($broker)) return null;

        return "SSO-{$brokerId}-{$token}-" . hash('sha256', 'session' . $token . $broker['secret']);
    }

    /**
     * Generate session id from session token
     *
     * @param string $brokerId
     * @param string $token
     * @return string
     */
    protected function generateAttachChecksum($brokerId, $token)
    {
        $broker = $this->getBrokerInfo($brokerId);

        if (!isset($broker)) return null;

        return hash('sha256', 'attach' . $token . $broker['secret']);
    }

    /**
     * Detect the type for the HTTP response.
     * Should only be done for an `attach` request.
     */
    protected function detectReturnType()
    {
        if (!empty($_GET['return_url'])) {
            $this->returnType = 'redirect';
        } elseif (!empty($_GET['callback'])) {
            $this->returnType = 'jsonp';
        } elseif (strpos($_SERVER['HTTP_ACCEPT'], 'image/') !== false) {
            $this->returnType = 'image';
        } elseif (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            $this->returnType = 'json';
        }
    }

    /**
     * Attach a user session to a broker session
     *
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
     * Output on a successful attach
     */
    protected function outputAttachSuccess()
    {
        if ($this->returnType === 'image') {
            $this->outputImage();
        }

        if ($this->returnType === 'json') {
            header('Content-type: application/json; charset=UTF-8');
            echo json_encode(['success' => 'attached']);
        }

        if ($this->returnType === 'jsonp') {
            $data = json_encode(['success' => 'attached']);
            echo $_REQUEST['callback'] . "($data, 200);";
        }

        if ($this->returnType === 'redirect') {
            $url = $_REQUEST['return_url'];
            header("Location: $url", true, 307);
            echo "You're being redirected to <a href='{$url}'>$url</a>";
        }
    }

    /**
     * Output a 1x1px transparent image
     */
    protected function outputImage()
    {
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQ'
            . 'MAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZg'
            . 'AAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
    }

    /**
     * Authenticate
     */
    public function login()
    {
        $this->startBrokerSession();

        if (empty($_POST['username'])) $this->fail("No username specified", 400);
        if (empty($_POST['password'])) $this->fail("No password specified", 400);

        $validation = $this->authenticate($_POST['username'], $_POST['password']);

        if ($validation->failed()) {
            return $this->fail($validation->getError(), 400);
        }

        $this->setSessionData('sso_user', $validation->getAccount());
        $this->userInfo();
    }

    /**
     * Log out
     */
    public function logout()
    {
        $this->startBrokerSession();
        $this->setSessionData('sso_user', null);

        header('Content-type: application/json; charset=UTF-8');
        http_response_code(204);
    }

    /**
     * Ouput user information as json.
     */
    public function userInfo()
    {
        $this->startBrokerSession();
        $user = null;

        $account = $this->getSessionData('sso_user');

        if ($account) {
            $user = $this->getUserInfo($account);
            if (!$user) return $this->fail("User not found", 500); // Shouldn't happen
        }

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($user);
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
     * An error occured.
     *
     * @param string $message
     * @param int $http_status
     * @throws SSOServerException
     */
    protected function fail($message, $http_status = 500)
    {
        if (!empty($this->options['fail_exception'])) {
            throw new SSOServerException($message, $http_status);
        }

        if ($http_status === 500) trigger_error($message, E_USER_WARNING);

        if ($this->returnType === 'jsonp') {
            echo $_REQUEST['callback'] . "(" . json_encode(['error' => $message]) . ", $http_status);";
            exit();
        }

        if ($this->returnType === 'redirect') {
            $url = $_REQUEST['return_url'] . '?sso_error=' . $message;
            header("Location: $url", true, 307);
            echo "You're being redirected to <a href='{$url}'>$url</a>";
            exit();
        }

        http_response_code($http_status);
        header('Content-type: application/json; charset=UTF-8');

        echo json_encode(['error' => $message]);
        exit();
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
