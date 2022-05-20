<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function success($message, $data = []) {
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    public function fail($code, $message, $data = []) {
        return response()->json([
            'status'  => false,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ]);
    }
}
