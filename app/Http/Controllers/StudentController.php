<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\StudentMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        
        // Get current academic year and semester
        $currentAcademicYear = config('app.current_academic_year', '2024-2025');
        $currentSemester = config('app.current_semester', '1');
        
        // Get subjects with student mappings
        $subjects = Subject::where('faculty_id', $faculty->id)
            ->where('academic_year', $currentAcademicYear)
            ->where('semester', $currentSemester)
            ->with(['studentMappings'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('students.index', compact('subjects'));
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'subject_id' => 'required|exists:subjects,id'
        ]);

        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        $subject = Subject::where('id', $request->subject_id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_shift($csvData); // Remove header row

            // Expected columns: name, email (at minimum)
            $nameIndex = $this->findColumnIndex($header, ['name', 'student_name', 'full_name']);
            $emailIndex = $this->findColumnIndex($header, ['email', 'student_email', 'email_address']);

            if ($nameIndex === false) {
                return back()->with('error', 'CSV must contain a name column (name, student_name, or full_name)');
            }

            // Get GCR students for this subject
            $gcrStudents = $this->getGcrStudents($subject);
            
            $imported = 0;
            $matched = 0;

            foreach ($csvData as $row) {
                if (empty($row[$nameIndex])) continue;

                $studentName = trim($row[$nameIndex]);
                $studentEmail = $emailIndex !== false ? trim($row[$emailIndex]) : null;

                // Auto-match with GCR students
                $matchResult = $this->autoMatchStudent($studentName, $studentEmail, $gcrStudents);

                StudentMapping::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'student_name' => $studentName,
                    ],
                    [
                        'student_email' => $studentEmail,
                        'gcr_student_id' => $matchResult['gcr_student_id'],
                        'mapping_confidence' => $matchResult['confidence'],
                        'csv_data' => $row,
                        'auto_matched' => $matchResult['matched'],
                    ]
                );

                $imported++;
                if ($matchResult['matched']) {
                    $matched++;
                }
            }

            return back()->with('success', "Imported {$imported} students. Auto-matched {$matched} with Google Classroom.");

        } catch (\Exception $e) {
            Log::error('CSV Import Error: ' . $e->getMessage());
            return back()->with('error', 'Error importing CSV: ' . $e->getMessage());
        }
    }

    private function findColumnIndex($header, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            $index = array_search(strtolower($name), array_map('strtolower', $header));
            if ($index !== false) {
                return $index;
            }
        }
        return false;
    }

    private function getGcrStudents($subject)
    {
        if (!$subject->gcr_class_id) {
            return [];
        }

        try {
            $faculty = $subject->faculty;
            $accessToken = $faculty->google_access_token;

            if (!$accessToken) {
                return [];
            }

            $response = \Http::withToken($accessToken)
                ->get("https://classroom.googleapis.com/v1/courses/{$subject->gcr_class_id}/students");

            if ($response->successful()) {
                return collect($response->json('students', []))->map(function ($student) {
                    return [
                        'id' => $student['userId'],
                        'name' => $student['profile']['name']['fullName'] ?? '',
                        'email' => $student['profile']['emailAddress'] ?? '',
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching GCR students: ' . $e->getMessage());
        }

        return [];
    }

    private function autoMatchStudent($csvName, $csvEmail, $gcrStudents)
    {
        $bestMatch = null;
        $highestConfidence = 0;

        foreach ($gcrStudents as $gcrStudent) {
            $confidence = 0;

            // Email match (highest priority)
            if ($csvEmail && $gcrStudent['email'] && 
                strtolower($csvEmail) === strtolower($gcrStudent['email'])) {
                $confidence = 100;
            }
            // Name similarity
            else {
                $nameSimilarity = $this->calculateNameSimilarity($csvName, $gcrStudent['name']);
                $confidence = $nameSimilarity;
            }

            if ($confidence > $highestConfidence) {
                $highestConfidence = $confidence;
                $bestMatch = $gcrStudent;
            }
        }

        // Only auto-match if confidence is above threshold (80%)
        if ($highestConfidence >= 80 && $bestMatch) {
            return [
                'matched' => true,
                'gcr_student_id' => $bestMatch['id'],
                'confidence' => $highestConfidence,
            ];
        }

        return [
            'matched' => false,
            'gcr_student_id' => null,
            'confidence' => $highestConfidence,
        ];
    }

    private function calculateNameSimilarity($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        // Exact match
        if ($name1 === $name2) {
            return 100;
        }

        // Remove common suffixes/prefixes
        $name1 = preg_replace('/\b(jr|sr|ii|iii|iv)\b\.?/i', '', $name1);
        $name2 = preg_replace('/\b(jr|sr|ii|iii|iv)\b\.?/i', '', $name2);

        // Split into parts
        $parts1 = preg_split('/\s+/', trim($name1));
        $parts2 = preg_split('/\s+/', trim($name2));

        // Check if all parts of shorter name are in longer name
        $shorter = count($parts1) < count($parts2) ? $parts1 : $parts2;
        $longer = count($parts1) >= count($parts2) ? $parts2 : $parts1;

        $matchCount = 0;
        foreach ($shorter as $part) {
            foreach ($longer as $longPart) {
                similar_text($part, $longPart, $percent);
                if ($percent > 85) {
                    $matchCount++;
                    break;
                }
            }
        }

        return ($matchCount / count($shorter)) * 100;
    }
}
