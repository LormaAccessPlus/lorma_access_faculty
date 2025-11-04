# Access DB Database Structure Analysis

## Overview
The `access_db` database is a comprehensive academic management system that tracks students, teachers, subjects, grades, and various administrative activities. Below is a detailed analysis of the database structure and relationships.

## 1. Core Entity Tables

### Students
**Primary Table: `studentdata`**
- **Primary Key**: `StudID` (char(7))
- **Key Fields**: 
  - Personal info: FirstName, MiddleName, LastName, Email
  - Academic: CourseID, YearLevel, CurriculumID
  - Status: isActive, isGraduate, Archived
  - Enrollment: RegDate, ModifiedDate, ModifiedBy

### Teachers
**Primary Table: `teacher`**
- **Primary Key**: `TeacherID` (varchar(20))
- **Key Fields**: 
  - Personal: FirstName, MiddleName, LastName
  - Department: DeptID

### Subjects
**Primary Table: `subject`**
- **Primary Key**: `SubjectID` (varchar(12))
- **Key Fields**: 
  - Description, Units, LabUnits
  - SubjectType, DeptID, CategoryID
  - Status: Enabled

### Schedules/Classes
**Primary Table: `schedule`**
- **Primary Key**: `SchedID` (bigint unsigned)
- **Key Fields**: 
  - `CodeNumber` (varchar(12)) - Unique class identifier
  - `SubjectID`, `TeacherID`
  - Time/Location: StartTime, EndTime, Room, Day
  - Academic Period: Sem, SchoolYear
  - Course/Department: CourseID, DeptID

## 2. Grade Storage System

### Primary Grades Table: `termgrades`
**Structure**:
- **Primary Key**: `ID` (bigint unsigned, auto_increment)
- **Student Link**: `StudID` (char(7))
- **Class Link**: `CodeNumber` (varchar(12))
- **Academic Period**: `Term` (varchar(6)), `SchoolYear` (char(9))

**Grade Fields**:
- `PrelimGrade` (varchar(20))
- `MidtermGrade` (varchar(20))
- `FinalsGrade` (varchar(20))
- `PreFinalsGrade` (varchar(20))
- Alternative grading periods:
  - `FirstGrading`, `SecondGrading`, `ThirdGrading`, `FourthGrading`

**Timestamps**:
- `PrelimDate`, `MidtermDate`, `FinalsDate`, `PreFinalsDate`
- `FirstDate`, `SecondDate`, `ThirdDate`, `FourthDate`
- `LastUpdate`

**Additional Fields**:
- `isRemedial` (int unsigned) - Indicates remedial status

## 3. Entity Relationships

### Student ↔ Grades
```
studentdata.StudID → termgrades.StudID
```

### Grades ↔ Subject ↔ Teacher
```
termgrades.CodeNumber → schedule.CodeNumber
schedule.SubjectID → subject.SubjectID
schedule.TeacherID → teacher.TeacherID
```

### Student ↔ Enrollment
```
studentdata.StudID → enrollment.StudID
enrollment: StudID + CourseID + YearLevel + Term + SchoolYear
```

### Student ↔ Section
```
studentdata.StudID → studentsection.StudID
studentsection.SectionID → section.ID
section.Adviser → teacher.TeacherID
```

### Teacher ↔ Subject Expertise
```
teacher.TeacherID → teachersubject.TeacherID
teachersubject.SubjectID → subject.SubjectID
```

## 4. Audit and Logging Tables

### Grade Change Tracking: `gradechange`
**Purpose**: Tracks all grade modification requests and approvals
**Key Fields**:
- `TeacherID`, `StudID`, `SchedID`
- Grade fields: `PrelimGrade`, `MidtermGrade`, `FinalsGrade`, etc.
- `Period` (varchar(10)) - Which grading period was changed
- `Grade` (varchar(20)) - New grade value
- `RequestType` (char(1)) - Type of change request
- `Requested` (datetime) - When change was requested
- `Approved` (datetime) - When change was approved

### General Data Changes: `datachange`
**Purpose**: Tracks general data modification requests
**Key Fields**:
- `Requestor` (varchar(32)) - Who requested the change
- `RequestDate` (datetime)
- `Data` (text) - Details of the change
- `ApprovedDate`, `ApprovedBy`, `DeniedBy`, `DeniedDate`

### User Activity: `onlineuser`
**Purpose**: Tracks active user sessions
**Key Fields**:
- `sessid` (char(40)) - Session ID
- `user` (varchar(32)) - Username
- `time` (int) - Timestamp
- `location`, `page`, `host` - Activity details

### Login/Logout Tracking: `lorclog`
**Purpose**: Tracks user login/logout activities
**Key Fields**:
- `fUserName` (varchar(32)) - Username
- `TimeIn`, `TimeOut` (datetime)
- `StudID` (varchar(7)) - If student login

### Additional Logging Tables
- `emaillog` - Email activity tracking
- `studenrollmentlog` - Student enrollment changes
- `studledgerhistory` - Financial transaction history
- `unenrollhistory` - Unenrollment tracking
- `studentnotices` - Student notifications/notices

## 5. Key Relationships Summary

### ERD-Style Relationships

```
STUDENT (studentdata)
├── StudID (PK)
├── CourseID, YearLevel
└── Personal/Contact Info

TEACHER (teacher)
├── TeacherID (PK)
├── DeptID
└── Name Info

SUBJECT (subject)
├── SubjectID (PK)
├── DeptID
└── Course Details

SCHEDULE (schedule)
├── SchedID (PK)
├── CodeNumber (Unique Class ID)
├── SubjectID (FK → subject)
├── TeacherID (FK → teacher)
└── Time/Location Info

TERMGRADES (termgrades)
├── ID (PK)
├── StudID (FK → studentdata)
├── CodeNumber (FK → schedule)
├── Term, SchoolYear
└── Grade Fields (Prelim, Midterm, Finals, etc.)

ENROLLMENT (enrollment)
├── StudID (FK → studentdata)
├── CourseID, YearLevel
└── Term, SchoolYear
```

### Relationship Flow
1. **Student Enrollment**: `studentdata` → `enrollment` → `studentsection`
2. **Class Assignment**: `schedule` links `subject` + `teacher` + time/location
3. **Grade Recording**: `termgrades` links `student` + `class` (via CodeNumber)
4. **Audit Trail**: All changes tracked in `gradechange`, `datachange`, etc.

## 6. System Activity Tracking

The system comprehensively tracks:

### Academic Activities
- **Grade Changes**: Complete audit trail with approval workflow
- **Enrollment Changes**: Student enrollment/unenrollment history
- **Schedule Changes**: Class schedule modifications

### User Activities
- **Login/Logout**: Session tracking with timestamps
- **Data Modifications**: General data change requests and approvals
- **Email Communications**: Email activity logging

### Financial Activities
- **Student Ledger**: Financial transaction history
- **Payment Tracking**: Payment records and modifications

### Administrative Activities
- **Student Notices**: Communication tracking
- **Document Management**: File uploads and references
- **System Access**: User session and page access tracking

## 7. Grade Calculation Notes

The system supports multiple grading periods:
- **College/University**: Prelim, Midterm, Finals, PreFinals
- **K-12**: First, Second, Third, Fourth Grading
- **Remedial**: Special flag for remedial classes

Each grade entry includes both the grade value and timestamp, enabling complete grade history tracking.
