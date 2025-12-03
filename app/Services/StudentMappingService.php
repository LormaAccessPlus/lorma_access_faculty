<?php

namespace App\Services;

use App\Models\StudentMapping;
use App\Models\Subject;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StudentMappingService
{
    /**
     * Automatically match GCR students to database students
     */
    public function autoMatchStudents(Subject $subject, array $gcrStudents): array
    {
        $students = $this->getSchoolStudentsForSubject($subject);
        $matches = [];
        $conflicts = [];
        $unmatched = [];

        foreach ($gcrStudents as $gcrStudent) {
            $match = $this->findBestMatch($gcrStudent, $students);
            
            if ($match && $match['confidence'] >= 0.8) {
                $matches[] = [
                    'gcr_student' => $gcrStudent,
                    'school_student' => $match['student'],
                    'confidence' => $match['confidence'],
                    'match_type' => $match['type']
                ];
            } elseif ($match && $match['confidence'] >= 0.5) {
                $conflicts[] = [
                    'gcr_student' => $gcrStudent,
                    'possible_matches' => [$match],
                    'confidence' => $match['confidence']
                ];
            } else {
                $unmatched[] = $gcrStudent;
            }
        }

        return [
            'matches' => $matches,
            'conflicts' => $conflicts,
            'unmatched' => $unmatched
        ];
    }

    /**
     * Find the best match for a GCR student among database students
     */
    protected function findBestMatch(array $gcrStudent, Collection $students): ?array
    {
        $bestMatch = null;
        $bestConfidence = 0;

        foreach ($students as $student) {
            $confidence = $this->calculateMatchConfidence($gcrStudent, $student);
            
            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestMatch = [
                    'student' => $student,
                    'confidence' => $confidence,
                    'type' => $this->getMatchType($gcrStudent, $student, $confidence)
                ];
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate match confidence between GCR and database student
     * Priority: Name matching is the primary validation criterion
     */
    protected function calculateMatchConfidence(array $gcrStudent, $student): float
    {
        $confidence = 0;

        // Name matching (PRIMARY validation - highest weight)
        if (isset($gcrStudent['profile']['name']['fullName']) && $student->full_name) {
            $nameSimilarity = $this->nameSimilarity(
                $gcrStudent['profile']['name']['fullName'],
                $student->full_name
            );
            
            // If names match exactly or very closely, give high confidence
            if ($nameSimilarity >= 0.95) {
                return 1.0; // 95%+ name match = 100% confidence, automatic match
            } elseif ($nameSimilarity >= 0.85) {
                $confidence += 0.9; // 85-95% name match = 90% confidence
            } elseif ($nameSimilarity >= 0.75) {
                $confidence += 0.8; // 75-85% name match = 80% confidence
            } else {
                $confidence += $nameSimilarity * 0.7; // Below 75% = proportional confidence
            }
        }

        // Google User ID matching (secondary - for already matched students)
        if (isset($gcrStudent['userId']) && $student->google_user_id) {
            if ($gcrStudent['userId'] === $student->google_user_id) {
                return 1.0; // Exact Google User ID match = 100% confidence
            }
        }

        // Email matching (tertiary - bonus if available)
        if (isset($gcrStudent['emailAddress']) && $student->email) {
            if (strtolower($gcrStudent['emailAddress']) === strtolower($student->email)) {
                return 1.0; // Exact email match = 100% confidence
            } else {
                // Add small bonus for similar emails
                $emailSimilarity = $this->emailSimilarity($gcrStudent['emailAddress'], $student->email);
                if ($emailSimilarity > 0.8) {
                    $confidence += 0.1; // 10% bonus for similar email
                }
            }
        }

        return min($confidence, 1.0); // Cap at 100%
    }

    /**
     * Calculate email similarity
     */
    protected function emailSimilarity(string $email1, string $email2): float
    {
        $email1 = strtolower($email1);
        $email2 = strtolower($email2);
        
        // Extract username parts before @
        $username1 = explode('@', $email1)[0];
        $username2 = explode('@', $email2)[0];
        
        return $this->stringSimilarity($username1, $username2);
    }

    /**
     * Calculate name similarity using various algorithms
     */
    protected function nameSimilarity(string $name1, string $name2): float
    {
        $name1 = $this->normalizeName($name1);
        $name2 = $this->normalizeName($name2);
        
        // Exact match
        if ($name1 === $name2) {
            return 1.0;
        }
        
        // Split names into parts
        $parts1 = explode(' ', $name1);
        $parts2 = explode(' ', $name2);
        
        // Check for partial matches (first name, last name combinations)
        $partialMatches = 0;
        $totalParts = max(count($parts1), count($parts2));
        
        foreach ($parts1 as $part1) {
            foreach ($parts2 as $part2) {
                if ($this->stringSimilarity($part1, $part2) > 0.8) {
                    $partialMatches++;
                    break;
                }
            }
        }
        
        $partialScore = $partialMatches / $totalParts;
        
        // Overall string similarity
        $overallSimilarity = $this->stringSimilarity($name1, $name2);
        
        return max($partialScore, $overallSimilarity);
    }

    /**
     * Normalize name for comparison
     */
    protected function normalizeName(string $name): string
    {
        // Convert to lowercase, remove extra spaces, handle common name variations
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Remove common prefixes/suffixes
        $name = preg_replace('/\b(jr|sr|ii|iii|iv)\b\.?/', '', $name);
        
        return trim($name);
    }

    /**
     * Calculate string similarity using Levenshtein distance
     */
    protected function stringSimilarity(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }

    /**
     * Determine the type of match based on confidence and matching factors
     */
    protected function getMatchType(array $gcrStudent, $student, float $confidence): string
    {
        // Check for exact name match
        if (isset($gcrStudent['profile']['name']['fullName']) && $student->full_name) {
            $nameSimilarity = $this->nameSimilarity(
                $gcrStudent['profile']['name']['fullName'],
                $student->full_name
            );
            if ($nameSimilarity >= 0.95) {
                return 'name_exact';
            }
        }
        
        // Check for Google User ID match
        if (isset($gcrStudent['userId']) && $student->google_user_id &&
            $gcrStudent['userId'] === $student->google_user_id) {
            return 'google_id_exact';
        }
        
        // Check for email match
        if (isset($gcrStudent['emailAddress']) && $student->email &&
            strtolower($gcrStudent['emailAddress']) === strtolower($student->email)) {
            return 'email_exact';
        }
        
        // Confidence-based types
        if ($confidence >= 0.9) {
            return 'high_confidence';
        } elseif ($confidence >= 0.8) {
            return 'medium_confidence';
        } else {
            return 'low_confidence';
        }
    }

    /**
     * Get students enrolled in a specific subject
     */
    protected function getSchoolStudentsForSubject(Subject $subject): Collection
    {
        try {
            // Get students enrolled in this subject through the student_subject pivot table
            $students = Student::query()
                ->join('student_subject', 'students.id', '=', 'student_subject.student_id')
                ->where('student_subject.subject_id', $subject->id)
                ->where('student_subject.status', 'enrolled')
                ->select('students.*')
                ->get();

            // Add full_name attribute to each student for easier matching
            return $students->map(function ($student) {
                $student->full_name = $student->full_name;
                return $student;
            });
        } catch (\Exception $e) {
            Log::error('Failed to fetch students for subject', [
                'subject_id' => $subject->id,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Save automatic matches to database
     */
    public function saveMatches(Subject $subject, array $matches): void
    {
        foreach ($matches as $match) {
            // Get student ID - handle both object and array formats
            $studentId = null;
            if (isset($match['school_student'])) {
                if (is_object($match['school_student'])) {
                    $studentId = $match['school_student']->id ?? null;
                } elseif (is_array($match['school_student'])) {
                    $studentId = $match['school_student']['id'] ?? null;
                }
            }
            
            // Log for debugging
            Log::info('Saving match', [
                'gcr_name' => $match['gcr_student']['profile']['name']['fullName'] ?? 'Unknown',
                'student_id' => $studentId,
                'confidence' => $match['confidence']
            ]);
            
            StudentMapping::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'gcr_student_id' => $match['gcr_student']['userId'] ?? null,
                ],
                [
                    'student_id' => $studentId,
                    'student_name' => $match['gcr_student']['profile']['name']['fullName'] ?? 'Unknown',
                    'student_email' => $match['gcr_student']['emailAddress'] ?? null,
                    'mapping_confidence' => $match['confidence'],
                ]
            );
        }
    }

    /**
     * Create manual mapping
     */
    public function createManualMapping(
        Subject $subject,
        array $gcrStudent,
        ?int $studentId = null
    ): StudentMapping {
        return StudentMapping::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'gcr_student_id' => $gcrStudent['userId'] ?? null,
            ],
            [
                'student_id' => $studentId,
                'student_name' => $gcrStudent['profile']['name']['fullName'] ?? 'Unknown',
                'student_email' => $gcrStudent['emailAddress'] ?? null,
                'mapping_confidence' => $studentId ? 1.0 : 0.0, // Manual mappings have full confidence
            ]
        );
    }

    /**
     * Get all mappings for a subject
     */
    public function getMappingsForSubject(Subject $subject): Collection
    {
        return StudentMapping::where('subject_id', $subject->id)
            ->with('subject')
            ->orderBy('student_name')
            ->get();
    }

    /**
     * Delete a mapping
     */
    public function deleteMapping(int $mappingId): bool
    {
        return StudentMapping::destroy($mappingId) > 0;
    }

    /**
     * Get mapping conflicts that need manual resolution
     */
    public function getMappingConflicts(Subject $subject): array
    {
        $mappings = $this->getMappingsForSubject($subject);
        $conflicts = [];

        // Find mappings with low confidence that might need review
        foreach ($mappings as $mapping) {
            if ($mapping->mapping_confidence < 0.8 && $mapping->mapping_confidence > 0) {
                $conflicts[] = [
                    'mapping' => $mapping,
                    'reason' => 'low_confidence',
                    'confidence' => $mapping->mapping_confidence
                ];
            }
        }

        // Find duplicate student mappings
        $studentCounts = $mappings->groupBy('student_id')
            ->filter(fn($group) => $group->count() > 1 && $group->first()->student_id !== null);

        foreach ($studentCounts as $studentId => $duplicateMappings) {
            $conflicts[] = [
                'mappings' => $duplicateMappings,
                'reason' => 'duplicate_student',
                'student_id' => $studentId
            ];
        }

        return $conflicts;
    }

    /**
     * Resolve mapping conflict by keeping one mapping and removing others
     */
    public function resolveConflict(int $keepMappingId, array $removeMappingIds): bool
    {
        try {
            StudentMapping::whereIn('id', $removeMappingIds)->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to resolve mapping conflict', [
                'keep_mapping_id' => $keepMappingId,
                'remove_mapping_ids' => $removeMappingIds,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}