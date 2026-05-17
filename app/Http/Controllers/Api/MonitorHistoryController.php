<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MonitorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MonitorCheckResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MonitorHistoryController extends Controller
{
    public function __construct(private readonly MonitorRepositoryInterface $monitors,){}

    /**
     * GET /api/monitors/{id}/history
     *
     * Single-action controller — this endpoint does exactly one thing,
     * so __invoke is cleaner than a named method.
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
