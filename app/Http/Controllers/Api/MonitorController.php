<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MonitorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Resources\MonitorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Monitored URLs
 *
 * Endpoints for registering and listing monitored URLs.
 */
class MonitorController extends Controller
{
    public function __construct(
        private readonly MonitorRepositoryInterface $monitors,
    ) {}

    /**
     * List all monitored urls
     *
     * Returns all registered monitors with their current status,
     * last checked time, and uptime percentage.
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "url": "https://example.com",
     *       "check_interval": 5,
     *       "threshold": 3,
     *       "status": "up",
     *       "last_checked_at": "2026-05-13T10:05:00.000000Z",
     *       "uptime_percentage": 99.5,
     *       "created_at": "2026-05-13T10:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        return MonitorResource::collection($this->monitors->all());
    }

    /**
     * Register a url
     *
     * Add a new URL to monitor. The system will begin checking it
     * on the next scheduler run based on the check_interval.
     * Status will be "pending" until the first check runs.
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "url": "https://example.com",
     *     "check_interval": 5,
     *     "threshold": 3,
     *     "status": "pending",
     *     "last_checked_at": null,
     *     "uptime_percentage": null,
     *     "created_at": "2026-05-13T10:00:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The url field is required.",
     *   "errors": {
     *     "url": ["The url field is required."]
     *   }
     * }
     * @response 422 scenario="Duplicate URL" {
     *   "message": "This URL is already being monitored.",
     *   "errors": {
     *     "url": ["This URL is already being monitored."]
     *   }
     * }
     */
    public function store(StoreMonitorRequest $request): JsonResponse
    {
        $monitor = $this->monitors->create($request->validated());

        return (new MonitorResource($monitor))->response()->setStatusCode(201);
    }
}
