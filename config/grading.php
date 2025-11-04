<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Grade Computation Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for grade computation formulas
    | used throughout the application. These values can be modified to
    | adjust the grading system as needed.
    |
    */

    'prelim' => [
        'class_weight' => 0.4,
        'exam_weight' => 0.6,
    ],

    'midterm' => [
        'class_weight' => 0.4,
        'exam_weight' => 0.6,
    ],

    'finals' => [
        'class_weight' => 0.4,
        'exam_weight' => 0.6,
    ],

    'final_rating' => [
        'prelim_weight' => 0.3,
        'midterm_weight' => 0.3,
        'finals_weight' => 0.4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Term Configuration
    |--------------------------------------------------------------------------
    |
    | Available terms and their display names
    |
    */

    'terms' => [
        'prelim' => 'Prelims',
        'midterm' => 'Midterm',
        'finals' => 'Finals',
    ],
];