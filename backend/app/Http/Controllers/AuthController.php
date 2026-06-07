<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Building;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)
            ->orWhere('username', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => '账号或密码错误',
            ])->withInput();
        }

        if ($user->status === 0) {
            return back()->withErrors([
                'email' => '账号已被禁用',
            ])->withInput();
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login')->with('success', '注册成功，请登录');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function profile()
    {
        $user = auth()->user();
        $accessibleCircles = $user->getAccessibleCircleTypes();
        $building = $user->building;

        return view('auth.profile', compact('user', 'accessibleCircles', 'building'));
    }

    public function showVerificationForm()
    {
        $user = auth()->user();
        $buildings = Building::all();

        if ($user->isVerified()) {
            return redirect()->route('profile')->with('error', '您已完成认证');
        }

        if ($user->isMoved()) {
            return redirect()->route('profile')->with('error', '您已搬离小区，请先重新关联楼栋');
        }

        if ($user->verification_status === 'pending') {
            return redirect()->route('profile')->with('error', '认证申请正在审核中，请耐心等待');
        }

        return view('auth.verify-apply', compact('user', 'buildings'));
    }

    public function applyVerification(Request $request)
    {
        $user = auth()->user();

        if ($user->isMoved()) {
            return back()->with('error', '您已搬离小区，请先重新关联楼栋')->withInput();
        }

        if ($user->isVerified()) {
            return back()->with('error', '您已完成认证')->withInput();
        }

        if ($user->verification_status === 'pending') {
            return back()->with('error', '认证申请正在审核中，请耐心等待')->withInput();
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

        return redirect()->route('profile')->with('success', '认证申请已提交，请等待管理员审核');
    }

    public function verificationList()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, '无权限操作');
        }

        $pendingUsers = User::where('verification_status', 'pending')->with('building')->get();
        $verifiedUsers = User::where('verification_status', 'verified')->with('building')->get();
        $buildings = Building::all();

        return view('auth.verify-list', compact('pendingUsers', 'verifiedUsers', 'buildings'));
    }

    public function reviewVerification(Request $request, User $user)
    {
        $admin = auth()->user();
        if (!$admin->isAdmin()) {
            abort(403, '无权限操作');
        }

        if ($user->isVerified() || $user->isMoved()) {
            return back()->with('error', '该用户状态不支持审核');
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
                return back()->with('error', '认证信息不完整，请提供住户类型和楼栋');
            }

            $user->verify($residentType, $buildingId, $unitNumber);
        } else {
            $user->update([
                'verification_status' => 'rejected',
                'verification_remark' => $validated['remark'] ?? null,
            ]);
        }

        return redirect()->route('verification.list')->with('success', $validated['status'] === 'verified' ? '认证已通过' : '认证已拒绝');
    }

    public function showMoveOutForm()
    {
        $user = auth()->user();

        if (!$user->isVerified()) {
            return redirect()->route('profile')->with('error', '您还未完成认证，无需办理搬离');
        }

        if ($user->isMoved()) {
            return redirect()->route('profile')->with('error', '您已办理过搬离手续');
        }

        return view('auth.move-out');
    }

    public function moveOut(Request $request)
    {
        $user = auth()->user();

        if (!$user->isVerified()) {
            return back()->with('error', '您还未完成认证，无需办理搬离');
        }

        if ($user->isMoved()) {
            return back()->with('error', '您已办理过搬离手续');
        }

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $user->markAsMoved($validated['remark'] ?? null);

        return redirect()->route('profile')->with('success', '已成功办理搬离手续，历史发帖将保留，但您将无法继续参与内部讨论');
    }

    public function cancelVerification()
    {
        $user = auth()->user();

        if (!$user->isMoved()) {
            return back()->with('error', '您尚未搬离，无需取消认证');
        }

        $user->update([
            'verification_status' => 'unverified',
            'building_id' => null,
            'unit_number' => null,
            'resident_type' => null,
            'moved_at' => null,
        ]);

        return redirect()->route('profile')->with('success', '已取消认证，您可以重新提交认证申请');
    }
}
