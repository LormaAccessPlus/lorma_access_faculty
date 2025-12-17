<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} - Grading Sheet</title>
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
            width: 200px;
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
            width: 50px;
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
        <h3>Full Grade Matrix</h3>
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
    
    <table>
        <thead>
            <tr>
                <th class="number-col">#</th>
                <th class="id-col">ID No.</th>
                <th class="student-name">Student Name</th>
                <th class="gender-col">Gender</th>
                <th class="course-col">Course</th>
                <th class="yl-col">YL</th>
                <th class="grade-col">Prelim</th>
                <th class="grade-col">Midterm</th>
                <th class="grade-col">Finals</th>
                <th class="grade-col">Final<br>Grade</th>
                <th class="status-col">Status</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $rowNumber = 1; 
                $debugInfo = [];
                $debugInfo['total_students'] = $subject->studentMappings->count();
                $debugInfo['grading_classes'] = $gradingClasses->count();
                $prelimClass = $gradingClasses->where('term', 'prelim')->first();
                $debugInfo['prelim_components'] = $prelimClass ? $prelimClass->components->count() : 0;
            @endphp
            @forelse($subject->studentMappings as $studentMapping)
                @php
                    $csvData = $studentMapping->csv_data ?? [];
                    
                    // Calculate term grades using ArchiveController logic
                    $termGrades = [];
                    $finalGrade = 0;
                    
                    // Calculate each term grade
                    foreach ($gradingClasses as $gradingClass) {
                        $termGrade = 0;
                        $totalWeight = 0;
                        
                        foreach($gradingClass->components as $component) {
                            if ($component->component_name === 'Exam') {
                                $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                                    ->where('student_mapping_id', $studentMapping->id)
                                    ->where('component_id', $component->id)
                                    ->first();
                                
                                if ($examGrade && $examGrade->exam_score !== null) {
                                    // Use computed_score if available (applies configured formula)
                                    if ($examGrade->computed_score !== null) {
                                        $examComputedScore = $examGrade->computed_score;
                                    } else {
                                        // Fallback to raw percentage calculation
                                        $examMaxScore = $component->exam_max_score ?? 100;
                                        $examComputedScore = ($examGrade->exam_score / $examMaxScore) * 100;
                                    }
                                    $termGrade += $examComputedScore * ($component->weight_percentage / 100);
                                    $totalWeight += $component->weight_percentage;
                                }
                            } else {
                                $grades = \App\Models\StudentGrade::where('student_mapping_id', $studentMapping->id)
                                    ->whereIn('component_item_id', $component->items->pluck('id'))
                                    ->get();
                                
                                $total = 0;
                                $count = 0;
                                foreach ($grades as $grade) {
                                    if ($grade->computed_score !== null) {
                                        $total += $grade->computed_score;
                                        $count++;
                                    }
                                }
                                
                                if ($count > 0) {
                                    $avg = $total / $count;
                                    $termGrade += $avg * ($component->weight_percentage / 100);
                                    $totalWeight += $component->weight_percentage;
                                }
                            }
                        }
                        
                        $termGrades[$gradingClass->term] = $termGrade;
                    }
                    
                    // Calculate final grade using term weights (30% prelim, 30% midterm, 40% finals)
                    $prelimGrade = $termGrades['prelim'] ?? 0;
                    $midtermGrade = $termGrades['midterm'] ?? 0;
                    $finalsGrade = $termGrades['finals'] ?? 0;
                    
                    if ($prelimGrade > 0 || $midtermGrade > 0 || $finalsGrade > 0) {
                        $finalGrade = ($prelimGrade * 0.30) + ($midtermGrade * 0.30) + ($finalsGrade * 0.40);
                    }
                    
                    $status = $finalGrade >= 75 ? 'Passed' : ($finalGrade > 0 ? 'Failed' : '');
                @endphp
                <tr>
                    <td class="number-col">{{ $rowNumber++ }}</td>
                    <td class="id-col">{{ $csvData['id_no'] ?? '' }}</td>
                    <td class="student-name">{{ strtoupper($studentMapping->student_name) }}</td>
                    <td class="gender-col">{{ $csvData['gender'] ?? '' }}</td>
                    <td class="course-col">{{ $csvData['course'] ?? '' }}</td>
                    <td class="yl-col">{{ $csvData['yl'] ?? '' }}</td>
                    <td class="grade-col">{{ $prelimGrade > 0 ? number_format($prelimGrade, 2) : '' }}</td>
                    <td class="grade-col">{{ $midtermGrade > 0 ? number_format($midtermGrade, 2) : '' }}</td>
                    <td class="grade-col">{{ $finalsGrade > 0 ? number_format($finalsGrade, 2) : '' }}</td>
                    <td class="grade-col">{{ $finalGrade > 0 ? number_format($finalGrade, 2) : '' }}</td>
                    <td class="status-col {{ $finalGrade >= 75 ? 'passed' : 'failed' }}">{{ $status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px;">
                        No student records found<br>
                        Debug: {{ json_encode($debugInfo ?? []) }}
                    </td>
                </tr>
            @endforelse
            
            @if(isset($debugInfo))
            <tr style="background-color: #f0f0f0; font-size: 8px;">
                <td colspan="10" style="text-align: center; padding: 5px;">
                    Debug Info: Students={{ $debugInfo['total_students'] }}, Classes={{ $debugInfo['grading_classes'] }}, Components={{ $debugInfo['prelim_components'] }}
                </td>
            </tr>
            @endif
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