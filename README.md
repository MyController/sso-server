# sso-server

Simple SSO Server for Lumen， 基于 [jasny/sso](https://github.com/jasny/sso)

## 安装

安装

  ```shell
  composer require mycontroller/sso-server
  ```

## 配置

> 前提: 
>  1. 确保 Lumen 框架的 Cache 系统 已经正确配置完毕.
>  2. 确保 Lumen 框架的 Session 系统 已经正确配置完毕.
>  


在 `/bootstrap/app.php` 文件中的 `Register Service Providers` 配置段落里添加配置:

  ```shell
  $app->register(\MyController\SSOServer\Providers\SSOServerProvider::class);
  ```

如果需要, 你可以为本插件添加Facade定义, 在 `/bootstrap/app.php` 文件中找到 `$app->withFacades();`，确保 `$app->withFacades();` 被开启, 在它后面:

  ```shell
  $app->withFacades();
  class_alias(\MyController\SSOServer\Facades\SSOServerFacade::class, 'SSOServer');
  ```

将配置文件 `/vendor/mycontroller/sso-server/config/sso-server.php` 复制为 `/config/sso-server.php` , 插件会自发加载 `sso-server` 配置.
  
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
    \MyController\SSOServer\Contracts\UserAuthContract::class,
    \App\MyUserAuth::class
  );
  ```
  
## 使用样例

> 需要配合 `mycontroller/sso-broker` 插件来使用, `mycontroller/sso-broker`(链接地址) 是客户端.

> 你还可以在 `/config/sso-server.php` 里自定义 SSOServer 的服务路由指向 (默认是 '/sso') , 插件会自发执行路由绑定. 

## License

MIT
