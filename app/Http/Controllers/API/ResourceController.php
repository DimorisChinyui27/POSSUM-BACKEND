<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Http\Resources\TopicResource;
use App\Models\PaymentMethod;
use App\Models\Topic;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Khsing\World\Models\Country;

class ResourceController extends Controller
{
    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function summarize(Request $request): Response|Application|ResponseFactory
    {
        $result = Http::post(config('app.summarize_url'), [
            'lang' => $request->get('lang'),
            'text' => $request->get('text'),
        ]);
        return response($result->json());
    }

    public function translate(Request $request) {
        $result = Http::post(config('app.translator_url'), [
            'source_language' => $request->get('source_language'),
            'targt_language' => $request->get('targt_language'),
            'text' => $request->get('text'),
        ]);
        return response($result->json());
    }
    /**
     * @return Response|Application|ResponseFactory
     */
    public function getCountries(): Response|Application|ResponseFactory
    {
        return response(CountryResource::collection(Country::all()));
    }

    /**
     * @return Response|Application|ResponseFactory
     */
    public function getTopics(): Response|Application|ResponseFactory
    {
        return response(TopicResource::collection(Topic::all()));
    }

    /**
     * get payment methods
     * @return Response|Application|ResponseFactory
     */
    public function getPaymentMethods(): Response|Application|ResponseFactory
    {
        $paymentMethods = PaymentMethod::whereStatus(true)->get()->transform(function (PaymentMethod $method) {
           return [
               'code' => $method->code,
               'name' => $method->name
           ];
        });
        return response($paymentMethods);
    }
}
