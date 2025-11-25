<?php

echo "\n═══════════════════════════════════════════════\n";
echo "  PASSWORD CHARACTER ANALYSIS\n";
echo "═══════════════════════════════════════════════\n\n";

$password = '32770&Sabi';

echo "Original Password: $password\n";
echo "Length: " . strlen($password) . " characters\n\n";

echo "Character Breakdown:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
for ($i = 0; $i < strlen($password); $i++) {
    $char = $password[$i];
    $ascii = ord($char);
    $hex = dechex($ascii);
    echo "Position $i: '$char' (ASCII: $ascii, Hex: 0x$hex)\n";
}
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Special Characters Detected:\n";
if (strpos($password, '&') !== false) {
    echo "  ⚠️  Ampersand (&) at position " . strpos($password, '&') . "\n";
    echo "     This character should work fine in SMTP authentication\n";
}

echo "\n═══════════════════════════════════════════════\n";
echo "  TROUBLESHOOTING STEPS\n";
echo "═══════════════════════════════════════════════\n\n";

echo "1. VERIFY PASSWORD IN HOSTINGER EMAIL CONTROL PANEL\n";
echo "   - Log into your HOSTINGER Email account\n";
echo "   - Check if you can login with these credentials\n";
echo "   - Password: $password\n\n";

echo "2. CHECK SMTP SETTINGS IN HOSTINGER\n";
echo "   - Verify SMTP is enabled for this mailbox\n";
echo "   - Check if 'Less secure apps' or 'App passwords' are required\n";
echo "   - Some providers require app-specific passwords\n\n";

echo "3. ALTERNATIVE: CREATE APP PASSWORD\n";
echo "   - HOSTINGER Email may require an app-specific password\n";
echo "   - Check HOSTINGER dashboard for 'App Passwords' or 'SMTP Access'\n\n";

echo "4. CHECK ACCOUNT STATUS\n";
echo "   - Ensure account is active and not suspended\n";
echo "   - Verify billing/subscription is current\n\n";

echo "5. TRY PORT 587 WITH TLS\n";
echo "   - Some providers prefer port 587 with STARTTLS\n";
echo "   - Port 465 uses implicit SSL\n";
echo "   - Port 587 uses explicit TLS (STARTTLS)\n\n";

echo "═══════════════════════════════════════════════\n\n";

