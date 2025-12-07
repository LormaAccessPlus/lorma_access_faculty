<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} - Full Grade Matrix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #08695A;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #08695A;
            font-size: 16px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 4px 3px;
            text-align: center;
            font-size: 8px;
        }
        th {
            background-color: #08695A;
            color: white;
            font-weight: bold;
        }
        .student-name {
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
            font-size: 8px;
        }
        .term-header {
            font-size: 10px;
            font-weight: bold;
        }
        .prelim-header { background-color: #3b82f6; color: white; }
        .midterm-header { background-color: #22c55e; color: white; }
        .finals-header { background-color: #a855f7; color: white; }
        .final-rating-header { background-color: #14b8a6; color: white; }
        
        .prelim-cell { background-color: #eff6ff; }
        .midterm-cell { background-color: #f0fdf4; }
        .finals-cell { background-color: #faf5ff; }
        .final-rating-cell { background-color: #f0fdfa; font-weight: bold; }
        
        .term-grade { font-weight: bold; font-size: 9px; }
        .passed { color: #16a34a; }
        .failed { color: #dc2626; }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            text-align: center;
            vertical-align: bottom;
            padding: 10px 20px;
        }
        .signature-line {
            border-top: 1px solid #000;
            display: inline-block;
            width: 180px;
            padding-top: 5px;
        }
        .formula-box {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            padding: 8px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .formula-box h4 {
            margin: 0 0 5px 0;
            color: #c2410c;
            font-size: 10px;
        }
        .formula-box p {
            margin: 2px 0;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FULL GRADE MATRIX</h1>
        <p style="font-size: 12px; font-weight: bold;">{{ $subject->subject_code }} - {{ $subject->subject_name }}</p>
        <p>Section: {{ $subject->section }} | Academic Year: {{ $subject->academic_year }} | Semester: {{ $subject->semester }}</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <!-- Formula Information -->
    <div class="formula-box">
        <h4>Final Rating Formula</h4>
        <p><strong>FR = (Prelim × {{ $finalRatingConfig['prelim_weight'] }}%) + (Midterm × {{ $finalRatingConfig['midterm_weight'] }}%) + (Finals × {{ $finalRatingConfig['finals_weight'] }}%)</strong></p>
        <p>Term Grade = Class Standing (40%) + Exam Grade (60%) | Passing Grade: ≥ 75</p>
        <p style="font-size: 8px; color: #999;">Total Students: {{ $subject->studentMappings->count() }}</p>
    </div>


    @php
        $terms = ['prelim', 'midterm', 'finals'];
        
        // Calculate final grades
        $finalGrades = [];
        foreach($subject->studentMappings as $studentMapping) {
            $studentTermGrades = $termGrades->get($studentMapping->id, collect());
            $prelim = $studentTermGrades->where('term', 'prelim')->first()?->term_grade;
            $midterm = $studentTermGrades->where('term', 'midterm')->first()?->term_grade;
            $finals = $studentTermGrades->where('term', 'finals')->first()?->term_grade;
            
            if ($prelim !== null && $midterm !== null && $finals !== null) {
                $finalRating = ($prelim * ($finalRatingConfig['prelim_weight'] / 100)) +
                              ($midterm * ($finalRatingConfig['midterm_weight'] / 100)) +
                              ($finals * ($finalRatingConfig['finals_weight'] / 100));
                $finalGrades[$studentMapping->id] = round($finalRating, 2);
            } else {
                $finalGrades[$studentMapping->id] = null;
            }
        }
    @endphp

    <table>
        <thead>
            <!-- Main Term Headers -->
            <tr>
                <th rowspan="2" style="width: 120px; background-color: #374151;">Student Name</th>
                @foreach($terms as $term)
                    @php
                        $termActivities = $allActivities->get($term, collect());
                        $colspan = $termActivities->count() + 3; // activities + CS + Exam + Term Grade
                        if ($termActivities->isEmpty()) $colspan = 4; // No activities + CS + Exam + Term Grade
                    @endphp
                    <th colspan="{{ $colspan }}" class="{{ $term }}-header term-header">
                        {{ strtoupper($term) }}
                    </th>
                @endforeach
                <th rowspan="2" class="final-rating-header" style="width: 60px;">FINAL<br>RATING</th>
                <th rowspan="2" class="final-rating-header" style="width: 50px;">REMARKS</th>
            </tr>
            <!-- Sub Headers -->
            <tr>
                @foreach($terms as $term)
                    @php
                        $termActivities = $allActivities->get($term, collect());
                        $headerClass = $term . '-header';
                    @endphp
                    
                    @forelse($termActivities as $activity)
                        <th class="{{ $headerClass }}" style="font-size: 7px; max-width: 50px;">
                            {{ \Illuminate\Support\Str::limit($activity->name, 8) }}<br>({{ $activity->max_score }})
                        </th>
                    @empty
                        <th class="{{ $headerClass }}" style="font-size: 7px;">-</th>
                    @endforelse
                    
                    <th class="{{ $headerClass }}">CS</th>
                    <th class="{{ $headerClass }}">Exam</th>
                    <th class="{{ $headerClass }}">TG</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($subject->studentMappings as $index => $studentMapping)
                @php
                    $studentTermGrades = $termGrades->get($studentMapping->id, collect());
                    $finalGrade = $finalGrades[$studentMapping->id] ?? null;
                @endphp
                <tr style="{{ $index % 2 === 0 ? '' : 'background-color: #f9fafb;' }}">
                    <td class="student-name">{{ $studentMapping->student_name ?? 'Unknown' }}</td>
                    
                    @foreach($terms as $term)
                        @php
                            $termActivities = $allActivities->get($term, collect());
                            $termGrade = $studentTermGrades->where('term', $term)->first();
                            $cellClass = $term . '-cell';
                        @endphp
                        
                        <!-- Activity Scores -->
                        @forelse($termActivities as $activity)
                            @php
                                $gradeRecord = $gradeMatrix[$studentMapping->id][$activity->id] ?? null;
                                $score = $gradeRecord?->score;
                            @endphp
                            <td class="{{ $cellClass }}">
                                {{ $score !== null ? number_format($score, 0) : '-' }}
                            </td>
                        @empty
                            <td class="{{ $cellClass }}">-</td>
                        @endforelse
                        
                        <!-- Class Standing -->
                        <td class="{{ $cellClass }}">
                            {{ $termGrade && $termGrade->class_standing !== null ? number_format($termGrade->class_standing, 1) : '-' }}
                        </td>
                        
                        <!-- Exam Grade -->
                        <td class="{{ $cellClass }}">
                            {{ $termGrade && $termGrade->exam_grade !== null ? number_format($termGrade->exam_grade, 1) : '-' }}
                        </td>
                        
                        <!-- Term Grade -->
                        <td class="{{ $cellClass }} term-grade">
                            {{ $termGrade && $termGrade->term_grade !== null ? number_format($termGrade->term_grade, 1) : '-' }}
                        </td>
                    @endforeach
                    
                    <!-- Final Rating -->
                    <td class="final-rating-cell" style="font-size: 10px;">
                        {{ $finalGrade !== null ? number_format($finalGrade, 1) : '-' }}
                    </td>
                    
                    <!-- Remarks -->
                    <td class="final-rating-cell {{ $finalGrade !== null && $finalGrade >= 75 ? 'passed' : 'failed' }}">
                        @if($finalGrade !== null)
                            {{ $finalGrade >= 75 ? 'PASSED' : 'FAILED' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="20" style="text-align: center; padding: 20px; color: #666;">
                        No students found for this subject.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Legend -->
    <div style="margin-top: 15px; font-size: 8px; color: #666;">
        <strong>Legend:</strong> CS = Class Standing (Weighted) | Exam = Exam Grade (Weighted) | TG = Term Grade | FR = Final Rating
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td style="width: 50%;">
                    <div style="margin-bottom: 5px; font-size: 11px;">
                        <strong>{{ $adviserName ?? '' }}</strong>
                    </div>
                    <div class="signature-line">
                        <span style="font-size: 8px;">Prepared by: Faculty</span>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div style="margin-bottom: 5px; font-size: 11px;">
                        <strong>{{ $deanName ?? '' }}</strong>
                    </div>
                    <div class="signature-line">
                        <span style="font-size: 8px;">Noted by: Dean</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Lorma Colleges - Faculty Grading System</p>
        <p>This document was automatically generated. Please verify all grades before submission.</p>
    </div>
</body>
</html>
