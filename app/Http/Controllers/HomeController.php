<?php

namespace App\Http\Controllers;

use App\Parents;
use App\Student;
use App\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $user = Auth::user();

        $view = [];

        switch ($user->roles()->first()->name) {
            case 'Admin' :
                $parents = Parents::latest()->get();
                $teachers = Teacher::latest()->get();
                $students = Student::latest()->get();
                $view = ['parents', 'teachers', 'students'];
                break;
            case 'Teacher' :

                $teacher = Teacher::with(['user', 'subjects', 'classes', 'students'])->withCount('subjects', 'classes')->findOrFail($user->teacher->id);

                $view = ['teacher'];

                break;
            case 'Parent' :
                $parents = Parents::with(['children'])->withCount('children')->findOrFail($user->parent->id);

                $view = ['parents'];
                break;
            case 'student' :
                $student = Student::with(['user', 'parent', 'class', 'attendances'])->findOrFail($user->student->id);

                $view = ['student'];
                break;
            default :
                dd("NO ROLE ASSIGNED YET!");

        }

        return view('home', compact($view));

    }

    /**
     * PROFILE
     */
    public function profile()
    {
        return view('profile.index');
    }

    public function profileEdit()
    {
        return view('profile.edit');
    }

    public function profileUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id()
        ]);

        if ($request->hasFile('profile_picture')) {
            $profile = Str::slug(auth()->user()->name) . '-' . auth()->id() . '.' . $request->profile_picture->getClientOriginalExtension();
            $request->profile_picture->move(public_path('images/profile'), $profile);
        } else {
            $profile = 'avatar.png';
        }

        $user = auth()->user();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'profile_picture' => $profile
        ]);

        return redirect()->route('profile');
    }

    /**
     * CHANGE PASSWORD
     */
    public function changePasswordForm()
    {
        return view('profile.changepassword');
    }

    public function changePassword(Request $request)
    {
        if (!(Hash::check($request->get('currentpassword'), Auth::user()->password))) {
            return back()->with([
                'msg_currentpassword' => 'Your current password does not matches with the password you provided! Please try again.'
            ]);
        }
        if (strcmp($request->get('currentpassword'), $request->get('newpassword')) == 0) {
            return back()->with([
                'msg_currentpassword' => 'New Password cannot be same as your current password! Please choose a different password.'
            ]);
        }

        $this->validate($request, [
            'currentpassword' => 'required',
            'newpassword' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        $user->password = bcrypt($request->get('newpassword'));
        $user->save();

        Auth::logout();
        return redirect()->route('login');
    }
}
