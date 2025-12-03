-- ============================================
-- NURSING MATRIX DATABASE MIGRATIONS
-- ============================================

-- 1. Add comprehensive_exam_scores to subjects table
-- This stores comprehensive exam scores as JSON
ALTER TABLE subjects 
ADD COLUMN comprehensive_exam_scores JSON NULL 
COMMENT 'Stores comprehensive exam scores for nursing subjects: {"student_mapping_id": score}';

-- Example data structure:
-- {
--   "1": 85.50,
--   "2": 90.00,
--   "3": 78.25
-- }

-- ============================================

-- 2. Add activity_category to activities table
-- This differentiates between regular activities and quizzes
ALTER TABLE activities 
ADD COLUMN activity_category ENUM('activity', 'quiz') DEFAULT 'activity' 
COMMENT 'Differentiates between activities and quizzes for nursing matrix';

-- Add index for faster queries
CREATE INDEX idx_activities_category ON activities(activity_category);

-- ============================================

-- 3. Ensure term_grades.formula_config can store nursing-specific data
-- The formula_config JSON should store:
-- {
--   "activities_score": 12.50,
--   "quizzes_score": 20.75,
--   "exam_score": 48.00,
--   "activities_total": 80,
--   "activities_max": 100,
--   "quizzes_total": 90,
--   "quizzes_max": 100,
--   "exam_total": 85,
--   "exam_max": 100
-- }

-- Verify column exists (should already exist from previous migrations)
-- If not, add it:
-- ALTER TABLE term_grades 
-- ADD COLUMN formula_config JSON NULL 
-- COMMENT 'Stores detailed breakdown of grade components';

-- ============================================

-- ALTERNATIVE APPROACH: Separate comprehensive_exams table
-- If you prefer a normalized approach instead of JSON storage

CREATE TABLE IF NOT EXISTS comprehensive_exams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    student_mapping_id BIGINT UNSIGNED NOT NULL,
    score DECIMAL(5, 2) NOT NULL COMMENT 'Comprehensive exam score',
    max_score DECIMAL(5, 2) DEFAULT 100.00 COMMENT 'Maximum possible score',
    exam_date DATE NULL COMMENT 'Date when exam was taken',
    remarks TEXT NULL COMMENT 'Additional notes about the exam',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    CONSTRAINT fk_comp_exam_subject 
        FOREIGN KEY (subject_id) 
        REFERENCES subjects(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_comp_exam_student 
        FOREIGN KEY (student_mapping_id) 
        REFERENCES student_mappings(id) 
        ON DELETE CASCADE,
    
    -- Unique constraint: one comprehensive exam per student per subject
    UNIQUE KEY unique_comp_exam (subject_id, student_mapping_id),
    
    -- Indexes for performance
    INDEX idx_comp_exam_subject (subject_id),
    INDEX idx_comp_exam_student (student_mapping_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================

-- 4. Add matrix_type to subjects table (if not exists)
-- This helps identify which matrix calculation to use
ALTER TABLE subjects 
ADD COLUMN matrix_type ENUM('zero-based', 'general-education', 'nursing', 'customized') 
DEFAULT 'general-education'
COMMENT 'Determines which grading matrix and formulas to use';

-- Add index
CREATE INDEX idx_subjects_matrix_type ON subjects(matrix_type);

-- ============================================

-- SAMPLE DATA INSERTION QUERIES

-- Insert sample comprehensive exam scores (JSON approach)
UPDATE subjects 
SET comprehensive_exam_scores = JSON_OBJECT(
    '1', 85.50,
    '2', 90.00,
    '3', 78.25
)
WHERE id = 1 AND matrix_type = 'nursing';

-- Insert sample comprehensive exam (table approach)
INSERT INTO comprehensive_exams (subject_id, student_mapping_id, score, max_score, exam_date)
VALUES 
    (1, 1, 85.50, 100.00, '2024-12-15'),
    (1, 2, 90.00, 100.00, '2024-12-15'),
    (1, 3, 78.25, 100.00, '2024-12-15');

-- Update activities to mark quizzes
UPDATE activities 
SET activity_category = 'quiz' 
WHERE name LIKE '%quiz%' 
   OR name LIKE '%Quiz%'
   OR type = 'quiz';

-- ============================================

-- ROLLBACK QUERIES (if needed)

-- Remove comprehensive_exam_scores column
-- ALTER TABLE subjects DROP COLUMN comprehensive_exam_scores;

-- Remove activity_category column
-- ALTER TABLE activities DROP COLUMN activity_category;

-- Drop comprehensive_exams table
-- DROP TABLE IF EXISTS comprehensive_exams;

-- Remove matrix_type column
-- ALTER TABLE subjects DROP COLUMN matrix_type;

-- ============================================
