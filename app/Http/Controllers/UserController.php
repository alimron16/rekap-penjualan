<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        // Only Super Admin and Admin can manage users
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola pengguna.');
        }

        $users = User::orderByRaw("FIELD(role, 'super_admin', 'admin', 'toko')")
            ->orderBy('name')
            ->get();

        return view('settings.users', compact('users'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['super_admin', 'admin', 'toko'])],
            'store_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'permissions' => 'nullable|array',
        ]);

        // Non-super-admins cannot create super_admins
        if ($validated['role'] === 'super_admin' && !Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Hanya Super Admin yang dapat membuat akun Super Admin baru.');
        }

        $permissions = [
            'master' => isset($request->permissions['master']),
            'purchase' => isset($request->permissions['purchase']),
            'pos' => isset($request->permissions['pos']),
            'transfer' => isset($request->permissions['transfer']),
            'accounting' => isset($request->permissions['accounting']),
            'reports' => isset($request->permissions['reports']),
            'settings' => isset($request->permissions['settings']),
            'users' => isset($request->permissions['users']),
        ];

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'store_name' => $validated['store_name'] ?? ($validated['role'] === 'toko' ? 'Toko Cabang' : 'Kantor Pusat'),
            'phone' => $validated['phone'],
            'permissions' => $permissions,
            'is_active' => true,
        ]);

        return redirect()->route('settings.users.index')->with('success', 'Pengguna ' . $validated['name'] . ' berhasil ditambahkan!');
    }

    public function update(Request $request, User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => ['required', Rule::in(['super_admin', 'admin', 'toko'])],
            'store_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'permissions' => 'nullable|array',
        ]);

        // Protection: Non-super-admin cannot change super_admin role
        if ($user->isSuperAdmin() && !Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Hanya Super Admin yang dapat mengedit akun Super Admin.');
        }

        $permissions = [
            'master' => isset($request->permissions['master']),
            'purchase' => isset($request->permissions['purchase']),
            'pos' => isset($request->permissions['pos']),
            'transfer' => isset($request->permissions['transfer']),
            'accounting' => isset($request->permissions['accounting']),
            'reports' => isset($request->permissions['reports']),
            'settings' => isset($request->permissions['settings']),
            'users' => isset($request->permissions['users']),
        ];

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'store_name' => $validated['store_name'],
            'phone' => $validated['phone'],
            'permissions' => $permissions,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('settings.users.index')->with('success', 'Data akun ' . $user->name . ' berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Hanya Super Admin yang dapat menghapus akun pengguna.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('settings.users.index')->with('success', 'Akun ' . $name . ' berhasil dihapus.');
    }

    public function toggleStatus(User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('settings.users.index')->with('success', "Status akun {$user->name} berhasil {$statusText}.");
    }
}
