<!DOCTYPE html>
<html>
<head>
    <title>Employee List</title>

    <!--ALL CUSTOM FUNCTIONS -->
    @php
        // Define a function within the Blade file
        function processString($inputString) {

            $finalString = explode('#', $inputString)[0];

        $finalString = str_replace('business_', '', $finalString);
        $finalString = str_replace('_', '', $finalString);

            // Capitalize the string
            $finalString = ucwords($finalString);

            return $finalString;
        }
    @endphp

    @php
        $business = auth()->user()->business;
    @endphp


    <style>
        /* Add any additional styling for your PDF */
        body {
            font-family: Arial, sans-serif;
            margin:0;
            padding:0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size:800px;


        }
        .table_head_row{
            color:#fff;
            background-color:#dc2d2a;
            font-weight:600;
        }
        .table_head_row td{
            color:#fff;
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
            border-bottom:0.2px solid #ddd;
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
    .logo{
        width:75px;
        height:75px;
    }
    .file_title{
        font-size:1.3rem;
        font-weight:bold;
        text-align:right;
    }
    .business_name{
        font-size:1.2rem;
        font-weight:bold;
        display:block;
    }
    .business_address{

    }
    </style>

</head>
<body>

    <table style="margin-top:-30px">
       <thead>
          <tr>
            <th class="file_title">Employee List: </th>
          </tr>
        </thead>
    </table>


    <table>
        <thead>
            <tr class="table_head_row">
                <th>Employee</th>
                <th>Employee ID</th>
                <th>Email</th>
                <th>Designation</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $index=>$user)
                <tr class="table_row">

                    <td class="employee">
                        {{ ($user->first_Name ." ". $user->last_Name ." ". $user->last_Name )}}
                    </td>
                    <td class="employee_id">{{ $user->user_id }}</td>
                    <td class="email">{{ $user->email }}</td>
                    <td class="designation">{{ $user->designation->name }}</td>
                    <td class="role">{{ processString($user->roles[0]->name) }}</td>
                    <td class="status">{{ $user->is_active ? "Active":"De-active" }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
