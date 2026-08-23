<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($qq) => $qq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->orderBy('name');

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $unionGroups = UnionGroup::orderBy('name')->get();

        return view('users.create', compact('unionGroups'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        if ($user->role === 'officer' && $request->filled('union_group_ids')) {
            $user->managedUnionGroups()->sync($request->input('union_group_ids'));
        }

        return redirect()->route('users.index')->with('success', 'Đã thêm tài khoản thành công.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $unionGroups = UnionGroup::orderBy('name')->get();
        $assignedGroupIds = $user->managedUnionGroups()->pluck('union_groups.id')->all();

        return view('users.edit', compact('user', 'unionGroups', 'assignedGroupIds'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $user->update($data);

        if ($user->role === 'officer') {
            $user->managedUnionGroups()->sync($request->input('union_group_ids', []));
        } else {
            $user->managedUnionGroups()->sync([]);
        }

        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản thành công.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Đã xóa tài khoản thành công.');
    }
}
