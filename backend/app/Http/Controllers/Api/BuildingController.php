<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Building::where('status', 1)
            ->orderBy('name', 'asc');

        if ($request->has('community_name')) {
            $query->where('community_name', 'like', "%{$request->community_name}%");
        }

        $buildings = $query->get();

        return response()->json([
            'data' => $buildings->map(function ($building) {
                return [
                    'id' => $building->id,
                    'name' => $building->name,
                    'community_name' => $building->community_name,
                    'total_floors' => $building->total_floors,
                    'total_units' => $building->total_units,
                    'resident_count' => $building->resident_count,
                ];
            }),
        ]);
    }

    public function show(Building $building): JsonResponse
    {
        $building->loadCount([
            'users as verified_resident_count' => function ($query) {
                $query->where('verification_status', 'verified')
                    ->whereNull('moved_at');
            },
        ]);

        return response()->json([
            'data' => [
                'id' => $building->id,
                'name' => $building->name,
                'community_name' => $building->community_name,
                'total_floors' => $building->total_floors,
                'total_units' => $building->total_units,
                'verified_resident_count' => $building->verified_resident_count,
                'created_at' => $building->created_at,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:buildings,name'],
            'community_name' => ['nullable', 'string', 'max:100'],
            'total_floors' => ['nullable', 'integer', 'min:1'],
            'total_units' => ['nullable', 'integer', 'min:1'],
        ]);

        $building = Building::create($validated);

        return response()->json([
            'data' => $building,
            'message' => '楼栋创建成功',
        ], 201);
    }

    public function update(Request $request, Building $building): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', 'unique:buildings,name,' . $building->id],
            'community_name' => ['nullable', 'string', 'max:100'],
            'total_floors' => ['nullable', 'integer', 'min:1'],
            'total_units' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:0,1'],
        ]);

        $building->update($validated);

        return response()->json([
            'data' => $building,
            'message' => '楼栋更新成功',
        ]);
    }

    public function destroy(Request $request, Building $building): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        $building->delete();

        return response()->json([
            'message' => '楼栋删除成功',
        ]);
    }
}
