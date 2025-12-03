@php
    // Get final rating configuration at the top so it's available everywhere
    $finalRatingConfig = $subject->final_rating_config ?? [
        'subject_type' => 'lecture',
        'prelim_weight' => 30,
        'midterm_weight' => 30,
        'finals_weight' => 40
    ];
    
    // Use the subject's configured type from database
    $subjectType = $finalRatingConfig['subject_type'] ?? 'lecture';
    
    // Check if this is nursing matrix - use the passed $matrixType if available, otherwise fall back to subject's matrix_type
    $currentMatrixType = $matrixType ?? $subject->matrix_type ?? '';
    $isNursing = $currentMatrixType === 'nursing';
    $isLecture = $isNursing && ($subjectType === 'lecture');
    $isLab = $isNursing && !$isLecture;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} - Grades (PP)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #08695A;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #08695A;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #08695A;
            color: white;
            font-weight: bold;
        }
        .student-name {
            text-align: left;
            font-weight: bold;
        }
        .final-grade {
            background-color: #f0f9ff;
            font-weight: bold;
            font-size: 12px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $subject->subject_code }} - {{ $subject->subject_name }}</h1>
        <p>
            Section: {{ $subject->section }}
            @if($isNursing)
                | Type: <strong>{{ $isLecture ? 'LECTURE' : 'LABORATORY' }}</strong>
                | Config: {{ $subjectType }}
            @endif
        </p>
        <p>Computed Grades (Percentage)</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
        <!-- Debug Info -->
        <p style="font-size: 8px; color: #999;">
            Current Matrix: {{ $currentMatrixType }} | 
            Subject Type: {{ $subjectType }} | 
            isNursing: {{ $isNursing ? 'yes' : 'no' }} | 
            isLecture: {{ $isLecture ? 'yes' : 'no' }} | 
            isLab: {{ $isLab ? 'yes' : 'no' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 200px;">Student Name</th>
                <th>Prelim<br><small>({{ $finalRatingConfig['prelim_weight'] }}%)</small></th>
                <th>Midterm<br><small>({{ $finalRatingConfig['midterm_weight'] }}%)</small></th>
                <th>Finals<br><small>({{ $finalRatingConfig['finals_weight'] }}%)</small></th>
                @if($isLecture)
                    <th>Final Grade<br><small>(80%)</small></th>
                    <th>Comp. Exam<br><small>(20%)</small></th>
                @else
                    @if($isNursing)
                        <th>Final Grade<br><small>(100%)</small></th>
                    @endif
                @endif
                <th>FINAL RATING</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subject->studentMappings as $mapping)
                @php
                    $prelimGrade = $termGrades->where('student_mapping_id', $mapping->id)
                                             ->where('term', 'prelim')
                                             ->first();
                    $midtermGrade = $termGrades->where('student_mapping_id', $mapping->id)
                                               ->where('term', 'midterm')
                                               ->first();
                    $finalsGrade = $termGrades->where('student_mapping_id', $mapping->id)
                                              ->where('term', 'finals')
                                              ->first();
                    
                    // Calculate final grade (100%)
                    $finalGrade = null;
                    if ($prelimGrade && $prelimGrade->term_grade !== null && 
                        $midtermGrade && $midtermGrade->term_grade !== null && 
                        $finalsGrade && $finalsGrade->term_grade !== null) {
                        $finalGrade = ($prelimGrade->term_grade * ($finalRatingConfig['prelim_weight'] / 100)) + 
                                     ($midtermGrade->term_grade * ($finalRatingConfig['midterm_weight'] / 100)) + 
                                     ($finalsGrade->term_grade * ($finalRatingConfig['finals_weight'] / 100));
                    }
                    
                    // Calculate final rating based on subject type
                    $finalRating = null;
                    $finalGrade80 = null;
                    $comprehensiveExam20 = null;
                    
                    if ($isLecture) {
                        // Lecture: Final Rating = Final Grade (80%) + Comprehensive Exam (20%)
                        if ($finalGrade !== null) {
                            $finalGrade80 = $finalGrade * 0.80;
                            
                            // Get comprehensive exam score
                            $comprehensiveExamRaw = $subject->comprehensive_exam_scores[$mapping->id] ?? null;
                            if ($comprehensiveExamRaw !== null) {
                                // Transmute: (raw / 100) * 60 + 40
                                $comprehensiveExamTransmuted = (($comprehensiveExamRaw / 100) * 60 + 40);
                                // Get 20%
                                $comprehensiveExam20 = $comprehensiveExamTransmuted * 0.20;
                                
                                $finalRating = $finalGrade80 + $comprehensiveExam20;
                            }
                        }
                    } else {
                        // Lab: Final Rating = Final Grade (100%)
                        $finalRating = $finalGrade;
                    }
                    
                    $remarks = $finalRating ? ($finalRating >= 75 ? 'PASSED' : 'FAILED') : 'NO GRADE';
                @endphp
                <tr>
                    <td class="student-name">{{ $mapping->student_name }}</td>
                    <td>{{ $prelimGrade ? number_format($prelimGrade->term_grade, 2) : '-' }}</td>
                    <td>{{ $midtermGrade ? number_format($midtermGrade->term_grade, 2) : '-' }}</td>
                    <td>{{ $finalsGrade ? number_format($finalsGrade->term_grade, 2) : '-' }}</td>
                    @if($isLecture)
                        <td>{{ $finalGrade80 !== null ? number_format($finalGrade80, 2) : '-' }}</td>
                        <td>{{ $comprehensiveExam20 !== null ? number_format($comprehensiveExam20, 2) : '-' }}</td>
                    @else
                        @if($isNursing)
                            <td>{{ $finalGrade !== null ? number_format($finalGrade, 2) : '-' }}</td>
                        @endif
                    @endif
                    <td class="final-grade">{{ $finalRating !== null ? number_format(round($finalRating)) : '-' }}</td>
                    <td style="color: {{ $finalRating && $finalRating >= 75 ? '#10b981' : '#ef4444' }};">
                        {{ $remarks }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Signature Section -->
    <div style="margin-top: 50px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; border: none; text-align: center; vertical-align: bottom;">
                    <div style="margin-bottom: 5px; font-size: 13px;">
                        <strong>{{ $adviserName ?? '' }}</strong>
                    </div>
                    <div style="border-top: 1px solid #000; display: inline-block; width: 200px; padding-top: 5px;">
                        <span style="font-size: 9px;">Submitted by Adviser</span>
                    </div>
                </td>
                <td style="width: 50%; border: none; text-align: center; vertical-align: bottom;">
                    <div style="margin-bottom: 5px; font-size: 13px;">
                        <strong>{{ $deanName ?? '' }}</strong>
                    </div>
                    <div style="border-top: 1px solid #000; display: inline-block; width: 200px; padding-top: 5px;">
                        <span style="font-size: 9px;">Noted by Dean</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Lorma Colleges - Faculty Grading System</p>
        @if($isNursing)
            @if($isLecture)
                <p>Subject Type: <strong>LECTURE</strong></p>
                <p>Final Grade = Prelim ({{ $finalRatingConfig['prelim_weight'] }}%) + Midterm ({{ $finalRatingConfig['midterm_weight'] }}%) + Finals ({{ $finalRatingConfig['finals_weight'] }}%)</p>
                <p>Final Rating = Final Grade (80%) + Comprehensive Exam (20%)</p>
            @else
                <p>Subject Type: <strong>LABORATORY</strong></p>
                <p>Final Rating = Prelim ({{ $finalRatingConfig['prelim_weight'] }}%) + Midterm ({{ $finalRatingConfig['midterm_weight'] }}%) + Finals ({{ $finalRatingConfig['finals_weight'] }}%)</p>
            @endif
        @else
            <p>Grade Computation: Prelim ({{ $finalRatingConfig['prelim_weight'] }}%) + Midterm ({{ $finalRatingConfig['midterm_weight'] }}%) + Finals ({{ $finalRatingConfig['finals_weight'] }}%)</p>
        @endif
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>
</body>
</html>
