<!doctype html>
<html>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color:#222; max-width:480px; margin:0 auto; padding:24px;">
  <h2>You've been invited to CRX Farm</h2>
  <p>Hi {{ $user->name }},</p>
  <p>
    You've been set up as an admin on the CRX Farm parts catalog. Follow the
    link below to finish setting up your account — you'll pick a 6-digit PIN,
    then set up a passkey (Face ID / Touch ID / Windows Hello / security key)
    on this device. The passkey is how you'll sign in from now on; the PIN is
    only a fallback.
  </p>
  <p>
    <a href="{{ $signedUrl }}" style="display:inline-block; background:#1c3b2e; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none;">
      Finish setting up your account
    </a>
  </p>
  <p style="color:#666; font-size:13px;">This link expires in 48 hours and can only be used once.</p>
  <p>Thanks,<br>CRX Farm</p>
</body>
</html>
