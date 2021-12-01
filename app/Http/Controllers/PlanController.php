<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Cashier\Exceptions\IncompletePayment;

class PlanController extends Controller
{
    public function index() {
        $plans = \App\Plan::all();
        $currentPlan = auth()->user()->subscription('main');
        $priceCurrentPlan = null;
        if ($currentPlan) {
            if ($currentPlan->active()) {
                $plan = \App\Plan::whereSlug($currentPlan->stripe_plan)->first();
                $priceCurrentPlan = $plan->amount;
            }
        }
        return view("plans", compact("plans", "priceCurrentPlan"));
    }

    public function create() {
        return view("plans_form");
    }

    public function store() {
        $this->validate(request(), [
            'plan_name' => 'required|unique:plans,nickname|string|max:200',
            'plan_price' => 'required|numeric',
        ]);
       
        try {
            DB::beginTransaction();
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            $plan = \Stripe\Plan::create([
                'currency' => env("CASHIER_CURRENCY"),
                'interval' => env("CASHIER_INTERVAL"),
                "product" => [
                    "name" => request('plan_name')
                ],
                'nickname' => request('plan_name'),
                'id' => Str::slug(request('plan_name')),
                'amount' => request('plan_price') * 100,
            ]);
            if ($plan) {
                \App\Plan::create([
                    'product' => $plan->product,
                    'nickname' => request('plan_name'),
                    'amount' => request('plan_price'),
                    'slug' => $plan->id
                ]);
            }
            DB::commit();
            session()->flash('message', ['success', __('Plan dado de alta correctamente')]);
            return redirect(route('plans.index'));
        } catch (\Exception $exception) {
            DB::rollBack();
            $plan = \Stripe\Plan::retrieve(Str::slug(request('plan_name')));
            if ($plan) {
                $plan->delete();
            }
            session()->flash('message', ['danger', $exception->getMessage()]);
            return back()->withInput();
        }
    }

    /**
     *
     * Contratar suscripciones y subir de plan
     *
     * @param $hash
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function buy () {
        if ( ! auth()->user()->hasPaymentMethod()) {
            return back()->with('message', ['danger', __('No sabemos cómo has llegado hasta aquí, ¡añade una tarjeta para contratar un plan!')]);
        }
        
        $planId = (int) request("plan");
        
        $this->validate(request(), [
            'plan' => 'required'
        ]);

        //obtenemos el plan que se está intentando contratar
        $plan = \App\Plan::find($planId);
       
        try {
            //nos aseguramos que el plan a contratar es el correcto
            if ($planId === $plan->id) {
                $currentPlan = auth()->user()->subscription('main');
                dd($currentPlan);
                // si no ha finalizado subimos el plan
                if ($currentPlan && ! $currentPlan->ended()) {
                    $currentPlanForCompare = \App\Plan::whereSlug($currentPlan->stripe_plan)->first();
                    //comparamos los precios para saber que el próximo plan tiene un precio superior
                    if ($currentPlanForCompare) {
                        if ($currentPlanForCompare->amount < $plan->amount) {
                            //subimos el plan y generamos la factura al momento!
                            auth()->user()->subscription('main')->swapAndInvoice($plan->slug);
                            return redirect(route("plans.index"))->with('message', ['info', __('Has cambiado al plan ' . $plan->nickname . ' correctamente, recuerda revisar tu correo electrónico por si es necesario confirmar el pago')]);
                        }
                    }
                } else {
                    // si nunca ha contratado una suscripción
                    auth()->user()->newSubscription('main', $plan->slug)->create();
                    return redirect(route("plans.index"))->with('message', ['info', __('Te has suscrito al plan ' . $plan->nickname . ' correctamente, recuerda revisar tu correo electrónico por si es necesario confirmar el pago')]);
                }
            } else {
                return back()->with('message', ['info', __('El plan seleccionado parece no estar disponible')]);
            }
        } catch (IncompletePayment $exception) {
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => back()->with('message', ['success', __('Te has suscrito al plan ' . $plan->nickname . ' correctamente, ya puedes disfrutar de todas las ventajas')])]
            );
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }

        return abort(401);
    }

    public function resumeSubscription () {
        $subscription = request()->user()->subscription(request('plan'));
        if ($subscription->cancelled()) {
            request()->user()->subscription(request('plan'))->resume();
            return back()->with('message', ['success', __("Has reanudado tu suscripción correctamente")]);
        }
        return back()->with('message', ['danger', __("La suscripción no se puede reanudar, consulta con el administrador")]);
    }

    public function cancelSubscription () {
        dd(request('plan'));
        auth()->user()->subscription(request('plan'))->cancel();
        return back()->with('message', ['success', __("La suscripción se ha cancelado correctamente")]);
    }
}
