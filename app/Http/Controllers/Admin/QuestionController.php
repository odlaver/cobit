<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Material;
use App\Models\Question;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $questions = Question::with('material.course')
            ->when($request->filled('search'), fn ($q) => $q->where('question_text', 'ilike', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('material_id'), fn ($q) => $q->where('material_id', $request->integer('material_id')))
            ->when($request->filled('course_id'), fn ($q) => $q->whereHas('material', fn ($m) => $m->where('course_id', $request->integer('course_id'))))
            ->latest()->paginate(15)->withQueryString();
        return view('admin.questions.index', [
            'questions' => $questions,
            'materials' => Material::with('course')->orderBy('title')->get(),
            'courses' => Course::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $courseId = $request->integer('course_id');
        $materialId = $request->integer('material_id');

        $courses = Course::with('materials')->orderBy('name')->get();
        $selectedCourse = $courseId ? Course::with('materials')->find($courseId) : null;

        $materialsQuery = Material::with('course')->orderBy('title');
        if ($courseId) {
            $materialsQuery->where('course_id', $courseId);
        }
        $materials = $materialsQuery->get();

        return view('admin.questions.create', [
            'materials' => $materials,
            'courses' => $courses,
            'selectedCourse' => $selectedCourse,
            'selectedCourseId' => $courseId,
            'selectedMaterialId' => $materialId,
        ]);
    }

    public function store(Request $request)
    {
        Question::create($this->validated($request));

        if ($request->filled('redirect_course_id')) {
            return redirect()->route('admin.courses.show', $request->integer('redirect_course_id'))
                ->with('success', 'Soal berhasil ditambahkan.');
        }

        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil dibuat.');
    }

    public function edit(Question $question) { return view('admin.questions.edit', ['question' => $question, 'materials' => Material::with('course')->orderBy('title')->get()]); }

    public function update(Request $request, Question $question) { $question->update($this->validated($request)); return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil diperbarui.'); }

    public function toggleStatus(Question $question)
    {
        $question->update(['status' => $question->status === Question::STATUS_ACTIVE ? Question::STATUS_INACTIVE : Question::STATUS_ACTIVE]);
        return back()->with('success', 'Status soal berhasil diubah.');
    }

    public function approve(Question $question) { $question->update(['status' => Question::STATUS_ACTIVE]); return back()->with('success', 'Soal disetujui.'); }

    public function destroy(Question $question)
    {
        try {
            $question->delete();
        } catch (QueryException) {
            return back()->with('error', 'Soal ini sudah dipakai di duel, jadi tidak bisa dihapus. Nonaktifkan saja.');
        }
        return back()->with('success', 'Soal berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'], 'question_text' => ['required', 'string'],
            'option_a' => ['required', 'string'], 'option_b' => ['required', 'string'], 'option_c' => ['required', 'string'], 'option_d' => ['required', 'string'],
            'correct_answer' => ['required', Rule::in(Question::ANSWERS)], 'status' => ['required', Rule::in(Question::STATUSES)], 'source' => ['nullable', Rule::in(Question::SOURCES)],
        ]);
        $data['source'] = $data['source'] ?? 'manual';
        return $data;
    }
}
