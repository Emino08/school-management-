<?php
/**
 * Test Password Reset Email Template
 * This file lets you preview the password reset email in a browser
 */

// Mock the Mailer class settings
$settings = [
    'from_name' => 'Bo School',
    'from_email' => 'noreply@boschool.com'
];

// Mock data for testing
$name = 'John Doe';
$reset_url = 'http://localhost:5173/reset-password?token=sample_token_12345';
$reset_token = 'sample_token_12345';
$expires_in = '1 hour';

// Create a mock $this object to simulate Mailer context
$mockMailer = new class($settings) {
    public $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
    }
};

// Set $this to our mock object
$this = $mockMailer;

// Include the template
include __DIR__ . '/src/Templates/emails/password-reset.php';
?>
