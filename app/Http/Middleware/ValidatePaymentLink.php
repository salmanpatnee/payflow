<?php

namespace App\Http\Middleware;

use App\Models\PaymentCollection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaymentLink
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        if (! $token) {
            abort(404);
        }

        $collection = PaymentCollection::where('payment_link_token', $token)->first();

        if (! $collection || ! $collection->isPaymentLinkActive()) {
            abort(404);
        }

        $request->merge(['paymentCollection' => $collection]);

        return $next($request);
    }
}
