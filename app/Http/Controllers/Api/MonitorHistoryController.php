<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MonitorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MonitorCheckResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Monitor History
 *
 * Endpoints for browsing the check history of a monitored URL.
 */
class MonitorHistoryController extends Controller
{
    public function __construct(
        private readonly MonitorRepositoryInterface $monitors,
    ) {}

    /**
     * Get check history
     *
     * Returns a paginated list of all checks run against a monitor,
     * ordered by most recent first. Each check records the HTTP status
     * code, response time, and whether the site was considered up.
     *
     * A check is considered up if the status code is 2xx or 3xx.
     * If the request timed out or failed to connect, status_code will
     * be 0 and response_time_ms will be null.
     *
     * @urlParam id integer required The ID of the monitor. Example: 1
     *
     * @queryParam page integer Page number. Defaults to 1. Example: 1
     * @queryParam per_page integer Number of results per page. Maximum 100. Defaults to 15. Example: 15
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "monitor_id": 1,
     *       "status_code": 200,
     *       "response_time_ms": 245,
     *       "is_up": true,
     *       "checked_at": "2026-05-13T10:05:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "monitor_id": 1,
     *       "status_code": 0,
     *       "response_time_ms": null,
     *       "is_up": false,
     *       "checked_at": "2026-05-13T10:10:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 50
     *   }
     * }
     * @response 404 scenario="Monitor not found" {
     *   "message": "Monitor not found."
     * }
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $monitor = $this->monitors->findById($id);

        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = $monitor->checks()->orderBy('checked_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => MonitorCheckResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
