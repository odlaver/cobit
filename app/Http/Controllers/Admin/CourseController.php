<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::withCount(['materials', 'questions', 'questions as active_questions_count' => fn ($q) => $q->where('questions.status', Question::STATUS_ACTIVE)])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'ilike', '%'.$request->string('search').'%')
                ->orWhere('code', 'ilike', '%'.$request->string('search').'%')))
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->integer('semester')))
            ->latest()->paginate(10)->withQueryString();

        return view('admin.courses.index', compact('courses'));
    }

    public function create() { return view('admin.courses.create'); }

    public function show(Course $course)
    {
        $course->load([
            'materials' => fn ($q) => $q->withCount('questions')->with(['questions' => fn ($q2) => $q2->latest()]),
        ]);

        return view('admin.courses.show', compact('course'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'integer', 'between:1,5'],
            'prodi' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Course::create($data);
        return redirect()->route('admin.courses.index')->with('success', 'Matkul berhasil dibuat.');
    }

    public function edit(Course $course) { return view('admin.courses.edit', compact('course')); }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('courses', 'code')->ignore($course)],
            'name' => ['required', 'string', 'max:255'], 'semester' => ['required', 'integer', 'between:1,5'],
            'prodi' => ['nullable', 'string', 'max:50'], 'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $course->update($data);
        return redirect()->route('admin.courses.index')->with('success', 'Matkul berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        try {
            $course->delete();
        } catch (QueryException) {
            return back()->with('error', 'Matkul ini sudah dipakai di duel, jadi tidak bisa dihapus. Nonaktifkan saja.');
        }
        return back()->with('success', 'Matkul berhasil dihapus.');
    }
}
