<?php

namespace MyController\SSO\Providers;

use Illuminate\Support\ServiceProvider;
use MyController\SSO\SSOService;
use Validator;

class SSOServiceProvider extends ServiceProvider
{
    /**
     * 指定提供者加载是否延缓。
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * 运行注册后的启动服务。
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration files
        $this->publishes([
            __DIR__ . '/../../config/sso-service.php' => config_path('sso-service.php')
        ], 'config');

//        // Publish migration files
//        $this->publishes([
//            __DIR__ . '/../../migrations/' => database_path('/migrations'),
//        ], 'migrations');

//        // HTTP routing
//        if (strpos($this->app->version(), 'Lumen') !== false) {
//            $this->app->get('sso[/{config}]', '\MyController\SSO\LumenSSOController@getSSO');
//        } else {
//            if ((double)$this->app->version() >= 5.2) {
//                $this->app['router']->get('sso/{config?}', '\MyController\SSO\SSOController@getSSO')->middleware('web');
//            } else {
//                $this->app['router']->get('sso/{config?}', '\MyController\SSO\SSOController@getSSO');
//            }
//        }

//        // Validator extensions
//        $this->app['validator']->extend('sso-xxxxxxxx', function ($attribute, $value, $parameters) {
//            return true;
//        });
//        Validator::extend('zh_mobile', function ($attribute, $value, $parameters) {
//            return preg_match('/^(\+?0?86\-?)?((13\d|14[57]|15[^4,\D]|17[678]|18\d)\d{8}|170[059]\d{7})$/', $value);
//        });

//        view()->composer('view', function () {
//            //
//        });
    }

    /**
     * 注册服务提供者。
     *
     * @return void
     */
    public function register()
    {
        // Merge configs
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sso-service.php', 'sso-service'
        );

        // Bind sso
//        $this->app->bind('sso.service', function ($app) {
//            return new SSOService(
//                $app['Illuminate\Filesystem\Filesystem'],
//                $app['Illuminate\Config\Repository'],
//                $app['Intervention\Image\ImageManager'],
//                $app['Illuminate\Session\Store'],
//                $app['Illuminate\Hashing\BcryptHasher'],
//                $app['Illuminate\Support\Str']
//            );
//        });
        $this->app->singleton('sso.service', function ($app) {
            return new SSOService(
                $app['Illuminate\Filesystem\Filesystem'],
                $app['Illuminate\Config\Repository'],
                $app['Intervention\Image\ImageManager'],
                $app['Illuminate\Session\Store'],
                $app['Illuminate\Hashing\BcryptHasher'],
                $app['Illuminate\Support\Str']
            );
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
        return ['sso.service'];
    }

}
