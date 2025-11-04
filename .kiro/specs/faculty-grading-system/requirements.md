# Requirements Document

## Introduction

This document outlines the requirements for a Laravel-based faculty grading system that integrates with existing school databases and Google Classroom. The system enables faculty to manage grades across multiple terms (Prelims, Midterm, Finals) while maintaining separation between the existing school database and new application data through a dual-database architecture.

## Requirements

### Requirement 1

**User Story:** As a faculty member, I want to log in using my @lorma.edu account, so that I can access my assigned subjects securely.

#### Acceptance Criteria

1. WHEN a faculty member visits the login page THEN the system SHALL present Google OAuth login for @lorma.edu accounts
2. WHEN a faculty member successfully authenticates THEN the system SHALL verify their domain is @lorma.edu
3. IF the authenticated user is not from @lorma.edu domain THEN the system SHALL deny access and display an error message
4. WHEN authentication is successful THEN the system SHALL create or update the faculty session

### Requirement 2

**User Story:** As a faculty member, I want to see my assigned subjects for the current semester and academic year upon login, so that I can quickly access my current teaching load.

#### Acceptance Criteria

1. WHEN a faculty member logs in successfully THEN the system SHALL query the existing school database for their current semester assignments
2. WHEN displaying current subjects THEN the system SHALL show subject code, subject name, section, and schedule
3. WHEN no current assignments exist THEN the system SHALL display a message indicating no current semester load
4. WHEN subjects are displayed THEN the system SHALL provide navigation to access each subject's grading interface

### Requirement 3

**User Story:** As a faculty member, I want to fetch and view my past semester loads from the school database, so that I can access historical teaching assignments.

#### Acceptance Criteria

1. WHEN a faculty member requests past semester data THEN the system SHALL query the existing school database for historical assignments
2. WHEN displaying past semesters THEN the system SHALL organize data by academic year and semester
3. WHEN past semester data is retrieved THEN the system SHALL display subject code, subject name, section, and academic period
4. WHEN no past data exists THEN the system SHALL display an appropriate message

### Requirement 4

**User Story:** As a faculty member, I want to input and manage grades that sync with the existing school database, so that student records are properly maintained in the official system.

#### Acceptance Criteria

1. WHEN a faculty member enters final computed grades THEN the system SHALL insert them into the existing school database tables
2. WHEN inserting grades THEN the system SHALL NOT modify existing school database structure or existing records
3. WHEN grade insertion fails THEN the system SHALL log the error and notify the faculty member
4. WHEN grades are successfully inserted THEN the system SHALL provide confirmation to the faculty member

### Requirement 5

**User Story:** As a faculty member, I want to integrate with Google Classroom to fetch my classes and students, so that I can streamline the grading process.

#### Acceptance Criteria

1. WHEN a faculty member connects to Google Classroom THEN the system SHALL authenticate using Google Classroom API
2. WHEN GCR integration is active THEN the system SHALL fetch all classes associated with the faculty member's account
3. WHEN GCR classes are retrieved THEN the system SHALL display them alongside school database subjects for matching
4. WHEN GCR connection fails THEN the system SHALL provide error feedback and allow manual grade entry

### Requirement 6

**User Story:** As a faculty member, I want to view students in a matrix format with activities as columns, so that I can efficiently input and track grades across multiple assignments.

#### Acceptance Criteria

1. WHEN viewing a subject's grading interface THEN the system SHALL display students in rows with the first column showing student names
2. WHEN displaying the grade matrix THEN the system SHALL show activities/assignments as subsequent columns
3. WHEN activities exist THEN the system SHALL separate lecture and laboratory activities visually
4. WHEN no activities exist THEN the system SHALL provide options to add new activities
5. WHEN grade cells are clicked THEN the system SHALL allow inline editing of scores

### Requirement 7

**User Story:** As a faculty member, I want to dynamically add activities that are not from Google Classroom, so that I can include all assessment components in my grading.

#### Acceptance Criteria

