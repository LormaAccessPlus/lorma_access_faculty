# Subject Mapping Workflow Requirements

## Introduction

This specification defines a new subject creation workflow that replaces manual subject creation with a mapping-based approach. The system will match Google Classroom courses with existing school database subjects, then map students between both systems. All grading activities will be based on these mappings.

## Requirements

### Requirement 1: Remove Manual Subject Creation

**User Story:** As a faculty member, I should not be able to manually create subjects, so that all subjects are properly mapped between Google Classroom and the school database.

#### Acceptance Criteria

1. WHEN a faculty member accesses the subjects section THEN the manual "Create Subject" form SHALL be removed
2. WHEN a faculty member tries to access the subject creation route THEN the system SHALL redirect them to the mapping interface
3. WHEN displaying the subjects index page THEN the system SHALL show a message indicating subjects are created through mapping
4. WHEN a faculty member clicks "New Subject" THEN the system SHALL redirect to the subject mapping interface

### Requirement 2: Google Classroom to School Database Subject Mapping

**User Story:** As a faculty member, I want to map my Google Classroom courses with subjects from the school database, so that I can create properly linked subjects in the system.

#### Acceptance Criteria

1. WHEN a faculty member accesses the mapping interface THEN the system SHALL display available Google Classroom courses
2. WHEN a faculty member accesses the mapping interface THEN the system SHALL display available school database subjects for the current academic period
3. WHEN a faculty member selects a Google Classroom course and school database subject THEN the system SHALL create a mapping record
4. WHEN creating a subject mapping THEN the system SHALL store both Google Classroom course details and school database subject details
5. WHEN a mapping is created THEN the system SHALL prevent the same Google Classroom course from being mapped again
6. WHEN a mapping is created THEN the system SHALL prevent the same school database subject from being mapped again for the same academic period
7. WHEN displaying available courses THEN the system SHALL exclude already mapped Google Classroom courses
8. WHEN displaying available subjects THEN the system SHALL exclude already mapped school database subjects

### Requirement 3: Student Mapping Within Subjects

**User Story:** As a faculty member, I want to map Google Classroom students with enrolled students from the school database for each subject, so that grades can be properly synchronized between systems.

#### Acceptance Criteria

1. WHEN a subject mapping is created THEN the system SHALL require student mapping before the subject becomes active
2. WHEN accessing student mapping for a subject THEN the system SHALL display Google Classroom students for that course
3. WHEN accessing student mapping for a subject THEN the system SHALL display enrolled students from the school database for that subject
4. WHEN displaying students for mapping THEN the system SHALL suggest automatic matches based on name similarity
5. WHEN a faculty member confirms student mappings THEN the system SHALL save the mapping relationships
6. WHEN student mappings are saved THEN the system SHALL mark the subject as "mapping complete"
7. WHEN a student mapping exists THEN the system SHALL prevent duplicate mappings for the same Google Classroom student
8. WHEN displaying suggested mappings THEN the system SHALL show confidence scores for automatic matches

### Requirement 4: Mapping-Based Subject Display

**User Story:** As a faculty member, I want to see all my subjects based on the mappings I've created, so that I can manage my classes effectively.

#### Acceptance Criteria

1. WHEN viewing the subjects list THEN the system SHALL display only mapped subjects
2. WHEN displaying a subject THEN the system SHALL show both Google Classroom and school database information
3. WHEN displaying a subject THEN the system SHALL show the mapping status (pending student mapping, completed, needs review)
4. WHEN displaying a subject THEN the system SHALL show the number of mapped students
5. WHEN a subject has pending student mappings THEN the system SHALL provide a link to complete the mapping
6. WHEN a subject mapping is complete THEN the system SHALL allow access to grading features

### Requirement 5: Mapping-Based Grading Activities

**User Story:** As a faculty member, I want all grading activities to be based on the student mappings, so that grades are properly synchronized between Google Classroom and the school database.

#### Acceptance Criteria

1. WHEN creating activities THEN the system SHALL use mapped students from both Google Classroom and school database
2. WHEN displaying grade ledgers THEN the system SHALL show students based on the mapping relationships
3. WHEN computing term grades THEN the system SHALL use school database student information for official records
4. WHEN syncing grades to Google Classroom THEN the system SHALL use Google Classroom student information
5. WHEN displaying grade matrices THEN the system SHALL show both Google Classroom names and school database names
6. WHEN generating reports THEN the system SHALL use school database student information as the primary source

### Requirement 6: Mapping Management and Statistics

**User Story:** As a faculty member, I want to see statistics and manage my subject mappings, so that I can track my mapping progress and maintain accurate data.

#### Acceptance Criteria

1. WHEN accessing the mapping dashboard THEN the system SHALL display total number of mappings
2. WHEN accessing the mapping dashboard THEN the system SHALL display number of pending student mappings
3. WHEN accessing the mapping dashboard THEN the system SHALL display number of completed mappings
4. WHEN accessing the mapping dashboard THEN the system SHALL display total number of student mappings
5. WHEN viewing mapping statistics THEN the system SHALL show recent mapping activity
6. WHEN a faculty member wants to delete a mapping THEN the system SHALL remove both subject and student mappings
7. WHEN deleting a mapping THEN the system SHALL warn about data loss and require confirmation

### Requirement 7: Academic Period Management

**User Story:** As a faculty member, I want to create mappings for specific academic periods, so that I can manage subjects across different semesters and years.

#### Acceptance Criteria

1. WHEN creating a subject mapping THEN the system SHALL require academic year and semester selection
2. WHEN displaying available school subjects THEN the system SHALL filter by selected academic period
3. WHEN viewing existing mappings THEN the system SHALL group by academic period
4. WHEN changing academic period THEN the system SHALL update available school subjects dynamically
5. WHEN a mapping exists for an academic period THEN the system SHALL prevent duplicate mappings for the same period

### Requirement 8: Error Handling and Validation

**User Story:** As a faculty member, I want clear error messages when mapping fails, so that I can understand and resolve issues.

#### Acceptance Criteria

1. WHEN Google Classroom API is unavailable THEN the system SHALL display an appropriate error message
2. WHEN school database is unavailable THEN the system SHALL display an appropriate error message
3. WHEN attempting to create duplicate mappings THEN the system SHALL prevent the action and show a clear error
4. WHEN student mapping fails THEN the system SHALL show which specific mappings failed and why
5. WHEN required fields are missing THEN the system SHALL highlight the missing information
6. WHEN API rate limits are exceeded THEN the system SHALL queue requests and inform the user

### Requirement 9: Data Migration and Compatibility

**User Story:** As a system administrator, I want existing manually created subjects to be handled gracefully, so that the transition to mapping-based subjects is smooth.

#### Acceptance Criteria

1. WHEN existing subjects exist in the database THEN the system SHALL continue to display them in a separate section
2. WHEN viewing legacy subjects THEN the system SHALL indicate they are not mapping-based
3. WHEN accessing legacy subjects THEN the system SHALL provide limited functionality compared to mapped subjects
4. WHEN a faculty member wants to convert legacy subjects THEN the system SHALL provide a migration path
5. WHEN displaying subjects THEN the system SHALL clearly distinguish between mapped and legacy subjects