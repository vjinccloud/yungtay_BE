<?php

return [
    'required' => 'This field is required.',
    'email' => 'Please enter a valid email address.',
    'confirmed' => 'The password confirmation does not match.',
    'min.string' => 'Must be at least :min characters.',
    'unique' => 'This email is already registered, please choose another one.',
    'captcha' => 'Incorrect captcha',
    'url' => 'Please enter a valid URL',
    'password.confirmed' => 'Password confirmation does not match',
    'password.min' => 'Password must be at least 8 characters long',
    'password.regex' => 'Password must contain at least 8 characters with numbers and at least 1 uppercase and 1 lowercase letter',
    'password_confirmation.required' => 'Please confirm your password',
    'password_confirmation.min' => 'Password confirmation must be at least 8 characters long',
    'password_confirmation.regex' => 'Password confirmation must contain at least 8 characters with numbers and at least 1 uppercase and 1 lowercase letter',
    
    // Member system validation
    'date' => 'Please select a valid date.',
    'in' => 'The selected option is invalid.',
    'numeric' => 'This field must be a number.',
    'image' => 'This field must be an image file.',
    'mimes' => 'Invalid file format, only accepts: :values.',
    'max.file' => 'File size must not exceed :max KB.',
    'dimensions' => 'Image dimensions do not meet requirements.',
    'boolean' => 'This field must be true or false.',
    'exists' => 'The selected option does not exist.',
    'integer' => 'This field must be an integer.',
    'between' => [
        'numeric' => 'Value must be between :min and :max.',
        'string' => 'Character length must be between :min and :max.',
    ],
    'max' => [
        'numeric' => 'Value must not be greater than :max.',
        'string' => 'Character length must not exceed :max.',
    ],
    'phone' => 'Please enter a valid phone number format.',
    'taiwan_phone' => 'Please enter a valid Taiwan phone number.',
    'after' => 'Date must be after :date.',
    'before' => 'Date must be before :date.',
    'after_or_equal' => 'Date must be on or after :date.',
    'before_or_equal' => 'Date must be on or before :date.',
    'password' => [
        'same_as_current' => 'New password cannot be the same as current password. Please set a different password.',
    ],
];