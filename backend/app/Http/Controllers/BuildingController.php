<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index()
    {
        $buildings = Building::withCount('verifiedResidents')->get();

        return view('buildings.index', compact('buildings'));
    }

    public function show(Building $building)
    {
        $building->load('verifiedResidents');

        return view('buildings.show', compact('building'));
    }

    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, '无权限操作');
        }

        return view('buildings.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, '无权限操作');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:buildings,name'],
            'address' => ['required', 'string', 'max:255'],
            'total_units' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $building = Building::create($validated);

        return redirect()->route('buildings.show', $building)->with('success', '楼栋创建成功');
    }

    public function edit(Building $building)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, '无权限操作');
        }

        return view('buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, '无权限操作');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:buildings,name,' . $building->id],
            'address' => ['required', 'string', 'max:255'],
            'total_units' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $building->update($validated);

        return redirect()->route('buildings.show', $building)->with('success', '楼栋信息更新成功');
    }

    public function destroy(Building $building)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, '无权限操作');
        }

        $building->delete();

        return redirect()->route('buildings.index')->with('success', '楼栋已删除');
    }
}
