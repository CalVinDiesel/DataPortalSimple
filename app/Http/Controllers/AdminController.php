<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function manageUsers(Request $request)
    {
        $query = User::query();
        if ($request->has('role') && in_array($request->role, ['admin', 'user'])) {
            $query->where('role', $request->role);
        }
        $users = $query->get();
        return view('admin.users', compact('users'));
    }

    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'User account created successfully!');
    }

    public function deleteUser(User $user)
    {
        if ($user->role === 'admin') {
            return back()->withErrors(['error' => 'Cannot delete admin account.']);
        }
        $user->delete();
        return back()->with('success', 'User account deleted successfully!');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:8',
        ]);
        $user->update(['password' => bcrypt($validated['new_password'])]);
        return back()->with('success', 'Password perfectly reset for ' . $user->name);
    }

    public function manageSubmissions()
    {
        $submissions = Submission::with('user')->oldest()->get();
        return view('admin.submissions', compact('submissions'));
    }

    public function updateSubmission(Request $request, Submission $submission)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,rejected',
            'processed_data_path' => 'nullable|string',
            'admin_drive_link' => 'nullable|url',
            'sftp_result_path' => 'nullable|string',
            'terrain_path' => 'nullable|url',
            'building_path' => 'nullable|url',
            'orthophoto_path' => 'nullable|url',
            'rejection_reason' => 'nullable|string',
        ]);

        $submission->update($validated);

        return back()->with('success', 'Submission updated successfully!');
    }
}
