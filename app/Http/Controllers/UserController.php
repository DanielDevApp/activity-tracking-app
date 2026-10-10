<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderByDesc('is_active')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User()]);
    }

    public function store(UserRequest $request)
    {
        // New accounts get a temporary password the person must replace at first sign-in.
        User::create($request->validated() + ['must_change_password' => true]);

        return redirect()->route('users.index')->with('status', 'Team member added.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Admins cannot lock themselves out by demoting or deactivating their own account.
        if ($user->id === $request->user()->id) {
            $data['role'] = $user->role;
            $data['is_active'] = true;
        }

        // A password reset by an admin is temporary too (unless the admin is editing themselves).
        if (isset($data['password']) && $user->id !== $request->user()->id) {
            $data['must_change_password'] = true;
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'Team member updated.');
    }
}
