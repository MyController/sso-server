<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSO-Server 服务配置文件
    |--------------------------------------------------------------------------
    |
    | 与 SSO-Server 对应的客户端 SSO-Broker 连接到 SSO-Server 前, 需要配置三个参数:
    |
    |   SSO-Server-Route-Url
    |   SSO-Broker-Id
    |   SSO-Broker-Secret
    |
    */


    /*
    |--------------------------------------------------------------------------
    | SSO-Server 的服务路由
    |--------------------------------------------------------------------------
    |
    |
    */
    'routeUrl' => '/sso-server',

    /*
    |--------------------------------------------------------------------------
    | SSO-Broker
    |--------------------------------------------------------------------------
    |
    | 这里登记了所有的客户端的 SSO-Broker-Id 和 SSO-Broker-Secret
    |
    */

    'brokers' => [
//        'Alice' => [
//            'secret' => '8iwzik1bwd'
//        ],
//
//        'Greg' => [
//            'secret' => '7pypoox2pc'
//        ],
//
//        'Julias' => [
//            'secret' => 'ceda63kmhp'
//        ],
    ],

];