<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Item;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $total = 0;
        $productsInCart = [];
        $productsInSession = $request->session()->get("products");
        if ($productsInSession) {
            $productsInCart = Product::findMany(array_keys($productsInSession));
            $total = Product::sumPricesByQuantities($productsInCart, $productsInSession);
        }
        $viewData = [];
        $viewData["title"] = "Cart - Online Store";
        $viewData["subtitle"] = "Shopping Cart";
        $viewData["total"] = $total;
        $viewData["products"] = $productsInCart;

        if($request->wantsJson()){
            return response()->json(
                $viewData
            , 200);
        }

        return view('cart.index')->with("viewData", $viewData);
    }
    public function add(Request $request, $id)
    {
        $products = $request->session()->get("products");
        $products[$id] = $request->input('quantity');
        $request->session()->put('products', $products);
        
        if($request->wantsJson()){
            return response()->json(
                [
                    'success'=> TRUE
                ]
            , 200);
        }
        return redirect()->route('cart.index');
    }
    public function delete(Request $request)
    {
        $request->session()->forget('products');

        if($request->wantsJson()){
            return response()->json(
                [
                    'success'=> TRUE
                ]
            , 200);
        }

        return back();
    }


    public function purchase(Request $request)
    {
        $productsInSession = $request->session()->get("products");

        if ($productsInSession) {
            /**
             * @disregard P1009 Undefined type
             */
            $userId = Auth::user()->getId();
            $order = new Order();
            $order->setUserId($userId);
            $order->setTotal(0);
            $order->save();
            $total = 0;
            $productsInCart = Product::findMany(array_keys($productsInSession));
            foreach ($productsInCart as $product) {
                $quantity = $productsInSession[$product->getId()];
                $item = new Item();
                $item->setQuantity($quantity);
                $item->setPrice($product->getPrice());
                $item->setProductId($product->getId());
                $item->setOrderId($order->getId());
                $item->save();
                $total = $total + ($product->getPrice() * $quantity);
            }
            $order->setTotal($total);
            $order->save();
            /**
             * @disregard P1009 Undefined type
             */
            $newBalance = Auth::user()->getBalance() - $total;
            /**
             * @disregard P1009 Undefined type
             */
            Auth::user()->setBalance($newBalance);
            /**
             * @disregard P1009 Undefined type
             */
            Auth::user()->save();
            $request->session()->forget('products');
            $viewData = [];
            $viewData["title"] = "Purchase - Online Store";
            $viewData["subtitle"] = "Purchase Status";
            $viewData["order"] = $order;

            if($request->wantsJson()){
                return response()->json(
                    $viewData
                , 200);
            }

            return view('cart.purchase')->with("viewData", $viewData);
        } else {
            if($request->wantsJson()){
                return response()->json(
                    [
                        'success'=> TRUE,
                        'message' => 'There are no products in the session'
                    ]
                , 200);
            }
            
            return redirect()->route('cart.index');
        }
    }
}
