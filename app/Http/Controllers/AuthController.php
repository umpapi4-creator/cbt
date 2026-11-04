<?php
namespace App\Http\Controllers;

use App\Models\{User, ExamAttempt, ExamTabEvent};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect('/home');
        return view('auth.login');
    }

    public function showRegister()
    {
        if (Auth::check()) return redirect('/home');
        return view('auth.register');
    }

    /**
     * Pendaftaran publik hanya untuk mahasiswa.
     * Role sengaja tidak dibaca dari request agar user tidak bisa mendaftar sebagai dosen.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'student_number' => ['required', 'string', 'max:40', 'unique:users,student_number'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'student_number.required' => 'NIM wajib diisi.',
            'student_number.unique' => 'NIM sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $name = trim($data['name']);
        $usernameBase = Str::of($name)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(45, '')->value();
        $usernameBase = $usernameBase !== '' ? $usernameBase : 'student';
        $username = $usernameBase;
        $suffix = 2;
        while (User::where('username', $username)->exists()) {
            $username = Str::limit($usernameBase, 50 - strlen((string) $suffix) - 1, '') . '_' . $suffix++;
        }

        $student = User::create([
            'name' => $name,
            'student_number' => trim($data['student_number']),
            'username' => $username,
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'lecturer_number' => null,
        ]);

        Auth::login($student);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard')
            ->with('success', 'Akun mahasiswa berhasil dibuat. Silakan masukkan kode ujian dari dosen.');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $value = $field === 'email' ? strtolower(trim($data['login'])) : strtolower(trim($data['login']));

        if (Auth::attempt([$field => $value, 'password' => $data['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/home');
        }

        return back()
            ->withErrors(['login' => 'Username/email atau password salah.'])
            ->onlyInput('login');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->role === 'student') {
            DB::transaction(function () use ($user) {
                $attempts = ExamAttempt::where('student_id', $user->id)
                    ->where('status', 'in_progress')
                    ->lockForUpdate()
                    ->get();

                foreach ($attempts as $attempt) {
                    $count = (int) $attempt->logout_count + 1;
                    $attempt->update(['logout_count' => $count]);

                    ExamTabEvent::create([
                        'attempt_id' => $attempt->id,
                        'event_type' => 'logout',
                        'occurred_at' => now(),
                        'count_after' => min($count, 255),
                    ]);
                }
            });
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function home(Request $request)
    {
        return match ($request->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'lecturer' => redirect()->route('lecturer.dashboard'),
            default => redirect()->route('student.dashboard'),
        };
    }
}
