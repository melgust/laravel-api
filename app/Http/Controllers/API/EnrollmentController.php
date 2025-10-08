<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index()
    {
        return response()->json(Enrollment::with(['student', 'course'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        $enrollment = Enrollment::create($data);
        return response()->json($enrollment->load(['student', 'course']), 201);
    }

    public function show($id)
    {
        $enrollment = Enrollment::with(['student', 'course'])->find($id);
        return $enrollment ? response()->json($enrollment) : response()->json(['message' => 'Not Found'], 404);
    }

    public function destroy($id)
    {
        $enrollment = Enrollment::find($id);
        if (!$enrollment) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $enrollment->delete();
        return response()->json(['message' => 'Deleted']);
    }
}