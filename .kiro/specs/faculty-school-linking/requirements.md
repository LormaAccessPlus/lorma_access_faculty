# Requirements Document

## Introduction

The system currently has two separate faculty models - the main application's Faculty model for Google authentication and the SchoolFaculty model that connects to the school database. Faculty members are not properly linked between these two systems, preventing proper integration of authentication with school data access and subject assignments.

## Glossary

- **Faculty**: The main application's faculty model that handles Google OAuth authentication and stores user session data
- **SchoolFaculty**: The school database faculty model that contains academic information, department assignments, and subject relationships
- **School Database**: The external database system that contains academic data including faculty, students, subjects, and grades
- **Faculty Linking**: The process of establishing and maintaining relationships between Faculty and SchoolFaculty records
- **Authentication System**: The Google OAuth-based login system used by faculty members
- **Subject Assignment**: The relationship between faculty members and the subjects they teach in the school database

## Requirements

### Requirement 1

**User Story:** As a faculty member, I want my Google account to be automatically linked to my school database record, so that I can access my assigned subjects and student data without manual configuration.

#### Acceptance Criteria

1. WHEN a faculty member logs in with Google OAuth, THE Authentication System SHALL attempt to match their email address with a SchoolFaculty record
2. IF a matching SchoolFaculty record is found by email, THEN THE Authentication System SHALL establish the link by updating the school_faculty_id field
3. THE Faculty model SHALL store the school_faculty_id to maintain the relationship with the SchoolFaculty record
4. WHILE a faculty member is authenticated, THE Authentication System SHALL provide access to their linked SchoolFaculty data
5. IF no matching SchoolFaculty record exists, THEN THE Authentication System SHALL log the unmatched faculty member for administrative review

### Requirement 2

**User Story:** As a system administrator, I want to manually link faculty accounts to school database records, so that I can resolve cases where automatic linking fails or needs correction.

#### Acceptance Criteria

1. THE Faculty Linking System SHALL provide an interface for administrators to view unlinked faculty accounts
2. THE Faculty Linking System SHALL allow administrators to search SchoolFaculty records by name, email, or faculty code
3. WHEN an administrator selects a Faculty and SchoolFaculty pair, THE Faculty Linking System SHALL establish the link between the records
4. THE Faculty Linking System SHALL validate that a SchoolFaculty record is not already linked to another Faculty account before creating the link
5. THE Faculty Linking System SHALL log all manual linking actions with administrator identification and timestamp

### Requirement 3

**User Story:** As a faculty member, I want to view my subject assignments and student rosters from the school database, so that I can access the academic data I need for teaching.

#### Acceptance Criteria

1. WHEN a faculty member accesses their dashboard, THE Faculty System SHALL display their current subject assignments from the linked SchoolFaculty record
2. THE Faculty System SHALL show subject details including subject code, description, schedule, and enrolled student count
3. WHEN a faculty member selects a subject, THE Faculty System SHALL display the student roster for that subject
4. THE Faculty System SHALL provide access to grade entry and modification functions for assigned subjects
5. IF a faculty member is not linked to a SchoolFaculty record, THEN THE Faculty System SHALL display a message indicating the account needs to be linked

### Requirement 4

**User Story:** As a system administrator, I want to monitor faculty linking status and resolve linking issues, so that all faculty members have proper access to their academic data.

#### Acceptance Criteria

1. THE Faculty Linking System SHALL provide a dashboard showing the count of linked and unlinked faculty accounts
2. THE Faculty Linking System SHALL display a list of faculty members who have logged in but are not linked to school database records
3. THE Faculty Linking System SHALL show potential matches for unlinked faculty based on email similarity and name matching
4. THE Faculty Linking System SHALL allow administrators to mark faculty accounts as "no school record" to exclude them from unlinked reports
5. THE Faculty Linking System SHALL send notifications to administrators when new unlinked faculty members are detected

### Requirement 5

**User Story:** As a faculty member, I want my profile information to be synchronized between my Google account and school database record, so that my information remains consistent across systems.

#### Acceptance Criteria

1. WHEN a faculty member's Google profile is updated, THE Faculty System SHALL compare the information with their linked SchoolFaculty record
2. THE Faculty System SHALL highlight discrepancies between Google profile data and SchoolFaculty data
3. WHERE profile synchronization is enabled, THE Faculty System SHALL update the Faculty record with current Google profile information
4. THE Faculty System SHALL maintain an audit log of profile synchronization activities
5. THE Faculty System SHALL allow faculty members to view both their Google profile and school database information side by side