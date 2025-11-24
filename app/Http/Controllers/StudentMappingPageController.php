<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentMappingPageController extends Controller
{
    /**
     * Display the student mapping page
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->with(['studentMappings'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student-mapping.index', compact('subjects'));
    }
}
