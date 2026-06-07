<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Building;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
            'message' => '注册成功',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)
            ->orWhere('username', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['账号或密码错误'],
            ]);
        }

        if ($user->status === 0) {
            throw ValidationException::withMessages([
                'email' => ['账号已被禁用'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
            'message' => '登录成功',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => '登出成功',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('building');

        $userData = array_merge($user->toArray(), [
            'building_name' => $user->building?->name,
            'accessible_circles' => $user->getAccessibleCircleTypes(),
            'is_moved' => $user->isMoved(),
            'is_verified' => $user->isVerified(),
        ]);

        unset($userData['id_card'], $userData['verification_documents']);

        return response()->json([
            'data' => [
                'user' => $userData,
            ],
        ]);
    }

    public function applyVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isMoved()) {
            return response()->json([
                'message' => '您已搬离小区，请先重新关联楼栋',
            ], 400);
        }

        if ($user->isVerified()) {
            return response()->json([
                'message' => '您已完成认证',
            ], 400);
        }

        if ($user->verification_status === 'pending') {
            return response()->json([
                'message' => '认证申请正在审核中，请耐心等待',
            ], 400);
        }

        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'unit_number' => ['required', 'string', 'max:50'],
            'resident_type' => ['required', 'in:owner,tenant,committee'],
            'real_name' => ['required', 'string', 'max:50'],
            'id_card' => ['required', 'string', 'max:50'],
            'verification_documents' => ['nullable', 'array'],
        ]);

        $user->update([
            'building_id' => $validated['building_id'],
            'unit_number' => $validated['unit_number'],
            'resident_type' => $validated['resident_type'],
            'real_name' => $validated['real_name'],
            'id_card' => $validated['id_card'],
            'verification_documents' => $validated['verification_documents'] ?? null,
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'message' => '认证申请已提交，请等待审核',
            'data' => [
                'verification_status' => $user->verification_status,
            ],
        ]);
    }

    public function reviewVerification(Request $request, User $user): JsonResponse
    {
        $admin = $request->user();
        if (!$admin->isAdmin()) {
            return response()->json([
                'message' => '无权限操作',
            ], 403);
        }

        if ($user->isVerified() || $user->isMoved()) {
            return response()->json([
                'message' => '该用户状态不支持审核',
            ], 400);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'resident_type' => ['required_if:status,verified', 'in:owner,tenant,committee'],
            'building_id' => ['required_if:status,verified', 'exists:buildings,id'],
            'unit_number' => ['required_if:status,verified', 'string', 'max:50'],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['status'] === 'verified') {
            $residentType = $validated['resident_type'] ?? $user->resident_type;
            $buildingId = $validated['building_id'] ?? $user->building_id;
            $unitNumber = $validated['unit_number'] ?? $user->unit_number;

            if (is_null($residentType) || is_null($buildingId)) {
                return response()->json([
                    'message' => '认证信息不完整，请提供住户类型和楼栋',
                ], 400);
            }

            $user->verify($residentType, $buildingId, $unitNumber);
        } else {
            $user->update([
                'verification_status' => 'rejected',
                'verification_remark' => $validated['remark'] ?? null,
            ]);
        }

        return response()->json([
            'message' => $validated['status'] === 'verified' ? '认证已通过' : '认证已拒绝',
            'data' => [
                'verification_status' => $user->verification_status,
            ],
        ]);
    }

    public function moveOut(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isMoved()) {
            return response()->json([
                'message' => '您已经标记为已搬离',
            ], 400);
        }

        if (!$user->isVerified()) {
            return response()->json([
                'message' => '您尚未完成认证',
            ], 400);
        }

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $user->markAsMoved($validated['remark'] ?? null);

        return response()->json([
            'message' => '已标记为搬离，您将无法继续访问内部讨论，但历史发帖将保留',
            'data' => [
                'moved_at' => $user->moved_at,
                'accessible_circles' => $user->getAccessibleCircleTypes(),
            ],
        ]);
    }

    public function cancelVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->verification_status !== 'pending') {
            return response()->json([
                'message' => '当前状态无法取消认证申请',
            ], 400);
        }

        $user->update([
            'verification_status' => 'unverified',
            'verification_remark' => '用户取消申请',
        ]);

        return response()->json([
            'message' => '认证申请已取消',
        ]);
    }
}
