<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradingSystemController extends Controller
{
    /**
     * Display the grading system page
     */
    public function index(Request $request): View
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->with(['studentMappings', 'activities'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('grading-system.index', compact('subjects'));
    }
}
