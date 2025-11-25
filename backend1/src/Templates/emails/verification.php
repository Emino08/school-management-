<?php
/**
 * Email Verification Template
 * Variables available: $name, $verify_url, $verification_token
 */

$schoolName = $this->settings['from_name'] ?? 'Bo School';
$logoUrl = ($_ENV['APP_URL'] ?? 'http://localhost:8000') . '/Bo-School-logo.png';
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 40px 30px; text-align: center;">
                            <img src="<?php echo $logoUrl; ?>" alt="<?php echo htmlspecialchars($schoolName); ?>" style="max-width: 180px; height: auto; margin-bottom: 20px; background-color: white; padding: 15px; border-radius: 10px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">Verify Your Email</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Hello <strong><?php echo htmlspecialchars($name); ?></strong>,
                            </p>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                Thank you for registering with <?php echo htmlspecialchars($schoolName); ?>! To complete your registration and access all features, please verify your email address by clicking the button below:
                            </p>
                            
                            <!-- Verify Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="<?php echo htmlspecialchars($verify_url); ?>" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);">
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 15px 20px; margin: 30px 0; border-radius: 4px;">
                                <p style="color: #555555; font-size: 14px; margin: 0 0 10px; font-weight: 600;">
                                    Button not working? Copy and paste this link into your browser:
                                </p>
                                <p style="color: #28a745; font-size: 13px; margin: 0; word-break: break-all;">
                                    <?php echo htmlspecialchars($verify_url); ?>
                                </p>
                            </div>
                            
                            <p style="color: #555555; font-size: 15px; line-height: 1.6; margin: 30px 0 20px;">
                                If you didn't create an account with us, please ignore this email.
                            </p>
                            
                            <p style="color: #777777; font-size: 14px; line-height: 1.6; margin: 30px 0 0;">
                                Best regards,<br>
                                <strong style="color: #28a745;"><?php echo htmlspecialchars($schoolName); ?> Team</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; font-size: 13px; margin: 0 0 10px;">
                                This email was sent to you by <?php echo htmlspecialchars($schoolName); ?>
                            </p>
                            <p style="color: #6c757d; font-size: 12px; margin: 0;">
                                &copy; <?php echo $currentYear; ?> <?php echo htmlspecialchars($schoolName); ?>. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
