@extends('layouts.app')

@section('content')
<div class="grid-2" style="grid-template-columns: 1fr 2fr;">
    <div class="card">
        <h2>Create New User</h2>
        <form method="POST" action="{{ url('/admin/users') }}">
            @csrf
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Min 8 characters">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" required style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-hover); color: var(--text-base);">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">User Accounts</h2>
            <div>
                <a href="{{ route('admin.users') }}" style="margin-right: 10px; text-decoration: {{ !request('role') ? 'underline' : 'none' }}; color: var(--text-base);">All</a>
                <a href="{{ route('admin.users', ['role' => 'admin']) }}" style="margin-right: 10px; text-decoration: {{ request('role') === 'admin' ? 'underline' : 'none' }}; color: var(--text-base);">Admins</a>
                <a href="{{ route('admin.users', ['role' => 'user']) }}" style="text-decoration: {{ request('role') === 'user' ? 'underline' : 'none' }}; color: var(--text-base);">Users</a>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ucfirst($user->role) }}</td>
                        <td>{{ $user->created_at->format('Y-m-d') }}</td>
                        <td>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" onsubmit="
                                    const newPwd = prompt('Enter new password for {{ $user->name }} (min 8 chars):');
                                    if (!newPwd || newPwd.length < 8) {
                                        if (newPwd !== null) alert('Password must be at least 8 characters.');
                                        return false;
                                    }
                                    document.getElementById('pwd_{{ $user->id }}').value = newPwd;
                                    return true;
                                ">
                                    @csrf
                                    <input type="hidden" name="new_password" id="pwd_{{ $user->id }}" value="">
                                    <button type="submit" style="background: none; border: none; color: #60a5fa; cursor: pointer;">Reset Password</button>
                                </form>
                                <form action="{{ route('admin.users.delete', $user) }}" method="POST" onsubmit="return confirm('Delete this user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #f87171; cursor: pointer;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
