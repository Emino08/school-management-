<?php
/**
 * Welcome Email Template
 * Variables available: $name, $role, $email, $temp_password, $login_url
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
    <title>Welcome to <?php echo htmlspecialchars($schoolName); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <img src="<?php echo $logoUrl; ?>" alt="<?php echo htmlspecialchars($schoolName); ?>" style="max-width: 180px; height: auto; margin-bottom: 20px; background-color: white; padding: 15px; border-radius: 10px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">Welcome to <?php echo htmlspecialchars($schoolName); ?>!</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Hello <strong><?php echo htmlspecialchars($name); ?></strong>,
                            </p>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                🎉 Congratulations! Your account has been successfully created. We're excited to have you as part of our school community!
                            </p>
                            
                            <!-- Account Details -->
                            <div style="background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%); border-radius: 8px; padding: 25px; margin: 30px 0;">
                                <h3 style="color: #667eea; margin: 0 0 20px; font-size: 18px;">Your Account Details</h3>
                                <table width="100%" cellpadding="8" cellspacing="0">
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; font-weight: 600; padding: 8px 0;">Role:</td>
                                        <td style="color: #333333; font-size: 14px; padding: 8px 0;">
                                            <span style="background-color: #667eea; color: white; padding: 4px 12px; border-radius: 20px; font-weight: 600;">
                                                <?php echo htmlspecialchars($role); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; font-weight: 600; padding: 8px 0;">Email:</td>
                                        <td style="color: #333333; font-size: 14px; padding: 8px 0;"><?php echo htmlspecialchars($email); ?></td>
                                    </tr>
                                    <?php if ($temp_password): ?>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; font-weight: 600; padding: 8px 0;">Temporary Password:</td>
                                        <td style="color: #333333; font-size: 14px; padding: 8px 0;">
                                            <code style="background-color: #f8f9fa; padding: 6px 12px; border-radius: 4px; font-family: 'Courier New', monospace; border: 1px dashed #dee2e6;">
                                                <?php echo htmlspecialchars($temp_password); ?>
                                            </code>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            
                            <?php if ($temp_password): ?>
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px 20px; margin: 30px 0; border-radius: 4px;">
                                <p style="color: #856404; font-size: 14px; margin: 0; line-height: 1.5;">
                                    ⚠️ <strong>Important:</strong> Please change your temporary password after your first login for security reasons.
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Login Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="<?php echo htmlspecialchars($login_url); ?>" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                                            Login to Your Account
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 15px 20px; margin: 30px 0; border-radius: 4px;">
                                <p style="color: #555555; font-size: 14px; margin: 0 0 10px; font-weight: 600;">
                                    Login URL:
                                </p>
                                <p style="color: #667eea; font-size: 13px; margin: 0; word-break: break-all;">
                                    <?php echo htmlspecialchars($login_url); ?>
                                </p>
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
