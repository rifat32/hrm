<!DOCTYPE html>
<html>

<head>
    <title>Employee</title>

    <!--ALL CUSTOM FUNCTIONS -->
    @php
        // Define a function within the Blade file
        function processString($inputString)
        {
            // Remove underscore
            $withoutUnderscore = str_replace('_', '', $inputString);

            // Remove everything from the pound sign (#) and onwards
            $finalString = explode('#', $withoutUnderscore)[0];

            // Capitalize the string
            $capitalizedString = ucwords($finalString);

            return $capitalizedString;
        }
    @endphp

    @php
    // hour format
    function time_format($breakHours) {
        if(!$breakHours){
            $breakHours = 0;
        }

// Convert break hours to seconds
$breakSeconds = round($breakHours * 3600);

// Format seconds to "00:00:00" time format
$formattedBreakTime = gmdate("H:i:s", $breakSeconds);
return $formattedBreakTime;
    }

        // GETTING BUSINESS
        $business = auth()->user()->business;
        // GETTING LEAVE

        // GETTING LEAVE CREATED BY
        $created_by = null;
        if (auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }
        // GETTING LEAVE TYPE
        $leave_types = \App\Models\SettingLeaveType::where(function ($query) use ($request, $created_by) {
            $query
                ->where('setting_leave_types.business_id', null)
                ->where('setting_leave_types.is_default', 1)
                ->where('setting_leave_types.is_active', 1)
                ->whereDoesntHave('disabled', function ($q) use ($created_by) {
                    $q->whereIn('disabled_setting_leave_types.created_by', [$created_by]);
                })
                ->when(isset($request->is_active), function ($query) use ($request, $created_by) {
                    if (intval($request->is_active)) {
                        return $query->whereDoesntHave('disabled', function ($q) use ($created_by) {
                            $q->whereIn('disabled_setting_leave_types.business_id', [auth()->user()->business_id]);
                        });
                    }
                })
                ->orWhere(function ($query) use ($request, $created_by) {
                    $query
                        ->where('setting_leave_types.business_id', null)
                        ->where('setting_leave_types.is_default', 0)
                        ->where('setting_leave_types.created_by', $created_by)
                        ->where('setting_leave_types.is_active', 1)

                        ->when(isset($request->is_active), function ($query) use ($request) {
                            if (intval($request->is_active)) {
                                return $query->whereDoesntHave('disabled', function ($q) {
                                    $q->whereIn('disabled_setting_leave_types.business_id', [auth()->user()->business_id]);
                                });
                            }
                        });
                })
                ->orWhere(function ($query) use ($request) {
                    $query
                        ->where('setting_leave_types.business_id', auth()->user()->business_id)
                        ->where('setting_leave_types.is_default', 0)
                        ->when(isset($request->is_active), function ($query) use ($request) {
                            return $query->where('setting_leave_types.is_active', intval($request->is_active));
                        });
                });
        })->get();

        // GETTING ATTENDANCE

    @endphp


    <style>
        /* Add any additional styling for your PDF */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10px;
        }

        .table_head_row {
            color: #fff;
            background-color: #dc2d2a;
            font-weight: 600;
        }

        .table_head_row td {
            color: #fff;
        }

        .table_head_row th,
        tbody tr td {
            text-align: left;
            padding: 10px 0px;
        }

        .table_row {
            background-color: #ffffff;
        }

        .index_col {
            width: 5px;
        }

        .empty_table_row {}

        .table_row td {
            padding: 10px 0px;
            border-bottom: 0.2px solid #ddd;
        }

        .employee {
            color: #dc2d2a;
            /*font-weight:600;*/
        }

        .logo {
            width: 75px;
            height: 75px;
        }

        .file_title {
            font-size: 1.3rem;
            font-weight: bold;
            text-align: right;
        }

        .business_name {
            font-size: 1.2rem;
            font-weight: bold;
            display: block;
        }

        .business_address {}
    </style>

</head>

