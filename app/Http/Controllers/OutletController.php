<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OutletController extends Controller
{
    public function index(Request $request)
    {
        // Only Admin or Super Admin can manage outlets
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengelola master cabang/toko.');
        }

        $query = Outlet::withCount(['users', 'sales', 'transfers']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $outlets = $query->orderBy('code')->paginate(15)->withQueryString();

        return view('master.outlets', compact('outlets'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:outlets,code',
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $outlet = Outlet::create($validated);

        return redirect()->route('master.outlets.index')
            ->with('success', "Cabang [{$outlet->name}] ({$outlet->code}) berhasil ditambahkan!");
    }

    public function update(Request $request, Outlet $outlet)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('outlets', 'code')->ignore($outlet->id)],
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $outlet->update($validated);

        return redirect()->route('master.outlets.index')
            ->with('success', "Data cabang [{$outlet->name}] berhasil diperbarui!");
    }

    public function destroy(Outlet $outlet)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        // Check if outlet has associated transactions
        $userCount = $outlet->users()->count();
        $saleCount = $outlet->sales()->count();
        $transferCount = $outlet->transfers()->count();

        if ($saleCount > 0 || $transferCount > 0) {
            return back()->with('error', "Cabang [{$outlet->name}] tidak dapat dihapus karena telah memiliki data transaksi ({$saleCount} penjualan, {$transferCount} transfer). Silakan nonaktifkan status cabang.");
        }

        if ($userCount > 0) {
            return back()->with('error', "Cabang [{$outlet->name}] masih memiliki {$userCount} pengguna terkait. Pindahkan pengguna ke cabang lain terlebih dahulu.");
        }

        $name = $outlet->name;
        $outlet->delete();

        return redirect()->route('master.outlets.index')
            ->with('success', "Cabang [{$name}] berhasil dihapus!");
    }

    public function toggleStatus(Outlet $outlet)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $outlet->status = $outlet->status === 'active' ? 'inactive' : 'active';
        $outlet->save();

        $statusText = $outlet->status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.outlets.index')
            ->with('success', "Cabang [{$outlet->name}] berhasil {$statusText}!");
    }
}
