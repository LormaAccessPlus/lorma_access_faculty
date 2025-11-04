# Implementation Plan

- [x] 1. Database Schema Updates
  - Create migration to add mapping fields to subjects table (gcr_class_name, school_subject_code, school_subject_name, school_schedule_code, mapping_status, student_mappings_count, mapping_notes)
  - Create migration to add mapping fields to student_mappings table (school_student_name, mapping_status, mapped_by, mapped_at)
  - Update Subject model fillable fields to include new mapping fields
  - Update StudentMapping model fillable fields to include new mapping fields
  - _Requirements: 2.4, 3.7, 6.6_

- [ ] 2. Core Mapping Service Implementation
  - [x] 2.1 Create SubjectMappingService class with core mapping logic
    - Implement getAvailableGoogleClassroomCourses method to fetch unmapped GCR courses
    - Implement getAvailableSchoolSubjects method to fetch unmapped school subjects by academic period
    - Implement createSubjectMapping method to create subject mappings with validation
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 7.1, 7.2_

  - [ ] 2.2 Implement student mapping functionality in SubjectMappingService
    - Create getStudentsForMapping method to fetch students from both systems
    - Implement suggestStudentMappings method with name similarity algorithm
    - Create saveStudentMappings method to persist student mappings
    - Implement getMappingStatistics method for dashboard metrics
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 6.1, 6.2, 6.3, 6.4_

  - [ ] 2.3 Write unit tests for SubjectMappingService
    - Test mapping creation with valid and invalid data
    - Test student matching algorithm accuracy
    - Test statistics calculation methods
    - _Requirements: 2.1-2.8, 3.1-3.8_

- [ ] 3. Subject Mapping Controller Implementation
  - [ ] 3.1 Create SubjectMappingController with mapping interface routes
    - Implement index method to display mapping dashboard with statistics
    - Create store method to handle subject mapping creation
    - Implement showStudentMapping method for student mapping interface
    - _Requirements: 2.1, 2.2, 6.1, 6.2, 6.3, 6.4_

  - [ ] 3.2 Implement student mapping and AJAX endpoints
    - Create saveStudentMappings method to handle student mapping form submission
    - Implement getGoogleClassroomCourses AJAX endpoint
    - Create getSchoolSubjects AJAX endpoint with academic period filtering
    - Implement destroy method for mapping deletion with confirmation
    - _Requirements: 3.5, 3.6, 6.6, 7.4, 8.3_

  - [ ] 3.3 Write feature tests for SubjectMappingController
    - Test mapping creation workflow end-to-end
    - Test student mapping interface and form submission
    - Test AJAX endpoints for dynamic data loading
    - _Requirements: 2.1-2.8, 3.1-3.8, 6.1-6.7_

- [ ] 4. User Interface Implementation
  - [ ] 4.1 Create subject mapping dashboard view
    - Build mapping statistics cards display
    - Create existing mappings list with status indicators
    - Implement create new mapping modal with form validation
    - Add academic period selection with dynamic school subject loading
    - _Requirements: 2.1, 2.2, 6.1, 6.2, 6.3, 6.4, 7.1, 7.3_

  - [ ] 4.2 Build student mapping interface
    - Create side-by-side student display (GCR vs School)
    - Implement suggested mappings with confidence scores
    - Build manual mapping controls and bulk actions
    - Add progress indicators and validation feedback
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

  - [ ] 4.3 Implement JavaScript functionality for mapping interface
    - Add dynamic form validation and AJAX form submission
    - Implement real-time school subject filtering by academic period
    - Create student mapping drag-and-drop or selection interface
    - Add confirmation dialogs for mapping deletion
    - _Requirements: 7.4, 8.3, 8.5, 6.6_

- [ ] 5. Remove Manual Subject Creation
  - [ ] 5.1 Update routing to disable manual subject creation
    - Remove create and store routes from subjects resource routes
    - Update web.php to redirect subject creation attempts to mapping interface
    - _Requirements: 1.1, 1.2_

  - [ ] 5.2 Update subject-related views and navigation
    - Remove "Create Subject" buttons and replace with "Map Subject" buttons
    - Update subjects index page to show mapping-based creation message
    - Modify navigation menu to prioritize Subject Mapping over manual creation
    - Update dashboard quick actions to use mapping workflow
    - _Requirements: 1.3, 1.4_

  - [ ] 5.3 Update SubjectController to handle legacy subjects
    - Modify index method to distinguish between mapped and legacy subjects
    - Update show method to display mapping information for mapped subjects
    - Remove create and store methods from SubjectController
    - _Requirements: 9.1, 9.2, 9.5_

- [ ] 6. Integration with Grading System
  - [ ] 6.1 Update Activity system to use student mappings
    - Modify ActivityController to get students from StudentMapping model
    - Update activity creation forms to show both GCR and school student names
    - Ensure grade recording uses mapped student relationships
    - _Requirements: 5.1, 5.5_

  - [ ] 6.2 Update Grade Ledger to display mapped students
    - Modify grade matrix views to show both GCR and school database names
    - Update grade computation to use school database student data as primary
    - Ensure grade sync uses GCR student data for Classroom integration
    - _Requirements: 5.2, 5.3, 5.4_

  - [ ] 6.3 Update Term Grading to use mapping data
    - Modify term grade computation to use school database student information
    - Update grade reports to use school database as primary data source
    - Ensure proper student identification across all grading components
    - _Requirements: 5.3, 5.6_

- [ ] 7. Error Handling and Validation
  - [ ] 7.1 Implement comprehensive error handling
    - Add try-catch blocks for Google Classroom API failures with user-friendly messages
    - Implement school database connection error handling with retry options
    - Create validation for duplicate mapping prevention
    - _Requirements: 8.1, 8.2, 8.3_

  - [ ] 7.2 Add form validation and user feedback
    - Implement client-side validation for mapping forms
    - Add server-side validation with detailed error messages
    - Create user feedback for successful operations and failures
    - _Requirements: 8.4, 8.5_

  - [ ] 7.3 Write integration tests for error scenarios
    - Test API failure handling and graceful degradation
    - Test validation error responses and user feedback
    - Test data consistency during partial failures
    - _Requirements: 8.1-8.5_

- [ ] 8. Final Integration and Testing
  - [ ] 8.1 Update application configuration
    - Add configuration for current academic year and semester
    - Update service provider bindings for new mapping services
    - Configure caching for Google Classroom and school database data
    - _Requirements: 7.1, 7.2_

  - [ ] 8.2 Perform end-to-end testing
    - Test complete workflow from mapping creation to student grading
    - Verify all grading components work with mapped subjects
    - Test academic period transitions and data filtering
    - _Requirements: 1.1-1.4, 2.1-2.8, 3.1-3.8, 5.1-5.6_

  - [ ] 8.3 Create comprehensive test suite
    - Write integration tests for complete mapping workflow
    - Test performance with large student datasets
    - Verify security and access control measures
    - _Requirements: All requirements_