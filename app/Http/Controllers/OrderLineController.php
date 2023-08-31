<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Notifications\OrderMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OrderLineController extends Controller
{
    public function index()
    {
        $orders = Order::whereDate('created_at', Carbon::today())->get();

        $ordersByRestaurant = $orders->groupBy('restaurant_id');

        return view("cart/index", [
            'orders' => $orders,
            'ordersByRestaurant' => $ordersByRestaurant
        ]);
    }

    public function store(Request $request, Product $product)
    {
        //create order

        $order = Order::create([
            'restaurant_id' => $product->restaurant_id,
            'created_at' => Carbon::today()
        ]);

        $validated = $request->validate(
            [
                'quantity' => 'required|numeric',
            ],

            [
                'quantity.required' => 'Renseignez ici la quantité',
            ]
        );

        // create orderline
        $orderline = OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'user_id' => $request->input('user_id'),
        ]);

        smilify('success', 'Produit ajouté au panier 🛒 !');


        return redirect("/cart");
    }

    public function destroy(Order $order)
    {
        $order->orderlines()->delete();
        $order->delete();

        return redirect()->back();
    }

    public function confirmOrder(Order $order)
    {
        $user = Auth::user();
        
        $groupedOrders = Order::where('restaurant_id', $order->restaurant_id)
            ->where('mail_sent', 0)
            ->get()
            ->groupBy('restaurant_id');

        $user->notify(new OrderMail($groupedOrders));

        $groupedOrders->each(function ($orders) {
            $orders->each(function ($order) {
                $order->update(['mail_sent' => true]);
            });
        });

 
        smilify('success', 'Mail de confirmation envoyé !');

        return redirect()->back();
    }

    public function addPickupTime(Order $order, Request $request)
    {
        $pickupTime = $request->input('pickup_time');

        $order->update([
            'pickup_time' => $pickupTime,
        ]);
        
        return redirect()->back();
    }
}
