<?php

if (!function_exists('authApi')){
    function authApi()
    {
    return auth()->guard('api');

    }
}
if (!function_exists('res_data')){
    function res_data($data, $message = null, $status = 200)
    {
        $message = $message??__('main.success_query');
        return response([
            'message'=> $message,
            'result'=> !empty($data)? $data:null,
            'statusCode'=> $status,
            'status' => in_array($status,[200,201,202,203])
        ],$status);
    }
}
