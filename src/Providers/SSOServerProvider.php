<?php

namespace MyController\SSOServer\Providers;

use Illuminate\Support\ServiceProvider;
use MyController\SSOServer\SSOServer;

class SSOServerProvider extends ServiceProvider
{
    /**
     * 指定提供者加载是否延缓。
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * 运行注册后的启动服务。
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/sso-server.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('sso-server.php');
        } else {
            $publishPath = base_path('config/sso-server.php');
        }
        $this->publishes([$configPath => $publishPath], 'config');

//        // HTTP routing
//        $this->app->get(config('sso-server.routeUrl', 'sso-server'), '\MyController\SSOServer\Http\Controllers\ServerController@index');
    }

    /**
     * 注册服务提供者。
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sso-server.php', 'sso-server'
        );

        $this->app->singleton('MyController\SSOServer\Providers\SSOServer', function ($app) {
            return new SSOServer($app);
        });
    }

    /**
     * 获取提供者所提供的服务。
     * PS: defer 属性设置为 true 时会使用本方法
     *
     * @return array
     */
    public function provides()
    {
        return ['MyController\SSOServer\Providers\SSOServer'];
    }

}
