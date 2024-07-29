
<div class="code-snippet">
    <h3>Create {{ $names["singular_model_name"] }} Model and Migration</h3>
    <pre id="create_model"><code>
php artisan make:model -m {{ $names["singular_model_name"] }}
</code></pre>
    <button class="copy-button" onclick="copyToClipboard('create_model')">Copy</button>
</div>

<div class="code-snippet">
    <h3>Create Disabled{{ $names["singular_model_name"] }} Model and Migration</h3>
    <pre id="create_disabled_model"><code>
php artisan make:model -m Disabled{{ $names["singular_model_name"] }}
</code></pre>
    <button class="copy-button" onclick="copyToClipboard('create_disabled_model')">Copy</button>
</div>
