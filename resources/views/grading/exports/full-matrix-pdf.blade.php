<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grading Sheet - {{ $subject->subject_code }}</title>
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
            background-color: #ffffff;
            padding: 6px 3px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #000000;
        }
        td {
            padding: 5px 3px;
            text-align: center;
            border: 1px solid #000000;
            font-size: 8px;
        }
        td.name {
            text-align: left;
            font-weight: 500;
        }
        .passed {
            color: #059669;
            font-weight: bold;
        }
        .failed {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lorma Colleges</h1>
        <div class="subtitle">ACCESS School Management System</div>
        <div class="title">Grading Sheet</div>
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

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 8%;">ID No.</th>
                <th style="width: 20%;">Student Name</th>
                <th style="width: 5%;">Gender</th>
                <th style="width: 10%;">Course</th>
                <th style="width: 4%;">YL</th>
                <th style="width: 7%;">Prelim<br>({{ $termWeights['prelim'] }}%)</th>
                <th style="width: 7%;">Midterm<br>({{ $termWeights['midterm'] }}%)</th>
                <th style="width: 7%;">Finals<br>({{ $termWeights['finals'] }}%)</th>
                @if($matrixComponents->count() > 0)
                @foreach($matrixComponents as $component)
                    <th style="width: 7%;">{{ $component->component_name }}<br>({{ $finalRatingFormula[$component->component_name] ?? 0 }}%)</th>
                @endforeach
                @endif
                <th style="width: 7%;">Final Rating</th>
                <th style="width: 8%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
                @php
                    $csvData = $student->csv_data ?? [];
                    $termGradesTotal = 0;
                    $termGradesCount = 0;
                    $finalRating = 0;
                    $termGrades = [];
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $csvData['id_no'] ?? '' }}</td>
                    <td class="name">{{ $student->student_name }}</td>
                    <td>{{ $csvData['gender'] ?? '' }}</td>
                    <td>{{ $csvData['course'] ?? '' }}</td>
                    <td>{{ $csvData['yl'] ?? '' }}</td>
                    
                    @foreach($gradingClasses as $gradingClass)
                        @php
                            $termGrade = 0;
                            foreach($gradingClass->components as $component) {
                                if ($component->component_name === 'Exam') {
                                    $examGrade = \App\Models\StudentGrade::where('grading_class_id', $gradingClass->id)
                                        ->where('student_mapping_id', $student->id)
                                        ->where('component_id', $component->id)
                                        ->first();
                                    if ($examGrade && $examGrade->exam_score !== null) {
                                        $examMaxScore = $component->exam_max_score ?? 100;
                                        $examComputedScore = ($examGrade->exam_score / $examMaxScore) * 100;
                                        $termGrade += $examComputedScore * ($component->weight_percentage / 100);
                                    }
                                } else {
                                    $grades = \App\Models\StudentGrade::where('student_mapping_id', $student->id)
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
                                    }
                                }
                            }
                            $termGradesTotal += $termGrade * ($termWeights[$gradingClass->term] / 100);
                            $termGradesCount++;
                        @endphp
                        <td>{{ $termGrade > 0 ? number_format($termGrade, 2) : '' }}</td>
                    @endforeach
                    
                    @php
                        $finalGrade = $termGradesCount > 0 ? $termGradesTotal : 0;
                        if ($matrixComponents->count() > 0) {
                            $finalRating = $finalGrade * ($finalRatingFormula['final_grade'] / 100);
                        } else {
                            $finalRating = $finalGrade;
                        }
                    @endphp
                    
                    @foreach($matrixComponents as $component)
                        @php
                            $score = $component->scores->where('student_mapping_id', $student->id)->first();
                            if ($score && $score->score !== null) {
                                if ($component->formula) {
                                    try {
                                        $formula = str_replace(['score', 'total'], [$score->score, $component->max_score], $component->formula);
                                        $componentGrade = eval("return {$formula};");
                                    } catch (\Exception $e) {
                                        $componentGrade = ($score->score / $component->max_score) * 100;
                                    }
                                } else {
                                    $componentGrade = ($score->score / $component->max_score) * 100;
                                }
                                if (isset($finalRatingFormula[$component->component_name])) {
                                    $finalRating += $componentGrade * ($finalRatingFormula[$component->component_name] / 100);
                                }
                            }
                        @endphp
                        <td>{{ $score && $score->score !== null ? $score->score : '' }}</td>
                    @endforeach
                    
                    <td class="final-rating">{{ $finalRating > 0 ? round($finalRating) : '' }}</td>
                    <td class="{{ $finalRating >= 75 ? 'passed' : 'failed' }}">
                        {{ $finalRating >= 75 ? 'Passed' : ($finalRating > 0 ? 'Failed' : '') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
