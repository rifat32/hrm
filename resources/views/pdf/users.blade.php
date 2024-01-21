<!DOCTYPE html>
<html>
<head>
    <title>Employee List</title>
    <style>
        /* Add any additional styling for your PDF */
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

    <h2>Employee List</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <!-- Add more columns as needed -->
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ ($user->first_Name ." " . $user->last_Name . " " . $user->last_Name )}}</td>
                    <td>{{ $user->email }}</td>
                    <!-- Add more columns as needed -->
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
