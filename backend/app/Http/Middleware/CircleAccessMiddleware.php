<?php

namespace App\Http\Middleware;

use App\Models\Topic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CircleAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() && !$request->user()) {
            try {
                $user = Auth::guard('sanctum')->user();
                if ($user) {
                    $request->setUserResolver(function () use ($user) {
                        return $user;
                    });
                }
            } catch (\Exception $e) {
            }
        }

        $user = $request->user();
        $topic = $request->route('topic');

        if ($topic instanceof Topic) {
            if (!$user?->canAccessCircle($topic->circle_type, $topic->building_id)) {
                if ($topic->circle_type !== 'public') {
                    return response()->json([
                        'message' => '无权访问该圈层话题',
                        'circle_type' => $topic->circle_type,
                    ], 403);
                }
            }
        }

        $circleType = $request->input('circle_type');
        if ($circleType && $circleType !== 'public') {
            $buildingId = $request->input('building_id');
            if (!$user?->canAccessCircle($circleType, $buildingId)) {
                return response()->json([
                    'message' => '无权访问该圈层',
                    'circle_type' => $circleType,
                ], 403);
            }
        }

        return $next($request);
    }
}
