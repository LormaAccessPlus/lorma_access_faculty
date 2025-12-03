<?php

namespace App\Services;

class FormulaEvaluator
{
    /**
     * Evaluate a formula with given variables
     * 
     * Supported operators: +, -, *, /, (, )
     * Supported functions: SUM, AVG, MAX, MIN, IF
     * 
     * Examples:
     * - "A1 + A2 + A3"
     * - "SUM(A1:A5)"
     * - "AVG(A1:A3) * 0.4 + B1 * 0.6"
     * - "IF(A1 > 75, A1, 0)"
     * 
     * @param string $formula The formula to evaluate
     * @param array $variables Array of variable values ['A1' => 85, 'A2' => 90, ...]
     * @return float|null The result or null if error
     */
    public static function evaluate(string $formula, array $variables): ?float
    {
        try {
            // Replace variables with their values
            $expression = self::replaceVariables($formula, $variables);
            
            // Handle functions
            $expression = self::handleFunctions($expression, $variables);
            
            // Evaluate the mathematical expression
            return self::evaluateExpression($expression);
        } catch (\Exception $e) {
            \Log::error('Formula evaluation error: ' . $e->getMessage(), [
                'formula' => $formula,
                'variables' => $variables
            ]);
            return null;
        }
    }

    /**
     * Replace variable names with their values
     */
    private static function replaceVariables(string $formula, array $variables): string
    {
        $expression = $formula;
        
        // Sort variables by length (longest first) to avoid partial replacements
        uksort($variables, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        foreach ($variables as $var => $value) {
            // Replace variable with its value, ensuring word boundaries
            $expression = preg_replace('/\b' . preg_quote($var, '/') . '\b/', (string)$value, $expression);
        }
        
        return $expression;
    }

    /**
     * Handle Excel-like functions
     */
    private static function handleFunctions(string $expression, array $variables): string
    {
        // Handle SUM function: SUM(A1:A5) or SUM(A1,A2,A3)
        $expression = preg_replace_callback('/SUM\(([^)]+)\)/i', function($matches) use ($variables) {
            $args = $matches[1];
            $values = self::parseArguments($args, $variables);
            return array_sum($values);
        }, $expression);

        // Handle AVG function: AVG(A1:A5) or AVG(A1,A2,A3)
        $expression = preg_replace_callback('/AVG\(([^)]+)\)/i', function($matches) use ($variables) {
            $args = $matches[1];
            $values = self::parseArguments($args, $variables);
            return count($values) > 0 ? array_sum($values) / count($values) : 0;
        }, $expression);

        // Handle MAX function
        $expression = preg_replace_callback('/MAX\(([^)]+)\)/i', function($matches) use ($variables) {
            $args = $matches[1];
            $values = self::parseArguments($args, $variables);
            return count($values) > 0 ? max($values) : 0;
        }, $expression);

        // Handle MIN function
        $expression = preg_replace_callback('/MIN\(([^)]+)\)/i', function($matches) use ($variables) {
            $args = $matches[1];
            $values = self::parseArguments($args, $variables);
            return count($values) > 0 ? min($values) : 0;
        }, $expression);

        // Handle IF function: IF(condition, true_value, false_value)
        $expression = preg_replace_callback('/IF\(([^,]+),([^,]+),([^)]+)\)/i', function($matches) use ($variables) {
            $condition = trim($matches[1]);
            $trueValue = trim($matches[2]);
            $falseValue = trim($matches[3]);
            
            // Evaluate condition
            $conditionResult = self::evaluateCondition($condition, $variables);
            
            // Return appropriate value
            if ($conditionResult) {
                return is_numeric($trueValue) ? $trueValue : self::replaceVariables($trueValue, $variables);
            } else {
                return is_numeric($falseValue) ? $falseValue : self::replaceVariables($falseValue, $variables);
            }
        }, $expression);

        return $expression;
    }

    /**
     * Parse function arguments (handles ranges like A1:A5 and lists like A1,A2,A3)
     */
    private static function parseArguments(string $args, array $variables): array
    {
        $values = [];
        
        // Check if it's a range (e.g., A1:A5)
        if (strpos($args, ':') !== false) {
            list($start, $end) = explode(':', $args);
            $start = trim($start);
            $end = trim($end);
            
            // Extract numeric parts
            preg_match('/([A-Z]+)(\d+)/', $start, $startMatches);
            preg_match('/([A-Z]+)(\d+)/', $end, $endMatches);
            
            if ($startMatches && $endMatches) {
                $prefix = $startMatches[1];
                $startNum = (int)$startMatches[2];
                $endNum = (int)$endMatches[2];
                
                for ($i = $startNum; $i <= $endNum; $i++) {
                    $varName = $prefix . $i;
                    if (isset($variables[$varName])) {
                        $values[] = $variables[$varName];
                    }
                }
            }
        } else {
            // It's a comma-separated list
            $argList = explode(',', $args);
            foreach ($argList as $arg) {
                $arg = trim($arg);
                if (isset($variables[$arg])) {
                    $values[] = $variables[$arg];
                } elseif (is_numeric($arg)) {
                    $values[] = (float)$arg;
                }
            }
        }
        
        return $values;
    }

    /**
     * Evaluate a condition (e.g., "A1 > 75")
     */
    private static function evaluateCondition(string $condition, array $variables): bool
    {
        // Replace variables
        $condition = self::replaceVariables($condition, $variables);
        
        // Evaluate comparison operators
        if (preg_match('/(.+?)(>=|<=|>|<|==|!=)(.+)/', $condition, $matches)) {
            $left = (float)trim($matches[1]);
            $operator = trim($matches[2]);
            $right = (float)trim($matches[3]);
            
            switch ($operator) {
                case '>': return $left > $right;
                case '<': return $left < $right;
                case '>=': return $left >= $right;
                case '<=': return $left <= $right;
                case '==': return $left == $right;
                case '!=': return $left != $right;
            }
        }
        
        return false;
    }

    /**
     * Evaluate a mathematical expression
     */
    private static function evaluateExpression(string $expression): float
    {
        // Remove whitespace
        $expression = preg_replace('/\s+/', '', $expression);
        
        // Security: Only allow numbers, operators, and parentheses
        if (!preg_match('/^[\d+\-*\/().]+$/', $expression)) {
            throw new \Exception('Invalid expression');
        }
        
        // Use eval with safety checks (only mathematical operations)
        $result = eval('return ' . $expression . ';');
        
        return (float)$result;
    }

    /**
     * Validate a formula syntax
     */
    public static function validate(string $formula): array
    {
        $errors = [];
        
        // Check for balanced parentheses
        $openCount = substr_count($formula, '(');
        $closeCount = substr_count($formula, ')');
        if ($openCount !== $closeCount) {
            $errors[] = 'Unbalanced parentheses';
        }
        
        // Check for valid function names
        if (preg_match_all('/([A-Z]+)\(/i', $formula, $matches)) {
            $validFunctions = ['SUM', 'AVG', 'MAX', 'MIN', 'IF'];
            foreach ($matches[1] as $func) {
                if (!in_array(strtoupper($func), $validFunctions)) {
                    $errors[] = "Unknown function: $func";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
