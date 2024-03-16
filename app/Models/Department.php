<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "work_location_id",
        "description",
        "is_active",
        "manager_id",
        "parent_id",
        "business_id",
        "created_by"
    ];






    public function parent(){
        return $this->belongsTo(Department::class,'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id', 'id');
    }


    public function getAllDescendantIds()
    {
        $descendantIds = [];
        $this->getDescendantIdsRecursive($this, $descendantIds);
        return $descendantIds;
    }

    protected function getDescendantIdsRecursive($department, &$descendantIds)
    {
        foreach ($department->children as $child) {
            $descendantIds[] = $child->id;

            // Recursively get the descendants of the current child
            $this->getDescendantIdsRecursive($child, $descendantIds);
        }
    }

    public function getAllParentIds()
    {
        $parentIds = [];
        $this->getParentIdsRecursive($this, $parentIds);

        return $parentIds;
    }



    protected function getParentIdsRecursive($department, &$parentIds)
    {
        if ($department->parent) {
            // Include the parent ID
            $parentIds[] = $department->parent->id;

            // Recursively get the parent IDs of the current parent
            $this->getParentIdsRecursive($department->parent, $parentIds);
        }
    }


    public function getAllParentManagerIds()
    {
        $parentManagerIds = [];
        $this->getParentManagerIdsRecursive($this, $parentManagerIds);

        return $parentManagerIds;
    }
    protected function getParentManagerIdsRecursive($department, &$parentManagerIds)
    {
        if ($department->parent) {
            // Include the parent ID
            $parentManagerIds[] = $department->parent->manager_id;

            // Recursively get the parent IDs of the current parent
            $this->getParentManagerIdsRecursive($department->parent, $parentManagerIds);
        }
    }




    public function recursiveChildren()
    {
        return $this->children()->with('recursiveChildren');
    }

    public function getTotalUsersCountAttribute()
    {
        return DepartmentUser::where('department_id', $this->id)->count();
    }


    // public function parentRecursive()
    // {
    //     return $this->belongsTo(Department::class, 'parent_id', 'id')->with('parentRecursive');
    // }

    // public function getAllParentIdsAttribute()
    // {
    //     $parentIds = [$this->id]; // Start with the current department's ID

    //     $department = $this;

    //     while ($department->parentRecursive) {
    //         $parentIds[] = $department->parentRecursive->id;
    //         $department = $department->parentRecursive;
    //     }

    //     return array_reverse($parentIds); // Reverse the array to have the top-level parent (father) first
    // }
    // public function getAllParentDataAttribute()
    // {
    //     $parentData = [$this]; // Start with the current department's data

    //     $department = $this;

    //     while ($department->parentRecursive) {
    //         $parentData[] = $department->parentRecursive;
    //         $department = $department->parentRecursive;
    //     }

    //     return array_reverse($parentData); // Reverse the array to have the top-level parent (father) first
    // }








    public function children_recursive()
    {
        return $this->hasMany(Department::class, 'parent_id', 'id')->with(
            [
                "children_recursive" => function ($query) {
                    $query->select('departments.id', 'departments.name'); // Specify the fields for the creator relationship
                },
                "manager" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                }

            ]


        )
        // ->where([
        //     "is_active" => 1
        // ])
        ->addSelect([
            'total_users_count' => DepartmentUser::selectRaw('COUNT(*)')
                ->whereColumn('departments.id', 'department_id')
        ]);
      ;
    }
    // public function getAllChildrenDataAttribute()
    // {
    //     $childrenData = collect(); // Start with an empty collection

    //     $this->load('children_recursive');

    //     foreach ($this->children_recursive as $child) {
    //         $childrenData->push($child);

    //         if ($child->children_recursive->isNotEmpty()) {
    //             // If the child has children, recursively get their data
    //             $childrenData = $childrenData->merge($child->getAllChildrenDataAttribute());
    //         }
    //     }

    //     return $childrenData;
    // }

    public function payrun_department()
    {
        return $this->hasOne(PayrunDepartment::class, "department_id" ,'id');
    }




    public function work_location()
    {
        return $this->belongsTo(WorkLocation::class, "work_location_id" ,'id');
    }

    public function manager(){
        return $this->belongsTo(User::class,'manager_id', 'id');
    }
    public function holidays() {
        return $this->belongsToMany(Holiday::class, 'department_holidays', 'department_id', 'holiday_id');
    }
    public function users() {
        return $this->belongsToMany(User::class, 'department_users', 'department_id', 'user_id');
    }


    public function announcements() {
        return $this->belongsToMany(Announcement::class, 'department_announcements', 'department_id', 'announcement_id');
    }
    public function work_shifts() {
        return $this->belongsToMany(WorkShift::class, 'department_work_shifts', 'department_id', 'work_shift_id');
    }



    public function scopeWhereHasRecursiveHolidays($query, $today, $depth = 5)
    {
        if ($depth <= 0) {
            return; // Stop recursion if depth limit is reached
        }

        $query->whereHas('holidays', function ($subQuery) use ($today) {
            $subQuery->where('start_date', '<=', $today->startOfDay())
                     ->where('end_date', '>=', $today->endOfDay());
        })->orWhere(function ($query) use ($today, $depth) {
            $query->whereHas('parent', function ($subQuery) use ($today, $depth) {
                $subQuery->whereNotNull('parent_id');
                $subQuery->whereHasRecursiveHolidays($today, $depth - 1); // Decrease depth
            });
        });
    }





}
