<?php

namespace App\Services;

use App\Models\StudentMapping;
use App\Models\Subject;
use App\Models\SchoolStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StudentMappingService
{
    /**
     * Automatically match GCR students to school database students
     */
    public function autoMatchStudents(Subject $subject, array $gcrStudents): array
    {
        $schoolStudents = $this->getSchoolStudentsForSubject($subject);
        $matches = [];
        $conflicts = [];
        $unmatched = [];

        foreach ($gcrStudents as $gcrStudent) {
            $match = $this->findBestMatch($gcrStudent, $schoolStudents);
            
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
     * Find the best match for a GCR student among school students
     */
    protected function findBestMatch(array $gcrStudent, Collection $schoolStudents): ?array
    {
        $bestMatch = null;
        $bestConfidence = 0;

        foreach ($schoolStudents as $schoolStudent) {
            $confidence = $this->calculateMatchConfidence($gcrStudent, $schoolStudent);
            
            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestMatch = [
                    'student' => $schoolStudent,
                    'confidence' => $confidence,
                    'type' => $this->getMatchType($gcrStudent, $schoolStudent, $confidence)
                ];
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate match confidence between GCR and school student
     */
    protected function calculateMatchConfidence(array $gcrStudent, $schoolStudent): float
    {
        $confidence = 0;

        // Email matching (highest weight)
        if (isset($gcrStudent['emailAddress']) && $schoolStudent->email) {
            if (strtolower($gcrStudent['emailAddress']) === strtolower($schoolStudent->email)) {
                $confidence += 0.6; // 60% weight for exact email match
            } else {
                $emailSimilarity = $this->emailSimilarity($gcrStudent['emailAddress'], $schoolStudent->email);
                if ($emailSimilarity > 0.8) {
                    $confidence += 0.4; // 40% weight for similar email
                }
            }
        }

        // Name matching
        if (isset($gcrStudent['profile']['name']['fullName']) && $schoolStudent->full_name) {
            $nameSimilarity = $this->nameSimilarity(
                $gcrStudent['profile']['name']['fullName'],
                $schoolStudent->full_name
            );
            $confidence += $nameSimilarity * 0.4; // 40% weight for name similarity
        }

        return $confidence;
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
    protected function getMatchType(array $gcrStudent, $schoolStudent, float $confidence): string
    {
        if (isset($gcrStudent['emailAddress']) && $schoolStudent->email &&
            strtolower($gcrStudent['emailAddress']) === strtolower($schoolStudent->email)) {
            return 'email_exact';
        }
        
        if ($confidence > 0.9) {
            return 'high_confidence';
        } elseif ($confidence > 0.7) {
            return 'medium_confidence';
        } else {
            return 'low_confidence';
        }
    }

    /**
     * Get school students for a specific subject
     */
    protected function getSchoolStudentsForSubject(Subject $subject): Collection
    {
        // This would typically query the school database for students enrolled in the subject
        // For now, returning a mock collection - this should be implemented based on actual school DB structure
        return collect([]);
    }

    /**
     * Save automatic matches to database
     */
    public function saveMatches(Subject $subject, array $matches): void
    {
        foreach ($matches as $match) {
            StudentMapping::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'gcr_student_id' => $match['gcr_student']['userId'] ?? null,
                ],
                [
                    'school_student_id' => $match['school_student']->id ?? null,
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
        ?int $schoolStudentId = null
    ): StudentMapping {
        return StudentMapping::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'gcr_student_id' => $gcrStudent['userId'] ?? null,
            ],
            [
                'school_student_id' => $schoolStudentId,
                'student_name' => $gcrStudent['profile']['name']['fullName'] ?? 'Unknown',
                'student_email' => $gcrStudent['emailAddress'] ?? null,
                'mapping_confidence' => $schoolStudentId ? 1.0 : 0.0, // Manual mappings have full confidence
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

        // Find duplicate school student mappings
        $schoolStudentCounts = $mappings->groupBy('school_student_id')
            ->filter(fn($group) => $group->count() > 1 && $group->first()->school_student_id !== null);

        foreach ($schoolStudentCounts as $schoolStudentId => $duplicateMappings) {
            $conflicts[] = [
                'mappings' => $duplicateMappings,
                'reason' => 'duplicate_school_student',
                'school_student_id' => $schoolStudentId
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