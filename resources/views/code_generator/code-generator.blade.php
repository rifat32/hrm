
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap Demo with Code Snippet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        /* Custom CSS */
        body {
            background-color: #f8f9fa;
            /* Light gray background */
        }

        .code-snippet {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
            position: relative;
            /* Necessary for absolute positioning of the button */
        }

        .copy-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f0f0f0;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1 class="text-center mt-5">Code Generation Form</h1>
        <form method="POST" action="{{ route('code-generator') }}">
            @csrf <div class="mb-3">
                <label for="table_name" class="form-label">Database Table Name</label>
                <input type="text" class="form-control" id="table_name"
 name="table_name" value="{{!empty($names["table_name"])?$names["table_name"]:''}}">
            </div>
            <button type="submit" class="btn btn-primary">Generate Code</button>
        </form>
   </div>





    @if (!empty($names))
    @include("code_generator.routes")
    @include("code_generator.controller")
    @include("code_generator.models_migrations")
    @include("code_generator.requests")
    @include("code_generator.custom_rules")

    @include("code_generator.permissions")
    @endif









    {{-- <div class="container">
      <h1 class="text-center mt-5">Sample</h1>
      <div class="row justify-content-center">
          <div class="col-md-8">



              <div class="code-snippet">
                  <h3>Sample Code</h3>
                  <pre id="sample"><code>

// ddd
        </code></pre>
                  <button class="copy-button" onclick="copyToClipboard('sample')">Copy</button>
              </div>


          </div>
      </div>
  </div> --}}



    <script>
        function copyToClipboard(id) {
            const codeElement = document.getElementById(id);
            const codeText = codeElement.textContent;
            const tempElement = document.createElement('textarea');
            tempElement.value = codeText;
            document.body.appendChild(tempElement);
            tempElement.select();
            document.execCommand('copy');
            document.body.removeChild(tempElement);
            alert('Code copied to clipboard!');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</body>

</html>
