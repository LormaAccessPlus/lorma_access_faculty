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
        <p>Section: {{ $subject->section }} | Computed Grades (Percentage)</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 200px;">Student Name</th>
                <th>Prelim<br><small>(30%)</small></th>
                <th>Midterm<br><small>(30%)</small></th>
                <th>Finals<br><small>(40%)</small></th>
                <th>Final Grade</th>
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
                    
                    // Calculate final grade (30% prelim, 30% midterm, 40% finals)
                    $finalGrade = null;
                    if ($prelimGrade && $midtermGrade && $finalsGrade) {
                        $finalGrade = ($prelimGrade->term_grade * 0.3) + 
                                     ($midtermGrade->term_grade * 0.3) + 
                                     ($finalsGrade->term_grade * 0.4);
                    }
                @endphp
                <tr>
                    <td class="student-name">{{ $mapping->student_name }}</td>
                    <td>{{ $prelimGrade ? number_format($prelimGrade->term_grade, 2) : '-' }}</td>
                    <td>{{ $midtermGrade ? number_format($midtermGrade->term_grade, 2) : '-' }}</td>
                    <td>{{ $finalsGrade ? number_format($finalsGrade->term_grade, 2) : '-' }}</td>
                    <td class="final-grade">{{ $finalGrade ? number_format($finalGrade, 2) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Lorma Colleges - Faculty Grading System</p>
        <p>Grade Computation: Prelim (30%) + Midterm (30%) + Finals (40%)</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
