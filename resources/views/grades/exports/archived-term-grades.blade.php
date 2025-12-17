<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} - {{ ucfirst($term) }} Term Grades</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 15px;
            line-height: 1.2;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 2px 0;
            font-size: 12px;
            font-weight: normal;
        }
        
        .header h3 {
            margin: 2px 0;
            font-size: 11px;
            font-weight: bold;
        }
        
        .subject-info {
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .subject-left {
            text-align: left;
        }
        
        .subject-right {
            text-align: right;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            font-size: 9px;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .student-name {
            text-align: left;
            font-weight: normal;
            width: 180px;
        }
        
        .number-col {
            width: 30px;
        }
        
        .id-col {
            width: 80px;
        }
        
        .gender-col {
            width: 40px;
        }
        
        .course-col {
            width: 60px;
        }
        
        .yl-col {
            width: 30px;
        }
        
        .grade-col {
            width: 45px;
        }
        
        .status-col {
            width: 60px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            text-align: center;
            width: 45%;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 5px;
            font-weight: bold;
        }
        
        .passed {
            color: #000;
        }
        
        .failed {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lorma Colleges</h1>
        <h2>ACCESS School Management System</h2>
        <h3>{{ ucfirst($term) }} Term Grading Sheet</h3>
        <h3>First Semester {{ $subject->academic_year }}</h3>
    </div>
    
    <div class="subject-info">
        <div class="subject-left">
            <strong>Subject:</strong> {{ $subject->subject_code }} {{ $subject->subject_name }} - {{ $subject->section }}
        </div>
        <div class="subject-right">
            <strong>Teacher:</strong> {{ $adviserName ?? 'IAN CEAZAR DADOLE' }}
        </div>
    </div>
    
    @php
        // Get activities for this term
        $lectureActivities = $subject->activities->where('term', $term)->where('type', 'lecture');
        $labActivities = $subject->activities->where('term', $term)->where('type', 'lab');
        
        // Get grading class for this term
        $gradingClass = $subject->gradingClasses->where('term', $term)->first();
        
        // Get all grades for this term with component items loaded
        $termGrades = [];
        if ($gradingClass) {
            // Load component items with their grades for better performance
            $gradingClass->load(['components.items.grades' => function($query) use ($subject) {
                $query->whereIn('student_mapping_id', $subject->studentMappings->pluck('id'));
            }]);
            
            foreach ($subject->studentMappings as $student) {
                $grades = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                    ->where('student_mapping_id', $student->id)
                    ->with('componentItem.activity')
                    ->get();
                $termGrades[$student->id] = $grades;
            }
        }
    @endphp
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" class="number-col">#</th>
                <th rowspan="2" class="id-col">ID No.</th>
                <th rowspan="2" class="student-name">Student Name</th>
                <th rowspan="2" class="gender-col">Gender</th>
                <th rowspan="2" class="course-col">Course</th>
                <th rowspan="2" class="yl-col">YL</th>
                @if($lectureActivities->isNotEmpty())
                    <th colspan="{{ $lectureActivities->count() }}" class="grade-col">Lecture Activities</th>
                @endif
                @if($labActivities->isNotEmpty() && $subject->type === 'lecture_lab')
                    <th colspan="{{ $labActivities->count() }}" class="grade-col">Laboratory Activities</th>
                @endif
                <th rowspan="2" class="grade-col">Class<br>Standing</th>
                <th rowspan="2" class="grade-col">Exam<br>Raw</th>
                <th rowspan="2" class="grade-col">Exam<br>Grade</th>
                <th rowspan="2" class="grade-col">{{ ucfirst($term) }}<br>Grade</th>
                <th rowspan="2" class="status-col">Status</th>
            </tr>
            <tr>
                @foreach($lectureActivities as $activity)
                    <th class="grade-col" title="{{ $activity->name }}">
                        {{ Str::limit($activity->name, 8) }}<br>/{{ $activity->max_score == floor($activity->max_score) ? intval($activity->max_score) : $activity->max_score }}
                    </th>
                @endforeach
                @if($subject->type === 'lecture_lab')
                    @foreach($labActivities as $activity)
                        <th class="grade-col" title="{{ $activity->name }}">
                            {{ Str::limit($activity->name, 8) }}<br>/{{ $activity->max_score == floor($activity->max_score) ? intval($activity->max_score) : $activity->max_score }}
                        </th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @php $rowNumber = 1; @endphp
            @forelse($subject->studentMappings as $studentMapping)
                @php
                    $csvData = $studentMapping->csv_data ?? [];
                    $studentGrades = $termGrades[$studentMapping->id] ?? collect();
                    
                    // Calculate class standing average (activities average)
                    $activityGrades = $studentGrades->whereNotNull('computed_score');
                    $classStanding = $activityGrades->isNotEmpty() ? $activityGrades->avg('computed_score') : null;
                    
                    // Get individual activity scores for display
                    $activityScores = [];
                    $activityComputedScores = [];
                    
                    foreach($lectureActivities as $activity) {
                        $activityGrade = $studentGrades->filter(function($grade) use ($activity) {
                            return $grade->componentItem && 
                                   $grade->componentItem->activity_id == $activity->id;
                        })->first();
                        
                        $activityScores[] = $activityGrade ? $activityGrade->score : null;
                        $activityComputedScores[] = $activityGrade ? $activityGrade->computed_score : null;
                    }
                    
                    foreach($labActivities as $activity) {
                        $activityGrade = $studentGrades->filter(function($grade) use ($activity) {
                            return $grade->componentItem && 
                                   $grade->componentItem->activity_id == $activity->id;
                        })->first();
                        
                        $activityScores[] = $activityGrade ? $activityGrade->score : null;
                        $activityComputedScores[] = $activityGrade ? $activityGrade->computed_score : null;
                    }
                    
                    // Calculate term grade using proper component weights and computed scores
                    $termGradeValue = null;
                    if ($gradingClass && $gradingClass->components->isNotEmpty()) {
                        $termGradeComponents = [];
                        
                        foreach ($gradingClass->components as $component) {
                            if ($component->component_type === 'exam') {
                                // Use computed exam score
                                $examGrade = $studentGrades->where('component_id', $component->id)->whereNotNull('exam_score')->first();
                                if ($examGrade && $examGrade->computed_score !== null) {
                                    $termGradeComponents[] = $examGrade->computed_score * ($component->weight_percentage / 100);
                                }
                            } else {
                                // Use average of computed scores for regular components
                                $componentGrades = $studentGrades->whereIn('component_item_id', $component->items->pluck('id'))
                                    ->whereNotNull('computed_score');
                                
                                if ($componentGrades->isNotEmpty()) {
                                    $componentAvg = $componentGrades->avg('computed_score');
                                    $termGradeComponents[] = $componentAvg * ($component->weight_percentage / 100);
                                }
                            }
                        }
                        
                        if (!empty($termGradeComponents)) {
                            $termGradeValue = array_sum($termGradeComponents);
                        }
                    }
                    
                    // Get exam data
                    $examGrade = $studentGrades->whereNotNull('exam_score')->first();
                    $examScore = $examGrade ? $examGrade->exam_score : null;
                    $examMaxScore = $gradingClass && $gradingClass->components->where('component_type', 'exam')->first() 
                        ? $gradingClass->components->where('component_type', 'exam')->first()->exam_max_score ?? 100 
                        : 100;
                    $examGradeValue = $examGrade && $examGrade->computed_score !== null ? $examGrade->computed_score : null;
                    
                    $status = $termGradeValue >= 75 ? 'Passed' : ($termGradeValue > 0 ? 'Failed' : '');
                @endphp
                <tr>
                    <td class="number-col">{{ $rowNumber++ }}</td>
                    <td class="id-col">{{ $csvData['id_no'] ?? '' }}</td>
                    <td class="student-name">{{ strtoupper($studentMapping->student_name) }}</td>
                    <td class="gender-col">{{ $csvData['gender'] ?? '' }}</td>
                    <td class="course-col">{{ $csvData['course'] ?? '' }}</td>
                    <td class="yl-col">{{ $csvData['yl'] ?? '' }}</td>
                    
                    <!-- Individual Activity Scores -->
                    @php $activityIndex = 0; @endphp
                    @foreach($lectureActivities as $activity)
                        @php
                            $activityGrade = $studentGrades->filter(function($grade) use ($activity) {
                                return $grade->componentItem && 
                                       $grade->componentItem->activity_id == $activity->id;
                            })->first();
                            $score = $activityGrade ? $activityGrade->score : null;
                            $computedScore = $activityGrade ? $activityGrade->computed_score : null;
                        @endphp
                        <td class="grade-col">
                            @if($score !== null)
                                {{ $score }}
                                @if($computedScore !== null && $computedScore != $score)
                                    <br><small>({{ number_format($computedScore, 2) }})</small>
                                @endif
                            @endif
                        </td>
                    @endforeach
                    
                    @if($subject->type === 'lecture_lab')
                        @foreach($labActivities as $activity)
                            @php
                                $activityGrade = $studentGrades->filter(function($grade) use ($activity) {
                                    return $grade->componentItem && 
                                           $grade->componentItem->activity_id == $activity->id;
                                })->first();
                                $score = $activityGrade ? $activityGrade->score : null;
                                $computedScore = $activityGrade ? $activityGrade->computed_score : null;
                            @endphp
                            <td class="grade-col">
                                @if($score !== null)
                                    {{ $score }}
                                    @if($computedScore !== null && $computedScore != $score)
                                        <br><small>({{ number_format($computedScore, 2) }})</small>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    @endif
                    
                    <!-- Class Standing -->
                    <td class="grade-col">{{ $classStanding !== null ? number_format($classStanding, 2) : '' }}</td>
                    
                    <!-- Exam Raw Score -->
                    <td class="grade-col">{{ $examScore !== null ? $examScore . '/' . $examMaxScore : '' }}</td>
                    
                    <!-- Exam Grade -->
                    <td class="grade-col">{{ $examGradeValue !== null ? number_format($examGradeValue, 2) : '' }}</td>
                    
                    <!-- Term Grade -->
                    <td class="grade-col">{{ $termGradeValue !== null ? number_format($termGradeValue, 2) : '' }}</td>
                    
                    <!-- Status -->
                    <td class="status-col {{ $termGradeValue >= 75 ? 'passed' : 'failed' }}">{{ $status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 11 + $lectureActivities->count() + ($subject->type === 'lecture_lab' ? $labActivities->count() : 0) }}" style="text-align: center; padding: 20px;">No student records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="signature-section">
        <div class="signature-box">
            <div>Submitted by:</div>
            <div class="signature-line">{{ $adviserName ?? 'IAN CEAZAR DADOLE' }}</div>
            <div>FACULTY</div>
        </div>
        <div class="signature-box">
            <div>Noted by:</div>
            <div class="signature-line">{{ $deanName ?? 'JEFFREY B. LAYCO, BRICS, MS' }}</div>
            <div>Dean, College of Computer Studies & Engineering</div>
        </div>
    </div>
</body>
</html>