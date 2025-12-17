<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Term Grades - {{ $subject->subject_code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: bold;
        }
        .header .subtitle {
            font-size: 11px;
            margin: 2px 0;
        }
        .header .title {
            font-size: 14px;
            margin: 8px 0;
            font-weight: bold;
        }
        .header .semester {
            font-size: 12px;
            margin: 5px 0;
            font-weight: bold;
        }
        .info-section {
            margin: 15px 0;
            font-size: 10px;
        }
        .info-section div {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f3f4f6;
            padding: 8px 4px;
            text-align: center;
            font-size: 7px;
            font-weight: bold;
            border: 1px solid #000000;
            white-space: nowrap;
        }
        td {
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #000000;
            font-size: 7px;
        }
        td.name {
            text-align: left;
            font-weight: 500;
            white-space: nowrap;
        }
        .component-header {
            background-color: #dbeafe;
            font-weight: bold;
        }
        .term-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .term-title {
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            padding: 5px;
            background-color: #e5e7eb;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lorma Colleges</h1>
        <div class="subtitle">Lorma Access+ Grading System</div>
        <div class="title">Term Grades Report</div>
        <div class="semester">
            @php
                $semesterText = $subject->semester == 1 ? 'First' : ($subject->semester == 2 ? 'Second' : 'Summer');
            @endphp
            {{ $semesterText }} Semester {{ $subject->academic_year }}
        </div>
    </div>

    <div class="info-section">
        <div><strong>Subject: {{ $subject->subject_code }} {{ $subject->subject_name }} - {{ $subject->section }}</strong></div>
        <div><strong>Teacher: {{ strtoupper($faculty->name) }}</strong></div>
    </div>

    @foreach($gradingClasses as $gradingClass)
        <div class="term-section">
            <div class="term-title">{{ strtoupper($gradingClass->term) }} TERM</div>
            
            <table>
                <thead>
                    <!-- Component Headers Row -->
                    <tr>
                        <th rowspan="2" style="width: 2%;">#</th>
                        <th rowspan="2" style="width: 6%;">ID No.</th>
                        <th rowspan="2" style="width: 15%;">Student Name</th>
                        <th rowspan="2" style="width: 3%;">Gender</th>
                        
                        @foreach($gradingClass->components as $component)
                            @if($component->component_name === 'Exam')
                                <th colspan="2" class="component-header">{{ $component->component_name }} ({{ $component->weight_percentage }}%)</th>
                            @else
                                <th colspan="{{ $component->items->count() + 1 }}" class="component-header">{{ $component->component_name }} ({{ $component->weight_percentage }}%)</th>
                            @endif
                        @endforeach
                        
                        <th rowspan="2" style="width: 6%;">{{ ucfirst($gradingClass->term) }}<br>Grade</th>
                    </tr>
                    <!-- Item Headers Row -->
                    <tr>
                        @foreach($gradingClass->components as $component)
                            @if($component->component_name === 'Exam')
                                <th style="width: 4%;">Score</th>
                                <th style="width: 4%;">Grade</th>
                            @else
                                @foreach($component->items as $item)
                                    <th style="width: 4%;">{{ $item->item_name }}</th>
                                @endforeach
                                <th style="width: 4%;">Avg</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $index => $student)
                        @php
                            $csvData = $student->csv_data ?? [];
                            $termGradeComponents = [];
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $csvData['id_no'] ?? '' }}</td>
                            <td class="name">{{ $student->formatted_name }}</td>
                            <td>{{ $csvData['gender'] ?? '' }}</td>
                            
                            @foreach($gradingClass->components as $component)
                                @php
                                    $componentTotal = 0;
                                    $componentCount = 0;
                                @endphp
                                
                                @if($component->component_name === 'Exam')
                                    @php
                                        $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                                            ->where('student_mapping_id', $student->id)
                                            ->where('component_id', $component->id)
                                            ->first();
                                        
                                        $examScore = '';
                                        $examComputedScore = null;
                                        
                                        if ($examGrade && $examGrade->exam_score !== null) {
                                            $examScore = $examGrade->exam_score;
                                            if ($examGrade->computed_score !== null) {
                                                $examComputedScore = $examGrade->computed_score;
                                            } else {
                                                $examMaxScore = $component->exam_max_score ?? 100;
                                                $examComputedScore = ($examGrade->exam_score / $examMaxScore) * 100;
                                            }
                                        }
                                    @endphp
                                    <td>{{ $examScore }}</td>
                                    <td>{{ $examComputedScore !== null ? number_format($examComputedScore, 2) : '' }}</td>
                                    @php
                                        if ($examComputedScore !== null) {
                                            $termGradeComponents[] = $examComputedScore * ($component->weight_percentage / 100);
                                        }
                                    @endphp
                                @else
                                    @foreach($component->items as $item)
                                        @php
                                            $grade = $item->grades->where('student_mapping_id', $student->id)->first();
                                        @endphp
                                        <td>{{ $grade && $grade->computed_score !== null ? number_format($grade->computed_score, 2) : '' }}</td>
                                        @php
                                            if ($grade && $grade->computed_score !== null) {
                                                $componentTotal += $grade->computed_score;
                                                $componentCount++;
                                            }
                                        @endphp
                                    @endforeach
                                    
                                    @php
                                        $componentAvg = $componentCount > 0 ? $componentTotal / $componentCount : null;
                                    @endphp
                                    <td>{{ $componentAvg !== null ? number_format($componentAvg, 2) : '' }}</td>
                                    @php
                                        if ($componentAvg !== null) {
                                            $termGradeComponents[] = $componentAvg * ($component->weight_percentage / 100);
                                        }
                                    @endphp
                                @endif
                            @endforeach
                            
                            @php
                                $termGrade = count($termGradeComponents) > 0 ? array_sum($termGradeComponents) : null;
                            @endphp
                            <td><strong>{{ $termGrade !== null ? number_format($termGrade, 2) : '' }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @php
        // Map departments to deans
        $deans = [
            'College of Nursing' => 'Teresita A. Ferrer, MAN',
            'COLLEGE OF PHARMACY' => 'Ellen Mae P. Abiqui, RPh, MSPharm, CPT',
            'COLLEGE OF MEDICAL LABORATORY SCIENCE' => 'Josephine Milan, RMT, MSME',
            'COLLEGE OF RADIOLOGIC TECHNOLOGY' => 'Gryn T. Salagma, RRT',
            'COLLEGE OF PHYSICAL THERAPY' => 'Maverick Kaypee A. Colet, EdD, MASE, PTRP',
            'COLLEGE OF RESPIRATORY THERAPY' => 'Dr. Guilvic Tirso S. Aspiras, MD, FPCP, FPCC',
            'COLLEGE OF PSYCHOLOGY' => 'Dr. Rogelio S. Quiroga Jr., Ph.D.',
            'COLLEGE OF COMPUTER STUDIES & ENGINEERING' => 'Jeoffrey B. Layco, BSICS, MIS',
            'College of Computer Studies & Engineering' => 'Jeoffrey B. Layco, BSICS, MIS',
            'COLLEGE OF BUSINESS' => 'Gloria Anne Hombrebueno, MBA / Elizabeth R. Camara, LPT, MAEd',
            'GENERAL EDUCATION' => 'Elizabeth R. Camara, LPT, MAEd',
            'General Education' => 'Elizabeth R. Camara, LPT, MAEd',
        ];
        
        // Get the department from the first grading class
        $department = $gradingClasses->first()->department ?? '';
        $deanName = $deans[$department] ?? 'Dean Name';
    @endphp

    <div style="margin-top: 40px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; border: none; text-align: center; vertical-align: top; padding: 0;">
                    <div style="font-size: 10px;">
                        <strong>Submitted by:</strong><br><br><br>
                        <strong style="text-decoration: underline;">{{ strtoupper($faculty->name) }}</strong><br>
                        <span style="font-size: 9px;">Instructor</span>
                    </div>
                </td>
                <td style="width: 50%; border: none; text-align: center; vertical-align: top; padding: 0;">
                    <div style="font-size: 10px;">
                        <strong>Noted by:</strong><br><br><br>
                        <strong style="text-decoration: underline;">{{ strtoupper($deanName) }}</strong><br>
                        <span style="font-size: 9px;">Dean</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
