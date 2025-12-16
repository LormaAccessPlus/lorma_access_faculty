<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} - {{ ucfirst($gradingClass->term) }} Term Grades</title>
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
            color: #000;
        }
        .header h2 {
            margin: 2px 0;
            font-size: 10px;
            color: #000;
        }
        .header h3 {
            margin: 8px 0 2px 0;
            font-size: 12px;
            font-weight: bold;
            color: #000;
        }
        .header h4 {
            margin: 2px 0 15px 0;
            font-size: 10px;
            color: #000;
        }
        .subject-info {
            margin-bottom: 15px;
            font-size: 9px;
        }
        .subject-info table {
            width: 100%;
            border: none;
        }
        .subject-info td {
            border: none;
            padding: 2px 5px;
            text-align: left;
        }
        table.grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 8px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }
        .grades-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 7px;
        }
        .student-name {
            text-align: left;
            font-weight: normal;
            width: 120px;
            font-size: 8px;
        }
        .number-col {
            width: 30px;
            font-weight: bold;
        }
        .id-col {
            width: 60px;
        }
        .gender-col {
            width: 40px;
        }
        .course-col {
            width: 50px;
        }
        .yl-col {
            width: 30px;
        }
        .grade-col {
            width: 35px;
            font-size: 7px;
        }
        .status-col {
            width: 60px;
        }
        .signature-section {
            margin-top: 40px;
            font-size: 9px;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            padding: 20px 10px 5px 10px;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 20px;
        }
        .signature-title {
            font-weight: bold;
            font-size: 8px;
        }
        .signature-name {
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lorma Colleges</h1>
        <h2>ACCESS School Management System</h2>
        <h3>Grading Sheet</h3>
        <h4>First Semester 2025-2026</h4>
    </div>

    <div class="subject-info">
        <table>
            <tr>
                <td><strong>Subject:</strong> {{ $subject->subject_code }} {{ $subject->subject_name }} - {{ ucfirst($gradingClass->term) }}</td>
                <td style="text-align: right;"><strong>Teacher:</strong> {{ strtoupper($faculty->name ?? 'N/A') }}</td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th rowspan="2" class="number-col">#</th>
                <th rowspan="2" class="id-col">ID No.</th>
                <th rowspan="2" class="student-name">Student Name</th>
                <th rowspan="2" class="gender-col">Gender</th>
                <th rowspan="2" class="course-col">Course</th>
                <th rowspan="2" class="yl-col">YL</th>
                @foreach($gradingClass->components as $component)
                    @if($component->component_type === 'exam')
                        <th colspan="2" class="grade-col">{{ $component->component_name }}<br>({{ $component->weight_percentage }}%)</th>
                    @else
                        <th colspan="{{ $component->items->count() + 1 }}" class="grade-col">{{ $component->component_name }}<br>({{ $component->weight_percentage }}%)</th>
                    @endif
                @endforeach
                <th rowspan="2" class="grade-col">{{ ucfirst($gradingClass->term) }}<br>Grade</th>
                <th rowspan="2" class="status-col">Status</th>
            </tr>
            <tr>
                @foreach($gradingClass->components as $component)
                    @if($component->component_type === 'exam')
                        <th class="grade-col">Raw</th>
                        <th class="grade-col">Computed</th>
                    @else
                        @foreach($component->items as $item)
                            <th class="grade-col">{{ $item->item_name }}</th>
                        @endforeach
                        <th class="grade-col">Avg</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
                @php
                    $termGradeComponents = [];
                    
                    // Calculate term grade
                    foreach($gradingClass->components as $component) {
                        $computedAvg = null;
                        
                        if ($component->component_type === 'exam') {
                            // Handle exam component
                            $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                                ->where('student_mapping_id', $student->id)
                                ->where('component_id', $component->id)
                                ->first();
                            
                            if ($examGrade && $examGrade->computed_score !== null) {
                                $computedAvg = $examGrade->computed_score;
                            }
                        } else {
                            // Handle regular components
                            $componentTotal = 0;
                            $componentCount = 0;
                            
                            foreach($component->items as $item) {
                                $grade = $item->grades->where('student_mapping_id', $student->id)->first();
                                if ($grade && $grade->computed_score !== null) {
                                    $componentTotal += $grade->computed_score;
                                    $componentCount++;
                                }
                            }
                            
                            if ($componentCount > 0) {
                                $computedAvg = $componentTotal / $componentCount;
                            }
                        }
                        
                        if ($computedAvg !== null) {
                            $termGradeComponents[] = $computedAvg * ($component->weight_percentage / 100);
                        }
                    }
                    
                    $termGrade = count($termGradeComponents) > 0 ? array_sum($termGradeComponents) : 0;
                    $status = $termGrade >= 75 ? 'Passed' : ($termGrade > 0 ? 'Failed' : '');
                @endphp
                @php
                    // Extract data from CSV - handle both string and array formats
                    $csvData = null;
                    if ($student->csv_data) {
                        if (is_string($student->csv_data)) {
                            $csvData = json_decode($student->csv_data, true);
                        } else {
                            $csvData = $student->csv_data;
                        }
                    }
                    
                    $studentId = $student->student_id ?? ($csvData['id_no'] ?? '');
                    $gender = $student->gender ?? ($csvData['gender'] ?? 'M');
                    $course = $student->course ?? ($csvData['course'] ?? 'BSIT');
                    $yearLevel = $student->year_level ?? ($csvData['yl'] ?? 'IV');
                @endphp
                <tr>
                    <td class="number-col">{{ $index + 1 }}</td>
                    <td class="id-col">{{ $studentId }}</td>
                    <td class="student-name">{{ strtoupper($student->student_name) }}</td>
                    <td class="gender-col">{{ $gender }}</td>
                    <td class="course-col">{{ $course }}</td>
                    <td class="yl-col">{{ $yearLevel }}</td>
                    
                    @foreach($gradingClass->components as $component)
                        @if($component->component_type === 'exam')
                            @php
                                // Handle exam component
                                $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                                    ->where('student_mapping_id', $student->id)
                                    ->where('component_id', $component->id)
                                    ->first();
                            @endphp
                            
                            <!-- Exam Raw Score -->
                            <td class="grade-col">
                                {{ $examGrade && $examGrade->exam_score !== null ? number_format($examGrade->exam_score, 2) : '' }}
                            </td>
                            
                            <!-- Exam Computed Score -->
                            <td class="grade-col">
                                {{ $examGrade && $examGrade->computed_score !== null ? number_format($examGrade->computed_score, 2) : '' }}
                            </td>
                        @else
                            @php
                                $componentComputedTotal = 0;
                                $componentCount = 0;
                            @endphp
                            
                            <!-- Individual Item Scores -->
                            @foreach($component->items as $item)
                                @php
                                    $grade = $item->grades->where('student_mapping_id', $student->id)->first();
                                    if ($grade && $grade->computed_score !== null) {
                                        $componentComputedTotal += $grade->computed_score;
                                        $componentCount++;
                                    }
                                @endphp
                                <td class="grade-col">
                                    {{ $grade && $grade->score !== null ? number_format($grade->score, 2) : '' }}
                                </td>
                            @endforeach
                            
                            <!-- Component Average -->
                            <td class="grade-col">
                                @php
                                    $componentAvg = $componentCount > 0 ? $componentComputedTotal / $componentCount : null;
                                @endphp
                                {{ $componentAvg !== null ? number_format($componentAvg, 2) : '' }}
                            </td>
                        @endif
                    @endforeach
                    
                    <td class="grade-col">{{ $termGrade > 0 ? number_format($termGrade, 2) : '' }}</td>
                    <td class="status-col">{{ $status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td style="width: 50%;">
                    <div style="text-align: center;">
                        <strong>Submitted by:</strong>
                    </div>
                    <div class="signature-line"></div>
                    <div class="signature-title">{{ strtoupper($faculty->name ?? 'FACULTY NAME') }}</div>
                    <div class="signature-name">Instructor</div>
                </td>
                <td style="width: 50%;">
                    <div style="text-align: center;">
                        <strong>Noted by:</strong>
                    </div>
                    <div class="signature-line"></div>
                    <div class="signature-title">JEFFREY B. LAYCO, BRICS, MS</div>
                    <div class="signature-name">Dean, College of Computer Studies & Engineering</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>