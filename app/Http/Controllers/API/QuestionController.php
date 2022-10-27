<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\FileService;
use App\Traits\FileUploadTrait;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class QuestionController extends Controller
{
    use FileUploadTrait;

    /**
     * @param QuestionRequest $request
     * @return Application|ResponseFactory|Response
     */
    public function store(QuestionRequest $request): Response|Application|ResponseFactory
    {
        $question = Question::create($request->only('title', 'body'));
        if ($request->get('files') && count($request->get('files')) > 0) {
            (new FileService())->storeFiles($request->get('files'), $question);
        }
        return response(new QuestionResource($question));
    }
}
