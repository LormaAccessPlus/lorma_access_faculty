# Score Formatting Update

## Summary
Updated score display to remove `.00` from whole numbers while keeping decimal values for non-whole numbers.

## Changes Made

### 1. Created Helper Function
- **File**: `app/Helpers/GradeHelper.php`
- **Function**: `format_score($value, $decimals = 2)`
- **Behavior**:
  - Whole numbers: `20.00` → `20`
  - Decimals: `18.84` → `18.84`
  - Trailing zeros removed: `95.50` → `95.5`
  - Null/empty: Returns `-`

### 2. Updated Views
- **term-grades.blade.php**: Activity score inputs now display without `.00`
- **matrix.blade.php**: Exam scores display without `.00` when whole numbers

### 3. Autoload Configuration
- Added `app/Helpers/helpers.php` to `composer.json` autoload files
- Helper function `format_score()` is now globally available

## Examples

### Activity Scores (Input Fields)
- Before: `20.00`, `50.00`, `18.50`
- After: `20`, `50`, `18.5`

### Computed Values (Still Show Decimals)
- Class Standing: `18.84` (keeps 2 decimals)
- Exam Grade: `85.00` → `85` (removes .00)
- Term Grade: `75.50` → `75.5` (removes trailing zero)

## Usage
```php
// In Blade templates
{{ format_score($score) }}          // Default 2 decimals
{{ format_score($score, 1) }}       // 1 decimal place
{{ format_score($score, 0) }}       // No decimals (whole numbers only)
```

## Technical Details
- Whole numbers are detected using `floor($value) == $value`
- Trailing zeros are removed using `rtrim()`
- Empty values and null return `-`
- Type-safe: converts strings to float before processing