<body>

    {{-- PDF HEADING  --}}
    <table style="margin-top:-30px">
        <tbody>
            <tr>
                @if ($business->logo)
                    <td rowspan="2">
                        <img class="logo" src="{{ public_path($business->logo) }}">
                    </td>
                @endif
                <td></td>
            </tr>
            <tr>
                <td class="file_title">Employee Report</td>
            </tr>
            <tr>
                <td>
                    <span class="business_name">{{ $business->name }}</span>
                    <address class="business_address">{{ $business->address_line_1 }}</address>
                </td>

            </tr>
        </tbody>
    </table>


    {{-- ALL TABLES  --}}

    {{-- 1. PERSONAL DETAILS  --}}
    <table>
        <h3>Employee Details</h3>
        <thead>
            <tr class="table_head_row">
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Employe ID</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Gender</th>
            </tr>
        </thead>
        <tbody>
            <tr class="table_row">
                <td>
                    {{ $user->first_Name }}
                </td>
                <td>
                    {{ $user->last_Name }}
                </td>
                <td>
                    {{ $user->last_Name }}
                </td>
                <td>
                    {{ $user->user_id }}
                </td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->phone }}</td>
                <td>{{ $user->gender }}</td>
            </tr>
        </tbody>
    </table>

    {{-- 2. LEAVE ALLOWANCE  --}}
    <table>
        <h3>Leave Allowances</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Allowance Name</th>
                <th>Type</th>
                <th>Allowance</th>
                <th>Earned</th>
                <th>Availability</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $key => $leave_type)
                    @php
                        $total_recorded_hours = \App\Models\LeaveRecord::whereHas('leave', function ($query) use ($user, $leave_type) {
                            $query->where([
                                'user_id' => $user->id,
                                'leave_type_id' => $leave_type->id,
                            ]);
                        })
                            ->get()
                            ->sum(function ($record) {
                                return \Carbon\Carbon::parse($record->end_time)->diffInHours(\Carbon\Carbon::parse($record->start_time));
                            });
                        $leave_types[$key]->already_taken_hours = $total_recorded_hours;
                    @endphp
                    <tr class="table_row">
                        <td class="index_col">{{ $key + 1 }}</td>
                        <td style="text-align: left">
                            {{ $leave_type->name }}
                        </td>
                        <td>{{ $leave_type->type }}</td>
                        <td>{{ number_format($leave_type->amount, 2) }}/ month</td>
                        <td>{{ number_format($leave_type->already_taken_hours, 2) }} Hour</td>
                        <td>{{ number_format($leave_type->amount - $leave_type->already_taken_hours, 2) }} Hour</td>
                    </tr>
                @endforeach
            @else
                <tr>No Data Found</tr>
            @endif

        </tbody>
    </table>

    {{-- 3. ATTENDANCE  --}}
    <table>
        <h3>Attendances</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Date</th>

                <th>Start Time</th>
                <th>End Time</th>
                <th>Break (hour)</th>
                <th>Schedule (hour)</th>
                <th>Overtime (hour)</th>
            </tr>
        </thead>
        <tbody>
            @if (count($user->attendances))
                @foreach ($user->attendances as $index => $attendance)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        <td>{{ $attendance->in_date }}</td>
                        <td>{{ $attendance->in_time }}</td>
                        <td>{{ $attendance->out_time }}</td>
                        <td>{{ $attendance->does_break_taken?time_format($attendance->break_hours):0 }}</td>
                        <td>{{ time_format($attendance->capacity_hours) }}</td>
                        <td>{{ time_format($attendance->overtime_hours) }}</td>


                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif

        </tbody>
    </table>

    {{-- 4. LEAVE  --}}
    <table>
        <h3>Leaves</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Date & Time</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Total Leave (hours)</th>
                {{-- <th>Attachment</th> --}}
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 5. DOCUMENTS  --}}
    <table>
        <h3>Documents</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Title</th>
                {{-- <th>Attachment</th> --}}
                <th>Added by</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 6. ASSETS  --}}
    <table>
        <h3>Asstes</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Asset Name</th>
                <th>Asset Code</th>
                <th>Serial No</th>
                <th>Is Working</th>
                <th>Type</th>
                <th>Date</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 7. EDUCATIONAL HISTORY  --}}
    <table>
        <h3>Educational History</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Degree</th>
                <th>Major</th>
                </th>
                {{-- <th>Institution</th> ETA TO ONEK BOTO HOBE KEMNE SHOW KORAIBA CSS DIA DEIKHO PARO KINA --}}
                <th>Start Date</th>
                <th>Achivments</th>
                {{-- <th>Description</th> ETA TO ONEK BOTO HOBE KEMNE SHOW KORAIBA CSS DIA DEIKHO PARO KINA --}}
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td style="padding:0px 10px">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 8. JOB HISTORY  --}}
    <table>
        <h3>Job History</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Job Title</th>
                <th>Company</th>
                <th>Start On</th>
                <th>Start at</th>
                <th>Supervisor</th>
                {{-- <th>Contact Info</th> JAGA HOILE DIO EDI --}}
                <th>Country</th>
                {{-- <th>Achivments</th> JAGA HOILE DIO EDI --}}
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 9. IMMIGRATION DERAILS  --}}

    {{-- COS HISTORY --}}
    <table>
        <h3>COS History</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>From</th>
                <th>To</th>
                <th>Date Assign</th>
                <th>Expiry Date</th>
                <th>Cirtificate Number</th>
                <th>Status</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{--
                        <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>
    {{-- PASSPORT HISTORY --}}
    <table>
        <h3>Passport History</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>From</th>
                <th>To</th>
                <th>Issue Date</th>
                <th>Expiry Date</th>
                <th>Passport Number</th>
                <th>Place Of Issue</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{--
                            <td>{{ $user->user_id }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->designation->name }}</td>
                            <td>{{ processString($user->roles[0]->name) }}</td>
                            <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>
    </table>
    {{-- PASSPORT HISTORY --}}
    <table>
        <h3>Visa History</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>From</th>
                <th>To</th>
                <th>Issue Date</th>
                <th>Expiry Date</th>
                <th>BRP Number</th>
                <th>Place Of Issue</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{--
                            <td>{{ $user->user_id }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->designation->name }}</td>
                            <td>{{ processString($user->roles[0]->name) }}</td>
                            <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 10. ADDRESS DETAILS  --}}
    <table>
        <h3>Address Details</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Address</th>
                <th>City</th>
                <th>Country</th>
                <th>Postcode</th>

            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 11. CONTACT  --}}
    <table>
        <h3>Contact Details</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Full Name</th>
                <th>Relationship To Employee</th>
                <th>Address</th>
                <th>Postcode</th>
                <th>Mobile Number</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 12. NOTES  --}}
    <table>
        <h3>Notes</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Title</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 13. BANK DETAILS  --}}
    <table>
        <h3>Bank Details</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Bank Name</th>
                <th>Sort Code</th>
                <th>Account Name</th>
                <th>Account Number</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>

    {{-- 14. SOCIAL LINKS  --}}
    <table>
        <h3>Social Links</h3>
        <thead>
            <tr class="table_head_row">
                <th class="index_col"></th>
                <th>Website</th>
                <th>Url</th>
            </tr>
        </thead>
        <tbody>
            @if (1)
                @foreach ($leave_types as $index => $user)
                    <tr class="table_row">
                        <td class="index_col">{{ $index + 1 }}</td>
                        {{-- <td>{{ $user->user_id }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->designation->name }}</td>
                        <td>{{ processString($user->roles[0]->name) }}</td>
                        <td>{{ $user->is_active ? 'Active' : 'De-active' }}</td> --}}
                    </tr>
                @endforeach
            @else
                <tr colspan="7">No Data Found</tr>
            @endif
        </tbody>
    </table>
</body>

</html>
