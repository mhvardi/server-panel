<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SetupAdminController extends Controller
{
    public function showForm()
    {
        // Double check to prevent accessing this page if users already exist
        if (User::count() > 0) {
            return redirect()->route('login');
        }

        return view('auth.setup-admin');
    }

    public function store(Request $request)
    {
        if (User::count() > 0) {
            return redirect()->route('login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login')->with('success', 'حساب مدیر با موفقیت ایجاد شد. لطفاً وارد شوید.');
    }
}
