<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject->subject_code }} - {{ ucfirst($term) }} Grades</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            font-size: 20px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .term-badge {
            display: inline-block;
            background-color: #08695A;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
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
        .grade-cell {
            font-size: 14px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $subject->subject_code }} - {{ $subject->subject_name }}</h1>
        <p>Section: {{ $subject->section }}</p>
        <div class="term-badge">{{ strtoupper($term) }} TERM GRADES</div>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No.</th>
                <th style="width: 250px;">Student Name</th>
                <th>Term Grade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subject->studentMappings as $index => $mapping)
                @php
                    $termGrade = $termGrades->where('student_mapping_id', $mapping->id)->first();
                    $grade = $termGrade ? $termGrade->term_grade : null;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="student-name">{{ $mapping->student_name }}</td>
                    <td class="grade-cell">{{ $grade ? number_format($grade, 2) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total Students</th>
                <th>{{ $subject->studentMappings->count() }}</th>
            </tr>
        </tfoot>
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
                        <span style="font-size: 10px;">Submitted by Adviser</span>
                    </div>
                </td>
                <td style="width: 50%; border: none; text-align: center; vertical-align: bottom;">
                    <div style="margin-bottom: 5px; font-size: 13px;">
                        <strong>{{ $deanName ?? '' }}</strong>
                    </div>
                    <div style="border-top: 1px solid #000; display: inline-block; width: 200px; padding-top: 5px;">
                        <span style="font-size: 10px;">Noted by Dean</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Lorma Colleges - Faculty Grading System</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>
</body>
</html>
