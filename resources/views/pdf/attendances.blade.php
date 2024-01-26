<!DOCTYPE html>
<html>
<head>
    <title>Employee List</title>

    <!--ALL CUSTOM FUNCTIONS -->
    @php
        // Define a function within the Blade file
        function processString($inputString) {
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
        $business = auth()->user()->business;
    @endphp


    <style>
        /* Add any additional styling for your PDF */
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size:10px;
            box-shadow:5px 5px 10px #000000;

        }
        .table_head_row{
            background-color:#f9f9fb;
            font-weight:600;
        }
        .table_head_row th, tbody tr td {
            text-align: left;
            padding:10px 0px;
        }
        .table_row {
            background-color:#ffffff;
        }
        .table_row td{
            padding:10px 0px;
            border-bottom:0.5px solid #666;
        }

        .employee_index{

        }
        .employee{
            color:#dc2d2a;
            /*font-weight:600;*/
        }
        .employee_name{

        }
        .role{

        }

    </style>

</head>
<body>


    <table>
       <tbody>
          <tr>
            <th rowspan="2">

                @if (empty($business->logo))
                {{$business->name}}
                @else
                <img src="{{public_path($business->logo)}}" >
                @endif



            </th>
            <th></th>
          </tr>
          <tr>
            <th>Employee List </th>
          </tr>
          <tr>
            <td>Business Name</td>
            <td></td>
          </tr>
        </tbody>
    </table>




    <h4>Employee List {{public_path(env("APP_URL").auth()->user()->business->logo)}}</h4>
    <table>
        <thead>
            <tr class="table_head_row">
                <th></th>
                {{-- <th>Employee</th> --}}
                {{-- <th>Employee ID</th>
                <th>Email</th>
                <th>Designation</th>
                <th>Role</th>
                <th>Status</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $index=>$user)
                <tr class="table_row">
                    {{-- <td class="employee_index" style="padding:0px 10px">{{ $index+1 }}</td>
                    <td class="employee">
                        {{ ($user->first_Name ." ". $user->last_Name ." ". $user->last_Name )}}
                    </td>
                    <td class="employee_id">{{ $user->user_id }}</td>
                    <td class="email">{{ $user->email }}</td>
                    <td class="designation">{{ ($user->designation->name) }}</td>
                    <td class="role">{{ processString($user->roles[0]->name) }}</td>
                    <td class="status">{{ $user->is_active ? "Active":"De-active" }}</td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
