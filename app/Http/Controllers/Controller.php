<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /*
     * This function use in all api response
     * @param Response data Array
     * @param Response code Int
     *
     * @return mix json
     */
    public function commonResponse(Array $response = array(), int $http_code = 500) {
        $response['status'] = isset($response['status'])? (int) $response['status']: 0;
        $response['message'] = isset($response['message'])? (string) $response['message']: '';
        $response['errors'] = isset($response['errors'])? (array) $response['errors']: array();
        $response['data'] = isset($response['data'])? $response['data']: (object) array();

        $responseJson = ['status' => $response['status'], 'message' => $response['message'], 'data' => $response['data']];
        if(isset($response['errors'])) $responseJson['errors'] = $response['errors'];
        if(isset($response['current_page'])) $responseJson['current_page'] = $response['current_page'];
        if(isset($response['next_page'])) $responseJson['next_page'] = $response['next_page'];
        if(isset($response['total'])) $responseJson['total'] = $response['total'];
        if(isset($response['redirect'])) $responseJson['redirect'] = $response['redirect'];
        if(isset($response['success'])) $responseJson['success'] = $response['success'];

        return response()->json($responseJson, $http_code);
    }
}