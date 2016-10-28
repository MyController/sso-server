<?php

namespace MyController\SSOServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use MyController\SSOServer\SSOServer;

class ServerController extends BaseController
{
    public function index(Request $request)
    {
        $SSOServerInstance = SSOServer::getInstance();
        $command = $request->get('command');

        if (!$command || !method_exists($SSOServerInstance, $command)) {
            return response()->json(['error' => 'Unknown command'])->setStatusCode(404);
        }

        $result = $SSOServerInstance->$command();
        return $result;
    }
}
