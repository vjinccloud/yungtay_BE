<?php

return [
    // General success messages
    'success' => [
        'created' => 'Created successfully',
        'updated' => 'Updated successfully',
        'deleted' => 'Deleted successfully',
        'saved' => 'Saved successfully',
        'sent' => 'Sent successfully',
        'completed' => 'Operation completed',
        'processed' => 'Processed successfully',
        'cleared' => 'Cleared successfully',
        'reset' => 'Reset successfully',
        'sorted' => 'Sorted successfully',
        'imported' => 'Imported successfully',
        'exported' => 'Exported successfully',
    ],

    // General error messages
    'error' => [
        'general' => 'Operation failed, please try again later',
        'not_found' => 'The specified data was not found',
        'unauthorized' => 'You do not have permission to perform this action',
        'forbidden' => 'Access denied',
        'validation_failed' => 'Data validation failed',
        'database_error' => 'Database operation failed',
        'network_error' => 'Network connection failed, please check your network',
        'file_error' => 'File processing failed',
        'permission_denied' => 'Insufficient permissions',
        'rate_limit' => 'Too many requests, please try again later',
        'system_maintenance' => 'System under maintenance, please try again later',
    ],

    // Member system messages
    'member' => [
        // Registration related
        'register_success' => 'Registration successful. A verification email has been sent to your inbox, please click the verification link to complete registration.',
        'register_success_no_email' => 'Registration successful, but verification email failed to send, please resend later.',
        'register_failed' => 'Registration failed, please try again later',
        'username_taken' => 'Username is already taken',
        'account_disabled' => 'Account has been disabled, please contact customer service',
        
        // Login related
        'login_success' => 'Login successful',
        'login_failed' => 'Login failed, please try again later',
        'login_invalid_credentials' => 'Invalid email or password',
        'logout_success' => 'Logout successful',
        'logout_failed' => 'Logout failed, please try again later',
        'please_login' => 'Please login first',
        
        // Profile
        'profile_updated' => 'Profile updated successfully',
        'profile_update_failed' => 'Update failed, please try again later',
        'profile_completed' => 'Registration completed! Welcome to SJTV',
        'profile_complete_required' => 'Your profile is already complete, no need to fill again',
        'user_not_found' => 'User does not exist',
        
        // Password reset
        'reset_email_sent' => 'Password reset email has been sent, please check your inbox',
        'reset_email_failed' => 'Email sending failed, please try again later',
        'reset_success' => 'Password reset successful, please login with your new password',
        'reset_failed' => 'Reset failed, please try again later',
        'reset_link_invalid' => 'Invalid or expired reset link, please retry the forgot password process',
        'reset_link_expired' => 'Reset link has expired, please retry the forgot password process',
        'reset_not_found' => 'User with this email not found',
        'reset_limit_exceeded' => 'Daily request limit reached, please try again tomorrow',
    ],

    // Password reset
    'password_reset' => [
        'link_sent_success' => 'Password reset link has been sent to your email. Please check your inbox and click the link to reset your password. The link is valid for 1 hour.',
        'link_send_failed' => 'Failed to send password reset link, please try again later.',
        'reset_success' => 'Password reset successful! Please login with your new password',
        
        // Email verification
        'email_verification_sent' => 'Verification email has been resent, please check your inbox',
        'email_verification_failed' => 'Failed to send verification email',
        'email_verify_success' => 'Email verification successful',
        'email_verify_failed' => 'Verification failed, please try again later',
        'email_verify_invalid' => 'Invalid or expired verification link',
        'email_verify_expired' => 'Verification link has expired, please request a new verification email',
        
        // Social login
        'social_login_success' => 'Login successful!',
        'social_bind_success' => 'Successfully bound and logged in!',
        'social_register_success' => 'Registration successful! Please complete your profile to use member features',
        'social_login_failed' => 'An error occurred during login, please try again later',
        'social_unbind_success' => 'Unbound successfully',
        'social_unbind_failed' => 'Unbind failed, please try again later',
        'social_account_not_found' => 'Account to unbind not found',
        'social_auth_cancelled' => 'User cancelled authorization or authorization failed',
    ],

    // Content management messages
    'content' => [
        'not_found' => 'Cannot find the',
        'video_not_found' => 'Cannot find the video',
        'episode_delete_success' => 'Deleted successfully',
        'episode_delete_failed' => 'Delete failed',
        'sort_success' => 'Sort updated successfully',
        'sort_failed' => 'Sort failed',
        'batch_delete_success' => 'Successfully deleted :count items',
        'batch_delete_failed' => 'Batch delete failed',
        'subcategory_delete_success' => 'Subcategory deleted successfully',
        'module_delete_success' => 'Deleted successfully',
    ],

    // View statistics messages
    'view' => [
        'record_success' => 'View recorded successfully',
        'record_failed' => 'Failed to record view',
        'cleanup_success' => 'Cleanup completed',
        'cleanup_failed' => 'Cleanup failed',
        'reset_success' => 'Daily view count reset completed',
        'reset_failed' => 'Reset failed',
        'ranking_update_success' => 'Ranking update completed',
        'ranking_update_failed' => 'Ranking update failed',
        'ranking_cleanup_success' => 'Old ranking cleanup completed',
        'ranking_cleanup_failed' => 'Cleanup failed',
    ],

    // System management messages
    'system' => [
        'dashboard_update_success' => 'Data update completed (with quick sync)',
        'dashboard_update_failed' => 'Failed to update data',
        'dashboard_recalculate_success' => 'Recalculation completed! Expired data cleaned and Redis synced',
        'dashboard_recalculate_failed' => 'Recalculation failed',
        'role_delete_failed' => 'Delete failed, this role permission is in use',
        'role_delete_success' => 'Deleted successfully',
        'news_type_save_success' => 'Submitted successfully',
        'news_type_delete_success' => 'Deleted successfully',
        'cache_cleared' => 'Cache cleared',
        'maintenance_mode_on' => 'Maintenance mode enabled',
        'maintenance_mode_off' => 'Maintenance mode disabled',
    ],

    // Page titles
    'page_title' => [
        'member_login' => 'Member Login',
        'member_register' => 'Member Registration',
        'member_center' => 'Member Center',
        'watch_history' => 'Watch History',
        'view_statistics' => 'View Statistics',
        'complete_profile' => 'Complete Registration',
        'email_verification' => 'Email Verification',
        'forgot_password' => 'Forgot Password',
        'reset_password' => 'Reset Password',
        'drama_type' => 'Video',
        'program_type' => 'Program',
    ],

    // Status messages
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
        'verified' => 'Verified',
        'unverified' => 'Unverified',
    ],
];