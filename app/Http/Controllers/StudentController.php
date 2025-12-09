<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\StudentMapping;
use App\Services\GoogleClassroomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    protected $classroomService;

    public function __construct(GoogleClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }

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

    public function fetchFromGcr(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id'
        ]);

        $faculty = $request->attributes->get('faculty') ?? auth('faculty')->user();
        $subject = Subject::where('id', $request->subject_id)
            ->where('faculty_id', $faculty->id)
            ->firstOrFail();

        if (!$subject->gcr_class_id) {
            return back()->with('error', 'This subject is not connected to Google Classroom.');
        }

        try {
            // Get GCR students
            $gcrStudents = $this->getGcrStudents($subject);
            
            if (empty($gcrStudents)) {
                return back()->with('error', 'No students found in Google Classroom. Please ensure you are logged in with Google and the classroom has students enrolled.');
            }

            $imported = 0;
            $updated = 0;

            foreach ($gcrStudents as $gcrStudent) {
                $existing = StudentMapping::where('subject_id', $subject->id)
                    ->where('gcr_student_id', $gcrStudent['id'])
                    ->first();

                if ($existing) {
                    // Update existing mapping
                    $existing->update([
                        'student_name' => $gcrStudent['name'],
                        'student_email' => $gcrStudent['email'],
                        'auto_matched' => true,
                        'mapping_confidence' => 100,
                    ]);
                    $updated++;
                } else {
                    // Create new mapping
                    StudentMapping::create([
                        'subject_id' => $subject->id,
                        'student_name' => $gcrStudent['name'],
                        'student_email' => $gcrStudent['email'],
                        'gcr_student_id' => $gcrStudent['id'],
                        'auto_matched' => true,
                        'mapping_confidence' => 100,
                    ]);
                    $imported++;
                }
            }

            $message = "Fetched from Google Classroom: {$imported} new students imported";
            if ($updated > 0) {
                $message .= ", {$updated} students updated";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('GCR Fetch Error: ' . $e->getMessage());
            return back()->with('error', 'Error fetching from Google Classroom: ' . $e->getMessage());
        }
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
            
            // Find the header row (the one that contains #, ID No., Full Name, etc.)
            $header = null;
            $headerRowIndex = 0;
            
            foreach ($csvData as $index => $row) {
                // Look for the row that has '#' in first column and 'ID No.' or 'Full Name' in subsequent columns
                if (!empty($row[0]) && trim($row[0]) === '#') {
                    $header = $row;
                    $headerRowIndex = $index;
                    break;
                }
            }
            
            if (!$header) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not find header row in CSV. Expected a row starting with "#"'
                ], 400);
            }
            
            // Remove all rows up to and including the header
            $csvData = array_slice($csvData, $headerRowIndex + 1);
            
            // Expected columns: #, ID No., Full Name, Gender, Course, YL
            $numberIndex = $this->findColumnIndex($header, ['#', 'no', 'number']);
            $idIndex = $this->findColumnIndex($header, ['id no.', 'id no', 'id_no', 'student_id', 'id number']);
            $nameIndex = $this->findColumnIndex($header, ['full name', 'name', 'student_name', 'fullname']);
            $genderIndex = $this->findColumnIndex($header, ['gender', 'sex']);
            $courseIndex = $this->findColumnIndex($header, ['course', 'program']);
            $ylIndex = $this->findColumnIndex($header, ['yl', 'year level', 'year', 'yearlevel']);

            if ($nameIndex === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV must contain a name column (Full Name, name, or student_name). Found columns: ' . implode(', ', $header)
                ], 400);
            }

            // Get GCR students for this subject
            $gcrStudents = $this->getGcrStudents($subject);
            
            $imported = 0;
            $matched = 0;

            $studentsToImport = [];
            
            foreach ($csvData as $row) {
                if (empty($row[$nameIndex])) continue;

                $studentName = trim($row[$nameIndex]);
                
                // Convert name to "LASTNAME, First Name" format
                $formattedName = $this->formatNameToLastnameFirst($studentName);
                
                // Build CSV data object with all available fields
                $csvDataObject = [
                    'number' => $numberIndex !== false ? trim($row[$numberIndex]) : null,
                    'id_no' => $idIndex !== false ? trim($row[$idIndex]) : null,
                    'full_name' => $studentName,
                    'gender' => $genderIndex !== false ? trim($row[$genderIndex]) : null,
                    'course' => $courseIndex !== false ? trim($row[$courseIndex]) : null,
                    'yl' => $ylIndex !== false ? trim($row[$ylIndex]) : null,
                ];

                // Auto-match with GCR students (using original name)
                $matchResult = $this->autoMatchStudent($studentName, null, $gcrStudents);

                // Auto-match with school database
                $schoolStudentId = $this->findSchoolStudent($studentName, null);

                $studentsToImport[] = [
                    'formatted_name' => $formattedName,
                    'original_name' => $studentName,
                    'csv_data' => $csvDataObject,
                    'match_result' => $matchResult,
                    'school_student_id' => $schoolStudentId,
                ];
            }
            
            // Sort students by formatted name (A-Z)
            usort($studentsToImport, function($a, $b) {
                return strcmp($a['formatted_name'], $b['formatted_name']);
            });
            
            // Import sorted students
            $imported = 0;
            $matched = 0;
            
            foreach ($studentsToImport as $student) {
                StudentMapping::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'student_name' => $student['formatted_name'],
                    ],
                    [
                        'student_email' => null,
                        'student_id' => $student['school_student_id'],
                        'gcr_student_id' => $student['match_result']['gcr_student_id'],
                        'mapping_confidence' => $student['match_result']['confidence'],
                        'csv_data' => $student['csv_data'], // Let the model cast handle JSON encoding
                        'auto_matched' => $student['match_result']['matched'],
                    ]
                );

                $imported++;
                if ($student['match_result']['matched']) {
                    $matched++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$imported} students. Auto-matched {$matched} with Google Classroom."
            ]);

        } catch (\Exception $e) {
            Log::error('CSV Import Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error importing CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    private function findColumnIndex($header, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            // Normalize both header and search term: lowercase, trim, remove extra spaces and dots
            $normalizedName = strtolower(trim(str_replace('.', '', $name)));
            
            foreach ($header as $index => $headerCol) {
                $normalizedHeader = strtolower(trim(str_replace('.', '', $headerCol)));
                
                if ($normalizedHeader === $normalizedName) {
                    return $index;
                }
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

            // Authenticate with Google Classroom
            if (!$this->classroomService->authenticateWithFaculty($faculty)) {
                Log::error('Failed to authenticate with Google Classroom for faculty: ' . $faculty->id);
                return [];
            }

            // Fetch students from Google Classroom
            $students = $this->classroomService->getStudents($subject->gcr_class_id);

            return collect($students)->map(function ($student) {
                return [
                    'id' => $student['userId'] ?? '',
                    'name' => $student['profile']['name']['fullName'] ?? $student['fullName'] ?? $student['name'] ?? '',
                    'email' => $student['profile']['emailAddress'] ?? $student['emailAddress'] ?? $student['email'] ?? '',
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error fetching GCR students: ' . $e->getMessage());
        }

        return [];
    }

    private function autoMatchStudent($csvName, $csvEmail, $gcrStudents)
    {
        // If no GCR students, cannot match
        if (empty($gcrStudents)) {
            Log::info("No GCR students available for matching: {$csvName}");
            return [
                'matched' => false,
                'gcr_student_id' => null,
                'confidence' => 0,
            ];
        }

        $bestMatch = null;
        $highestConfidence = 0;

        foreach ($gcrStudents as $gcrStudent) {
            $confidence = 0;

            // Email match (highest priority)
            if ($csvEmail && $gcrStudent['email'] && 
                strtolower($csvEmail) === strtolower($gcrStudent['email'])) {
                $confidence = 100;
                Log::info("Email match found: {$csvName} ({$csvEmail}) -> {$gcrStudent['name']} ({$gcrStudent['email']})");
            }
            // Name similarity
            else {
                $nameSimilarity = $this->calculateNameSimilarity($csvName, $gcrStudent['name']);
                $confidence = $nameSimilarity;
                
                if ($nameSimilarity > 50) {
                    Log::info("Name similarity: {$csvName} vs {$gcrStudent['name']} = {$nameSimilarity}%");
                }
            }

            if ($confidence > $highestConfidence) {
                $highestConfidence = $confidence;
                $bestMatch = $gcrStudent;
            }
        }

        // Only auto-match if confidence is above threshold (90% for stricter matching)
        if ($highestConfidence >= 90 && $bestMatch) {
            Log::info("Auto-matched: {$csvName} -> {$bestMatch['name']} (confidence: {$highestConfidence}%)");
            return [
                'matched' => true,
                'gcr_student_id' => $bestMatch['id'],
                'confidence' => $highestConfidence,
            ];
        }

        Log::info("No match found for: {$csvName} (best confidence: {$highestConfidence}%)");
        return [
            'matched' => false,
            'gcr_student_id' => null,
            'confidence' => $highestConfidence,
        ];
    }

    private function findSchoolStudent($name, $email)
    {
        try {
            // Try to find student in school database by email first
            if ($email) {
                $student = \DB::connection('school_db')->table('studentdata')
                    ->where('Email', $email)
                    ->first();
                if ($student) {
                    return $student->StudID;
                }
            }

            // Try to find by name
            $student = \DB::connection('school_db')->table('studentdata')
                ->whereRaw('LOWER(CONCAT(FirstName, " ", LastName)) = ?', [strtolower($name)])
                ->first();
            
            if ($student) {
                return $student->StudID;
            }
        } catch (\Exception $e) {
            Log::warning('Could not connect to school database: ' . $e->getMessage());
        }

        return null;
    }

    private function formatNameToLastnameFirst($fullName)
    {
        $fullName = trim($fullName);
        
        // Split name into parts
        $parts = array_values(array_filter(preg_split('/\s+/', $fullName)));
        
        if (count($parts) === 0) {
            return $fullName;
        }
        
        if (count($parts) === 1) {
            // Only one name part, return as is in uppercase
            return strtoupper($parts[0]);
        }
        
        // Last part is the last name, rest are first/middle names
        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);
        
        // Format: LASTNAME, First Name
        return strtoupper($lastName) . ', ' . $firstName;
    }

    private function calculateNameSimilarity($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        // Exact match
        if ($name1 === $name2) {
            return 100.0;
        }

        // Remove common suffixes/prefixes
        $name1 = preg_replace('/\b(jr|sr|ii|iii|iv)\b\.?/i', '', $name1);
        $name2 = preg_replace('/\b(jr|sr|ii|iii|iv)\b\.?/i', '', $name2);

        // Split into parts and filter empty
        $parts1 = array_values(array_filter(preg_split('/\s+/', trim($name1))));
        $parts2 = array_values(array_filter(preg_split('/\s+/', trim($name2))));

        // Must have same number of name parts
        if (count($parts1) !== count($parts2)) {
            return 0.0;
        }

        // If no parts, return 0
        if (count($parts1) === 0) {
            return 0.0;
        }

        $totalSimilarity = 0.0;
        
        // Compare each part in order
        for ($i = 0; $i < count($parts1); $i++) {
            $part1 = $parts1[$i];
            $part2 = $parts2[$i];
            
            // Calculate similarity for this part
            $percent = 0.0;
            similar_text($part1, $part2, $percent);
            
            // If any part is less than 90% similar, names don't match
            if ($percent < 90.0) {
                return 0.0;
            }
            
            $totalSimilarity += $percent;
        }

        // Return average similarity
        return $totalSimilarity / count($parts1);
    }
}
