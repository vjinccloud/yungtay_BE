<?php

return [
    // Password reset email
    'password_reset_subject' => ':site Password Reset Notification',
    'password_reset' => [
        'title' => 'Password Reset Notification',
        'greeting' => 'Dear :name,',
        'intro_1' => 'We received a password reset request for your account.',
        'intro_2' => 'To ensure your account security, please click the button below to reset your password:',
        'reset_button' => 'Reset Password',
        'expires_info' => 'Important Notice: This reset link will expire in 1 hour. Please complete the password reset as soon as possible.',
        'alternative_title' => 'Cannot click the button?',
        'alternative_text' => 'If the button above does not work, please copy and paste the following link into your browser address bar:',
        'security_title' => 'Security Reminder:',
        'security_1' => 'If you did not request a password reset, please ignore this email',
        'security_2' => 'Please do not share this reset link with others',
        'security_3' => 'This reset link can only be used once',
        'footer_contact' => 'If you have any questions, please contact our customer service team',
        'footer_home' => 'Back to Home',
        'footer_service' => 'Customer Service',
        'footer_note' => 'This email is automatically sent by the system, please do not reply'
    ]
];