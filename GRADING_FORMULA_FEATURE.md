# Grading Formula Configuration Feature

## Overview
Teachers can now define custom grading formulas for each term and configure the final rating computation directly from the Term Grades page.

## Features Added

### 1. Term Grading Configuration
Located at the top of the Term Grades page, teachers can configure:

**For Each Term (Prelim, Midterm, Finals):**
- **Class Standing Weight** - Percentage weight of class standing (activities)
- **Exam Weight** - Percentage weight of exam score
- Weights must sum to 100%

**Default Configurations:**
- **Prelim**: 100% Class Standing, 0% Exam
  - Formula: (Total Score / Total Possible) × 100
- **Midterm/Finals**: 60% Class Standing, 40% Exam
  - Formula: (Score / Items) × 50 + 50 (transmuted)

### 2. Final Rating Configuration
Teachers can configure how the final grade is computed:

**Final Rating Formula:**
- **Prelim Weight** - Default: 30%
- **Midterm Weight** - Default: 30%
- **Finals Weight** - Default: 40%
- Weights must sum to 100%

**Calculation:**
```
Final Rating = (Prelim × Prelim%) + (Midterm × Midterm%) + (Finals × Finals%)
```

### 3. User Interface

**Configuration Panel:**
- Collapsible sections for Term Config and Final Rating Config
- Real-time validation (weights must sum to 100%)
- Visual display of current configuration
- Color-coded buttons and sections

**Display Features:**
- Shows current formula for the active term
- Shows current final rating formula
- Helpful tooltips explaining the formulas
- Success/error notifications on save

## Database Structure

### New Table: `grading_configs`
```sql
- id
- subject_id (foreign key)
- term (prelim, midterm, finals)
- formula_config (JSON)
- class_standing_weight (decimal)
- exam_weight (decimal)
- timestamps
- unique(subject_id, term)
```

### Updated Table: `subjects`
```sql
- final_rating_config (JSON) - Stores prelim/midterm/finals weights
```

## API Endpoints

### Save Term Configuration
```
POST /grades/subjects/{subject}/grading-config
```
**Payload:**
```json
{
  "term": "prelim",
  "class_standing_weight": 100,
  "exam_weight": 0
}
```

### Save Final Rating Configuration
```
POST /grades/subjects/{subject}/final-rating-config
```
**Payload:**
```json
{
  "prelim_weight": 30,
  "midterm_weight": 30,
  "finals_weight": 40
}
```

## How It Works

### Term Grade Computation

1. **Class Standing Calculation:**
   - Sum all activity scores for the term
   - Divide by total possible score
   - Apply formula based on term:
     - Prelim: `(score/total) × 100`
     - Midterm/Finals: `(score/total) × 50 + 50`

2. **Term Grade Calculation:**
   ```
   Term Grade = (Class Standing × CS Weight) + (Exam Score × Exam Weight)
   ```

3. **Example (Midterm with 60/40 split):**
   ```
   Activities: 450/500 = 90%
   Transmuted CS: (90/100) × 50 + 50 = 95
   Exam Score: 85
   
   Term Grade = (95 × 0.60) + (85 × 0.40)
              = 57 + 34
              = 91
   ```

### Final Rating Computation

```
Final Rating = (Prelim Grade × Prelim Weight) + 
               (Midterm Grade × Midterm Weight) + 
               (Finals Grade × Finals Weight)
```

**Example (30/30/40 split):**
```
Prelim: 88
Midterm: 91
Finals: 93

Final Rating = (88 × 0.30) + (91 × 0.30) + (93 × 0.40)
             = 26.4 + 27.3 + 37.2
             = 90.9
```

## Usage Instructions

### For Teachers:

1. **Navigate to Term Grades:**
   - Go to Grading System → Select Subject → Term Grading

2. **Configure Term Formula:**
   - Click "Configure Term" button
   - Adjust Class Standing and Exam weights
   - Ensure they sum to 100%
   - Click "Save Term Config"

3. **Configure Final Rating:**
   - Click "Configure Final Rating" button
   - Adjust Prelim, Midterm, and Finals weights
   - Ensure they sum to 100%
   - Click "Save Final Config"

4. **View Current Configuration:**
   - Current formulas are always displayed at the top
   - Shows both term-specific and final rating formulas

### Automatic Grade Computation:

Once configured, the system automatically:
- Calculates class standing from activity scores
- Computes term grades using the configured weights
- Calculates final ratings using the configured formula
- Updates grades in real-time as scores are entered

## Benefits

1. **Flexibility** - Teachers can customize grading to match their syllabus
2. **Transparency** - Students can see exactly how grades are computed
3. **Accuracy** - Automatic calculations eliminate manual errors
4. **Consistency** - Same formula applied to all students
5. **Easy Updates** - Change formulas anytime without affecting existing grades

## Technical Implementation

### Models:
- `GradingConfig` - Stores term-specific configurations
- `Subject` - Stores final rating configuration

### Controllers:
- `GradeController@saveGradingConfig` - Saves term configuration
- `GradeController@saveFinalRatingConfig` - Saves final rating configuration

### Views:
- `resources/views/grades/term-grades.blade.php` - Configuration UI

### JavaScript:
- Alpine.js for reactive UI
- Fetch API for saving configurations
- Real-time validation

## Future Enhancements

Potential additions:
1. Formula templates (e.g., "Traditional", "OBE", "Custom")
2. Activity-specific weights within class standing
3. Bonus points configuration
4. Grade rounding rules
5. Historical formula tracking
6. Import/export formula configurations
