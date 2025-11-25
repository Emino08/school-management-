<?php
/**
 * Password Reset Email Template
 * Variables available: $name, $reset_url, $reset_token, $expires_in
 */

$schoolName = $this->settings['from_name'] ?? 'BoSchool';
$logoUrl = isset($_ENV['APP_URL']) ? rtrim($_ENV['APP_URL'], '/') . '/Bo-School-logo.png' : (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/Bo-School-logo.png' : 'https://via.placeholder.com/180x80/667eea/ffffff?text=BoSchool');
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    
                    <!-- Header with Logo -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <img src="<?php echo $logoUrl; ?>" alt="<?php echo htmlspecialchars($schoolName); ?>" style="max-width: 180px; height: auto; margin-bottom: 20px; background-color: white; padding: 15px; border-radius: 10px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">Password Reset Request</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Hello <strong><?php echo htmlspecialchars($name); ?></strong>,
                            </p>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                We received a request to reset your password for your <?php echo htmlspecialchars($schoolName); ?> account. Click the button below to create a new password:
                            </p>
                            
                            <!-- Reset Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="<?php echo htmlspecialchars($reset_url); ?>" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;">
                                            Reset Your Password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Alternative Link -->
                            <div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 15px 20px; margin: 30px 0; border-radius: 4px;">
                                <p style="color: #555555; font-size: 14px; margin: 0 0 10px; font-weight: 600;">
                                    Button not working? Copy and paste this link into your browser:
                                </p>
                                <p style="color: #667eea; font-size: 13px; margin: 0; word-break: break-all;">
                                    <?php echo htmlspecialchars($reset_url); ?>
                                </p>
                            </div>
                            
                            <!-- Security Notice -->
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px 20px; margin: 30px 0; border-radius: 4px;">
                                <p style="color: #856404; font-size: 14px; margin: 0; line-height: 1.5;">
                                    ⏱️ <strong>Important:</strong> This password reset link will expire in <strong><?php echo htmlspecialchars($expires_in); ?></strong> for security reasons.
                                </p>
                            </div>
                            
                            <p style="color: #555555; font-size: 15px; line-height: 1.6; margin: 30px 0 20px;">
                                If you didn't request a password reset, please ignore this email or contact our support team if you have concerns. Your password will remain unchanged.
                            </p>
                            
                            <!-- Tips Section -->
                            <div style="background-color: #e8f4f8; border-radius: 8px; padding: 20px; margin: 30px 0;">
                                <p style="color: #0c5460; font-size: 14px; margin: 0 0 10px; font-weight: 600;">
                                    🔐 Security Tips:
                                </p>
                                <ul style="color: #0c5460; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
                                    <li>Choose a strong password with at least 8 characters</li>
                                    <li>Include uppercase, lowercase, numbers, and symbols</li>
                                    <li>Don't reuse passwords from other accounts</li>
                                    <li>Never share your password with anyone</li>
                                </ul>
                            </div>
                            
                            <p style="color: #777777; font-size: 14px; line-height: 1.6; margin: 30px 0 0;">
                                Best regards,<br>
                                <strong style="color: #667eea;"><?php echo htmlspecialchars($schoolName); ?> Team</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; font-size: 13px; margin: 0 0 10px; line-height: 1.5;">
                                This email was sent to you by <?php echo htmlspecialchars($schoolName); ?>
                            </p>
                            <p style="color: #6c757d; font-size: 12px; margin: 0; line-height: 1.5;">
                                &copy; <?php echo $currentYear; ?> <?php echo htmlspecialchars($schoolName); ?>. All rights reserved.
                            </p>
                            <div style="margin-top: 15px;">
                                <a href="#" style="color: #667eea; text-decoration: none; font-size: 12px; margin: 0 10px;">Privacy Policy</a>
                                <span style="color: #dee2e6;">|</span>
                                <a href="#" style="color: #667eea; text-decoration: none; font-size: 12px; margin: 0 10px;">Terms of Service</a>
                                <span style="color: #dee2e6;">|</span>
                                <a href="#" style="color: #667eea; text-decoration: none; font-size: 12px; margin: 0 10px;">Contact Support</a>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <!-- Footer Note -->
                <table width="600" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td style="text-align: center; padding: 0 30px;">
                            <p style="color: #999999; font-size: 12px; line-height: 1.5; margin: 0;">
                                This is an automated message. Please do not reply to this email.<br>
                                If you need assistance, please contact our support team.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
