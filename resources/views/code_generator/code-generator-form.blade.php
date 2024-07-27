<div class="container">
    <h1 class="text-center mt-5">Code Generation Form</h1>
    <form method="POST" action="{{ route('code-generator') }}">
        @csrf
        <div class="mb-3">
            <label for="table_name" class="form-label">Database Table Name</label>
            <input type="text" class="form-control" id="table_name"
               name="table_name" value="{{!empty($names["table_name"])?$names["table_name"]:''}}">
        </div>


        <div class="row">
            <div class="row" id="field-container">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="field_name" class="form-label">Field Name</label>
                        <input type="text" class="form-control" id="table_name" name="field_name[]">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="basic_validation_rules" class="control-label">Basic Validation Rules:</label>
                        <select class="form-control select2" id="basic_validation_rules" name="basic_validation_rules[]">
                            <optgroup label="Basic Validation Rules">
                                @foreach($validationRules['Basic Validation Rules'] as $rule)
                                    <option value="{{ $rule }}">{{ $rule }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="validation_type" class="control-label">Validation Type:</label>
                        <select class="form-control select2" id="validation_type" name="validation_type[]" onchange="showValidationRules(this.value, this)">
                            <option value="">Select Type</option>
                            <option value="string">String</option>
                            <option value="number">Number</option>
                            
                            <option value="array">Array</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3" id="string-validation-rules" style="display: none;">
                    <div class="form-group">
                        <label for="string_validation_rules" class="control-label">String Validation Rules:</label>
                        <select class="form-control select2" id="string_validation_rules" name="string_validation_rules[]">
                            <optgroup label="String Validation Rules">
                                @foreach($validationRules['String Validation Rules'] as $rule)
                                    <option value="{{ $rule }}">{{ $rule }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="col-md-3" id="number-validation-rules" style="display: none;">
                    <div class="form-group">
                        <label for="number_validation_rules" class="control-label">Number Validation Rules:</label>
                        <select class="form-control select2" id="number_validation_rules" name="number_validation_rules[]">
                            <optgroup label="Number Validation Rules">
                                @foreach($validationRules['Numeric Validation Rules'] as $rule)
                                    <option value="{{ $rule }}">{{ $rule }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
        </div>


        <button type="button" class="btn btn-primary" id="add-more">Add More</button>






        <button type="submit" class="btn btn-primary">Generate Code</button>
    </form>
</div>



{{-- <script>
  function showValidationRules(value) {
    var stringRules = document.getElementById('string-validation-rules');
    var numberRules = document.getElementById('number-validation-rules');


    if (value =='string') {
        stringRules.style.display = 'block';
        numberRules.style.display = 'none';
    } else if (value == 'number') {
        stringRules.style.display = 'none';
        numberRules.style.display = 'block';
    } else {
        stringRules.style.display = 'none';
        numberRules.style.display = 'none';

    }
}

</script> --}}





<script>
    document.addEventListener('DOMContentLoaded', function() {
        var fieldContainer = document.getElementById('field-container');
        var addMoreButton = document.getElementById('add-more');

        addMoreButton.addEventListener('click', function() {
            var newRow = fieldContainer.innerHTML;
            var newDiv = document.createElement('div');
            newDiv.className = 'row';
            newDiv.innerHTML = newRow;
            fieldContainer.parentNode.appendChild(newDiv);
        });
    });

    function showValidationRules(value, element) {
        console.log(element.parentNode.parentNode)
        var validationType = value;
        var stringValidationRules = element.parentNode.parentNode.parentNode.querySelector('#string-validation-rules');
        var numberValidationRules = element.parentNode.parentNode.parentNode.querySelector('#number-validation-rules');

        if (validationType =='string') {
            stringValidationRules.style.display = 'block';
            numberValidationRules.style.display = 'none';
        } else if (validationType == 'number') {
            stringValidationRules.style.display = 'none';
            numberValidationRules.style.display = 'block';
        } else {
            stringValidationRules.style.display = 'none';
            numberValidationRules.style.display = 'none';
        }
    }
</script>
