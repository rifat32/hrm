<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Caller Form</title>
    <style>
        /* Simple styling to make the form look a bit better */
        body {
            font-family: Arial, sans-serif;
        }
        form {
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-bottom: 10px;
        }
        input, textarea, select {
            width: 100%;
            height: 40px;
            margin-bottom: 20px;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
        }
        textarea {
            height: 100px;
        }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        #response {
            margin-top: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<form id="apiForm">
    <label for="apiUrl">API URL:</label>
    <input type="text" id="apiUrl" name="apiUrl" required>

    <label for="bearerToken">Bearer Token:</label>
    <input type="text" id="bearerToken" name="bearerToken" required>

    <label for="payload">Payload Data (JSON):</label>
    <textarea id="payload" name="payload"></textarea>

    <label for="method">Request Method:</label>
    <select id="method" name="method" required>
        <option value="GET">GET</option>
        <option value="POST">POST</option>
        <option value="PUT">PUT</option>
        <option value="DELETE">DELETE</option>
    </select>

    <button type="submit">Call API</button>
</form>

<div id="response"></div>

<script>
    const form = document.getElementById('apiForm');
    const responseDiv = document.getElementById('response');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const apiUrl = document.getElementById('apiUrl').value;
        const bearerToken = document.getElementById('bearerToken').value;
        const payload = document.getElementById('payload').value;
        const method = document.getElementById('method').value;

        try {
            let headers = {
                'Authorization': `Bearer ${bearerToken}`,
                'Content-Type': 'application/json'
            };

            let body;
            if (method.toUpperCase()!== 'GET') {
                body = payload? JSON.parse(payload) : {};
            }

            const response = await fetch(apiUrl, {
                method: method.toUpperCase(),
                headers,
                body: body? JSON.stringify(body) : undefined
            });

            const responseData = await response.json();
            responseDiv.innerText = JSON.stringify(responseData, null, 2);
        } catch (error) {
            responseDiv.innerText = 'Error:'+ error.message;
        }
    });
</script>

</body>
</html>
