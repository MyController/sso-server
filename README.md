# sso-server

Simple SSO Server for Lumen， 基于 [jasny/sso](https://github.com/jasny/sso)

## 安装

安装

  ```shell
  composer require mycontroller/sso-server
  ```

## 配置

在 `/bootstrap/app.php` 文件中的 `Register Service Providers` 配置段落里添加配置:

  ```shell
  $app->register(MyController\SSOServer\Providers\SSOServerProvider::class);
  ```

在 `/bootstrap/app.php` 文件中找到 `$app->withFacades();`，确保 `$app->withFacades();` 被开启, 在它后面添加Facade定义:

  ```shell
  $app->withFacades();
  class_alias(MyController\SSOServer\Facades\SSOServerFacade::class, 'SSOServer');
  ```

将配置文件 `/vendor/mycontroller/sso-server/config/sso-server.php` 复制为 `/config/sso-server.php` ,
并在 `/bootstrap/app.php` 文件中加载 `sso-server` 配置:

  ```shell
  $app->configure('sso-server');
  ```
  
## 实现 UserAuthContract 接口

您需要自己实现 UserAuthContract 接口, 并将 UserAuthContract的具体实现类 绑定至 UserAuthContract 接口。

实现的样例:
 ```shell
 <?php
 
 namespace App;
 
 use MyController\SSOServer\Contracts\UserAuthContract;
 use MyController\SSOServer\Traits\UserAuthTrait;
 
 class MyUserAuth implements UserAuthContract
 {
     use UserAuthTrait;
 }
 ```

然后在 `/bootstrap/app.php` 文件中的 `Register Container Bindings` 配置段落里添加配置:

  ```shell
  $app->singleton(
    MyController\SSOServer\Contracts\UserAuthContract::class,
    App\MyUserAuth::class
  );
  ```
  
## 使用样例

> 需要配合 `mycontroller/sso-broker` 插件来使用, `mycontroller/sso-broker`(链接地址) 是客户端.

在 `/app/Http/routes.php` 文件里添加路由:

  ```shell
    $app->get('/sso-server', function () use ($app) {
    
        $command = isset($_REQUEST['command']) ? $_REQUEST['command'] : null;
        $result = SSOServer::$command();
    });
    $app->post('/sso-server', function () use ($app) {
    
        $command = isset($_REQUEST['command']) ? $_REQUEST['command'] : null;
        $result = SSOServer::$command();
    });
  ```
  
## License

MIT
