@extends('layouts.app')

@push("css")
    <style type="text/css">
        section.pricing {
            background: #007bff;
            background: linear-gradient(to right, #0062E6, #33AEFF);
        }

        .pricing .card {
            border: none;
            border-radius: 1rem;
            transition: all 0.2s;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
        }

        .pricing hr {
            margin: 1.5rem 0;
        }

        .pricing .card-title {
            margin: 0.5rem 0;
            font-size: 0.9rem;
            letter-spacing: .1rem;
            font-weight: bold;
        }

        .pricing .card-price {
            font-size: 3rem;
            margin: 0;
        }

        .pricing .card-price .period {
            font-size: 0.8rem;
        }

        .pricing ul li {
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="container py-5">

        @if(! auth()->user()->hasPaymentMethod())
            <div class="m-3 alert alert-danger text-center">
                <span class="fas fa-exclamation-circle"></span> {{ __("Todavía no has vinculado ninguna tarjeta a tu cuenta") }} <a href="{{ route('billing.credit_card_form') }}">{{ __("Házlo ahora") }}</a>
            </div>
        @endif
        <section class="pricing py-5">
            <div class="container">
                <div class="row">
                    @foreach($plans as $plan)
                         <div class="col-lg-4">
                            <div class="card mb-5 mb-lg-0">
                                <form action="{{ route("plans.buy") }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $plan->id }}">
                                    <div class="card-body">
                                        <h5 class="card-title text-muted text-uppercase text-center">{{ __($plan->nickname) }}</h5>
                                        <h6 class="card-price text-center">{{ __(":amount€", ["amount" => $plan->amount]) }}<span class="period">{{ __("/mensual") }}</span></h6>
                                        <hr>
                                        <ul class="fa-ul">
                                            <li><span class="fa-li"><i class="fas fa-check"></i></span>{{ __("Acceso a todo") }}</li>
                                            <li><span class="fa-li"><i class="fas fa-check"></i></span>{{ __("Proyectos ilimitados") }}</li>
                                            @if($plan->slug === 'bronce')
                                                <li class="text-muted"><span class="fa-li"><i class="fas fa-times"></i></span>{{ __("Soporte") }}</li>
                                            @else
                                                <li><span class="fa-li"><i class="fas fa-check"></i></span>{{ __("Soporte") }}</li>
                                            @endif

                                            @if($plan->slug === 'oro')
                                                <li><span class="fa-li"><i class="fas fa-check"></i></span>{{ __("Soporte Premium") }}</li>
                                            @else
                                                <li class="text-muted"><span class="fa-li"><i class="fas fa-times"></i></span>{{ __("Soporte Premium") }}</li>
                                            @endif
                                        </ul>

                                        @if( ! auth()->user()->hasIncompletePayment('main'))
                                            @if(auth()->user()->subscribed('main'))
                                                @if(auth()->user()->subscription('main')->stripe_plan === $plan->slug)
                                                    <button type="button" disabled class="btn btn-block btn-primary text-uppercase">{{ __("Tu plan actual") }}</button>
                                                @else
                                                    @if($priceCurrentPlan < $plan->amount)
                                                        <button type="submit" class="btn btn-block btn-primary text-uppercase">{{ __("Cambiar de plan") }}</button>
                                                    @else
                                                        <button type="button" disabled class="btn btn-block btn-primary text-uppercase">{{ __("No es posible bajar") }}</button>
                                                    @endif
                                                @endif
                                            @else
                                                <button type="submit" class="btn btn-block btn-primary text-uppercase">{{ __("Suscribirme") }}</button>
                                            @endif
                                        @else
                                            @if(auth()->user()->subscription('main')->stripe_plan === $plan->slug)
                                                <a class="btn btn-block btn-info text-uppercase" href="{{ route('cashier.payment', auth()->user()->subscription('main')->latestPayment()->id) }}">
                                                    {{ __("Confirma tu pago aquí") }}
                                                </a>
                                            @else
                                                <button type="button" disabled class="btn btn-block btn-primary text-uppercase">{{ __("Esperando...") }}</button>
                                            @endif
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- tabla suscripción actual! --}}
        @include('table_current_subscription')
    </div>
@endsection