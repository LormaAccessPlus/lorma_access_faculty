<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} - Activities & Exam</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
            padding: 6px;
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
        .term-header {
            background-color: #0A7B6A;
            color: white;
            font-weight: bold;
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
        <p>Section: {{ $subject->section }} | Activities & Exam Scores</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    @foreach(['prelim', 'midterm', 'finals'] as $term)
        @if($activitiesByTerm->has($term))
            <h3 style="color: #08695A; margin-top: 20px;">{{ ucfirst($term) }} Term</h3>
            
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 150px;">Student Name</th>
                        @php
                            $termActivities = $activitiesByTerm[$term];
                            $classActivities = $termActivities->where('type', 'class_standing');
                            $examActivities = $termActivities->where('type', 'exam');
                        @endphp
                        
                        @if($classActivities->count() > 0)
                            <th colspan="{{ $classActivities->count() }}">Class Standing</th>
                        @endif
                        @if($examActivities->count() > 0)
                            <th colspan="{{ $examActivities->count() }}">Exam</th>
                        @endif
                        <th rowspan="2">Term Grade</th>
                    </tr>
                    <tr>
                        @foreach($classActivities as $activity)
                            <th style="font-size: 8px;">{{ $activity->name }}</th>
                        @endforeach
                        @foreach($examActivities as $activity)
                            <th style="font-size: 8px;">{{ $activity->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($subject->studentMappings as $mapping)
                        <tr>
                            <td class="student-name">{{ $mapping->student_name }}</td>
                            
                            @foreach($classActivities as $activity)
                                @php
                                    $grade = $gradeRecords->where('student_mapping_id', $mapping->id)
                                                          ->where('activity_id', $activity->id)
                                                          ->first();
                                @endphp
                                <td>{{ $grade ? number_format($grade->score, 2) : '-' }}</td>
                            @endforeach
                            
                            @foreach($examActivities as $activity)
                                @php
                                    $grade = $gradeRecords->where('student_mapping_id', $mapping->id)
                                                          ->where('activity_id', $activity->id)
                                                          ->first();
                                @endphp
                                <td>{{ $grade ? number_format($grade->score, 2) : '-' }}</td>
                            @endforeach
                            
                            @php
                                $termGrade = $termGrades->where('student_mapping_id', $mapping->id)
                                                       ->where('term', $term)
                                                       ->first();
                            @endphp
                            <td style="font-weight: bold;">{{ $termGrade ? number_format($termGrade->term_grade, 2) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="footer">
        <p>Lorma Colleges - Faculty Grading System</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
