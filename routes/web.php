<?php

use Illuminate\Support\Facades\Route;

Route::post(
    'stripe/webhook',
    'StripeWebHookController@handleWebhook'
);

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::group(["middleware" => "auth"], function () {
    Route::get("credit-card", 'BillingController@creditCardForm')
        ->name("billing.credit_card_form");
    Route::post("credit-card", 'BillingController@processCreditCardForm')
        ->name("billing.process_credit_card");

    Route::get("plans", "PlanController@index")->name("plans.index");
    Route::get("plans/create", "PlanController@create")->name("plans.create");
    Route::post("plans/store", "PlanController@store")->name("plans.store");
    Route::post("plans/buy", "PlanController@buy")->name("plans.buy");
    Route::post("plans/cancel", "PlanController@cancelSubscription")->name("plans.cancel");
    Route::post("plans/resume", "PlanController@resumeSubscription")->name("plans.resume");
});
