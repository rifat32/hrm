select
    users.id
from
    users
where
    exists (
        select
            *
        from
            department_users
        where
            users.id = department_users.user_id
            and exists (
                select
                    *
                from
                    departments
                where
                    department_users.department_id = departments.id
                    and departments.id in (?, ?, ?, ?)
            )
    )
    and users.deleted_at is null








select
    count(*) as aggregate
from
    work_shift_detail_histories
where
    (
        day = ?
        and is_weekend = ?
    )
    and exists (
        select
            *
        from
            work_shift_histories
        where
            work_shift_detail_histories.work_shift_id = work_shift_histories.id
            and exists (
                select
                    *
                from
                    users
                    inner join employee_user_work_shift_histories on users.id = employee_user_work_shift_histories.user_id
                where
                    work_shift_histories.id = employee_user_work_shift_histories.work_shift_id
                    and users.id in (?, ?, ?, ?, ?, ?, ?)
                    and employee_user_work_shift_histories.from_date <= ?
                    and (
                        employee_user_work_shift_histories.to_date > ?
                        or employee_user_work_shift_histories.to_date is null
                    )
                    and users.deleted_at is null
            )
    )




select
    count(*) as aggregate
from
    attendances
where
    (is_present = ?)
    and user_id in (?, ?, ?, ?, ?, ?, ?)
    and in_date = ?
