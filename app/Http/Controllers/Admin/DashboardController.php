<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->withCount(['attempts', 'exams'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('student_number', 'like', $like)
                        ->orWhere('lecturer_number', 'like', $like);
                });
            })
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'lecturer' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.dashboard', [
            'users' => $users,
            'search' => $search,
            'studentCount' => User::where('role', 'student')->count(),
            'lecturerCount' => User::where('role', 'lecturer')->count(),
            'adminCount' => User::where('role', 'admin')->count(),
            'totalCount' => User::count(),
        ]);
    }
}
