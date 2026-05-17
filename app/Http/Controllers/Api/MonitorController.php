<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MonitorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Resources\MonitorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MonitorController extends Controller
{
    public function __construct(private readonly MonitorRepositoryInterface $monitors,){}

    /**
     * GET /api/monitors
     */
    public function index(): AnonymousResourceCollection
    {
        return MonitorResource::collection($this->monitors->all());
    }

    /**
     * POST /api/monitors
     */
    public function store(StoreMonitorRequest $request): JsonResponse
    {
        $monitor = $this->monitors->create($request->validated());

        return (new MonitorResource($monitor))->response()->setStatusCode(201);
    }
}
