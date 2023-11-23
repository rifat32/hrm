<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'due_date',
        'end_date',
        'status',
        'project_id',
        'parent_task_id',
        'assigned_by',

        "is_active",
        "business_id",
        "created_by"
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent_task()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function assigned_by()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }


    public function assignees() {
        return $this->belongsToMany(TaskAssignee::class, 'task_assignees', 'task_id', 'assignee_id');
    }



}
