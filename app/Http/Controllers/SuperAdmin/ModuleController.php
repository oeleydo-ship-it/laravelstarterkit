<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Support\ModuleCatalog;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        ModuleCatalog::sync();

        return view('superadmin.modules.index', [
            'modules' => Module::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'enabled_by_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_visible' => 'nullable|boolean',
        ]);

        $module->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'enabled_by_default' => $request->boolean('enabled_by_default'),
            'is_active' => $request->boolean('is_active'),
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return back()->with('success', "{$module->name} controls updated.");
    }
}
