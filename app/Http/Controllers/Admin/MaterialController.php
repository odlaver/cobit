<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $materials = Material::with('course')->withCount('questions')
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'ilike', '%'.$request->string('search').'%'))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->latest()->paginate(10)->withQueryString();
        return view('admin.materials.index', ['materials' => $materials, 'courses' => Course::orderBy('name')->get()]);
    }

    public function create(Request $request)
    {
        return view('admin.materials.create', [
            'courses' => Course::orderBy('name')->get(),
            'selectedCourseId' => $request->integer('course_id'),
        ]);
    }

    public function store(Request $request)
    {
        $material = Material::create($request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]));

        if ($request->filled('redirect_to_course')) {
            return redirect()->route('admin.courses.show', $material->course_id)
                ->with('success', 'Materi berhasil ditambahkan.');
        }

        return redirect()->route('admin.materials.index')->with('success', 'Materi berhasil dibuat.');
    }

    public function edit(Material $material) { return view('admin.materials.edit', ['material' => $material, 'courses' => Course::orderBy('name')->get()]); }

    public function update(Request $request, Material $material)
    {
        $material->update($request->validate(['course_id' => ['required', 'exists:courses,id'], 'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']]));
        return redirect()->route('admin.materials.index')->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Material $material) { $material->delete(); return back()->with('success', 'Materi berhasil dihapus.'); }
}