1. WHEN viewing the grade matrix THEN the system SHALL provide an "Add Activity" button
2. WHEN adding an activity THEN the system SHALL prompt for activity name, type (lecture/lab), maximum score, and term (prelim/midterm/finals)
3. WHEN an activity is created THEN the system SHALL add it as a new column in the appropriate section of the matrix
4. WHEN activities are added THEN the system SHALL store them in the new application database

### Requirement 8

**User Story:** As a faculty member, I want to handle subjects that may be lecture-only or lecture+laboratory, so that I can properly structure grades according to subject type.

#### Acceptance Criteria

1. WHEN configuring a subject THEN the system SHALL allow selection of subject type (lecture-only or lecture+laboratory)
2. WHEN a subject is lecture-only THEN the system SHALL display only lecture activities in the grade matrix
3. WHEN a subject is lecture+laboratory THEN the system SHALL display separate sections for lecture and lab activities
4. WHEN computing class standing THEN the system SHALL combine lecture and lab scores according to the configured weights

### Requirement 9

**User Story:** As a faculty member, I want to map Google Classroom students to school database students, so that grades can be properly attributed to the correct student records.

#### Acceptance Criteria

1. WHEN GCR students are fetched THEN the system SHALL attempt automatic matching based on email addresses or names
2. WHEN automatic matching is incomplete THEN the system SHALL provide a manual mapping interface
3. WHEN mapping students THEN the system SHALL store the mappings in the new application database
4. WHEN mappings are established THEN the system SHALL use them consistently for grade attribution
5. WHEN a mapping conflict occurs THEN the system SHALL alert the faculty member for resolution

### Requirement 10

**User Story:** As a faculty member, I want to manage grades across three terms (Prelims, Midterm, Finals) per semester, so that I can track student progress throughout the academic period.

#### Acceptance Criteria

1. WHEN accessing a subject THEN the system SHALL provide tabs or sections for Prelims, Midterm, and Finals
2. WHEN working within a term THEN the system SHALL display activities and grades specific to that term
3. WHEN switching between terms THEN the system SHALL preserve entered data and maintain context
4. WHEN all terms are complete THEN the system SHALL compute and display the final rating

### Requirement 11

**User Story:** As a faculty member, I want the system to automatically compute term grades and final ratings using configurable formulas, so that calculations are consistent and accurate.

#### Acceptance Criteria

1. WHEN class standing scores are entered THEN the system SHALL compute class standing percentage as (total score/total items) × 100 for Prelims
2. WHEN exam scores are entered THEN the system SHALL compute exam percentage as (score/items) × 100
3. WHEN computing Prelim grades THEN the system SHALL use formula: (Class standing × 40%) + (Exam grade × 60%)
4. WHEN computing Midterm grades THEN the system SHALL use formula: (Class standing × 40%) + (Exam grade × 60%) where class standing = (score/items) × 50 + 50 and exam grade = (score/items) × 50 + 50
5. WHEN computing Finals grades THEN the system SHALL use the same formula as Midterm
6. WHEN computing final rating THEN the system SHALL use formula: (Prelim × 30%) + (Midterm × 30%) + (Finals × 40%)
7. WHEN computation rules change THEN the system SHALL store new configurations in the application database without affecting existing calculations

### Requirement 12

**User Story:** As a system administrator, I want all application-specific data stored in a separate database from the school's existing database, so that we maintain data integrity and separation of concerns.

#### Acceptance Criteria

1. WHEN the application starts THEN the system SHALL connect to both the existing school database (read-only for most operations) and the new application database
2. WHEN storing grade computations THEN the system SHALL use the application database
3. WHEN storing student mappings THEN the system SHALL use the application database
4. WHEN storing activity configurations THEN the system SHALL use the application database
5. WHEN inserting final computed grades THEN the system SHALL write only to the existing school database grade tables
6. WHEN the application database is unavailable THEN the system SHALL prevent grade computation but allow viewing of school database data