<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Laravel\Ui\Presets\React;

class MyAccountController extends Controller
{
    public function orders(Request $request)
    {
        $viewData = [];
        $viewData["title"] = "My Orders - Online Store";
        $viewData["subtitle"] = "My Orders";
        /**
         * @disregard P1009 Undefined type
         */
        $viewData["orders"] = Order::with(['items.product'])->where('user_id', Auth::user()->getId())->get();
        
        if($request->wantsJson()){
            return response()->json(
                $viewData
            , 200);
        }
        
        return view('myaccount.orders')->with("viewData", $viewData);
    }
}
