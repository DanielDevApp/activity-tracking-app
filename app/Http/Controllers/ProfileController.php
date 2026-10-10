<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $mustChange = $user->must_change_password;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['required', 'string', 'max:120'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => [$mustChange ? 'required' : 'nullable', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $user->fill(collect($data)->only(['name', 'phone', 'job_title'])->all());

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
            $user->must_change_password = false;
        }

        $user->save();

        if ($mustChange && ! $user->must_change_password) {
            return redirect()->route('dashboard')->with('status', 'Password updated. You are all set.');
        }

        return back()->with('status', 'Profile saved. Future updates will record these details.');
    }
}
