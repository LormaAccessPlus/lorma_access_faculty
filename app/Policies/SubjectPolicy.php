<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\Faculty;
use Illuminate\Auth\Access\Response;

class SubjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Faculty $faculty): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Faculty $faculty, Subject $subject): bool
    {
        return $faculty->id === $subject->faculty_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Faculty $faculty): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Faculty $faculty, Subject $subject): bool
    {
        return $faculty->id === $subject->faculty_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Faculty $faculty, Subject $subject): bool
    {
        return $faculty->id === $subject->faculty_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Faculty $faculty, Subject $subject): bool
    {
        return $faculty->id === $subject->faculty_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Faculty $faculty, Subject $subject): bool
    {
        return $faculty->id === $subject->faculty_id;
    }
}
