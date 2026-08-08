<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withoutGlobalScopes()->with('tenant')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%')))
            ->when($request->filled('tenant_id'), fn ($query) => $query->where('tenant_id', $request->tenant_id))
            ->latest()->paginate(20)->withQueryString();

        return view('superadmin.users.index', ['users' => $users, 'tenants' => Tenant::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('superadmin.users.form', ['user' => new User, 'tenants' => Tenant::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['is_superadmin'] = $request->boolean('is_superadmin');
        if ($data['is_superadmin']) {
            $data['tenant_id'] = null;
            $data['role'] = null;
        }
        User::withoutGlobalScopes()->create($data);

        return redirect()->route('superadmin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('superadmin.users.form', ['user' => $user, 'tenants' => Tenant::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        $data['is_superadmin'] = $request->boolean('is_superadmin');
        if ($data['is_superadmin']) {
            $data['tenant_id'] = null;
            $data['role'] = null;
        }
        $user->update($data);

        return redirect()->route('superadmin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->is_superadmin && User::withoutGlobalScopes()->where('is_superadmin', true)->count() <= 1) {
            return back()->with('error', 'The final superadmin account cannot be deleted.');
        }
        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'role' => ['nullable', Rule::in(['owner', 'admin', 'member'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_superadmin' => ['nullable', 'boolean'],
        ]);
    }
}
