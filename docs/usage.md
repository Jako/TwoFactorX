## Manager

When the system setting `twofactorx.enable_2fa` is enabled, the manager login
asks for an additional authentication code.

### Verifying the authenticator

A manager user without a verified authenticator can log in without an
authentication code. Afterwards, a window shows the QR code and the secret each
time the manager is loaded. The user scans the QR code with an authentication
application (i.e. Google Authenticator, Authy or 1Password) and enters the
displayed authentication code in the window to verify it. From then on, each
manager login needs an authentication code.

### Authentication codes

* Each authentication code can be used only once.
* A wrong authentication code counts as failed login attempt, if the password is
  correct. After `failed_login_attempts` failed attempts, the user is blocked for
  `blocked_minutes`, like after wrong passwords. This applies to the verify window
  as well.

### TwoFactorX tab

Manager users with the `twofactorx_edit` permission can manage the two-factor
authentication of a user in the TwoFactorX tab of the user edit page. The tab is
shown after a new user is saved.

| Button                     | Description                                                                                               |
|----------------------------|-----------------------------------------------------------------------------------------------------------|
| Change Status              | Enable or disable the two-factor authentication for the user.                                             |
| Reset Secret               | Create a new secret. The user has to verify the new secret with the next manager login.                   |
| Email Instructions To User | Send an email with the setup instructions to the user.                                                    |
| Email QR Code To User      | Send the QR code and the secret to the user by email. This is the least secure way to provide the secret. |

### User profile

If the system setting `twofactorx.show_in_profile` is enabled, manager users can
see their QR code and their secret in their user profile.

## Snippets

There are two snippets included in the package.

### TwoFactorXLogin

This pre-hook secures the login of the "Login" extra with a two-factor
authentication:

```
[[!Login?
&preHooks=`TwoFactorXLogin`
]]
```

The hook checks the authentication code of each user, unless the two-factor
authentication is disabled for that user. The system setting
`twofactorx.enable_2fa` does not change that. Because the front end has no
verify window, the users have to save the secret in their authentication
application before, i.e. with the UserQRcode snippet or in the manager.

It uses the following properties:

| Property           | Description                                                           | Default |
|--------------------|-----------------------------------------------------------------------|---------|
| twofactorxErrorMsg | Alternative error message, if the authentication code does not match. | -       |

The authentication code for the two-factor authentication is requested in the
field with the name `code`. If you add the attribute
`autocomplete="one-time-code"` to the input, tools like 1Password will fill the
field automatically.

### UserQRcode

This snippet returns the SVG of a two-factor authentication QR code. It uses the
following properties:

| Property          | Description                                         | Default         |
|-------------------|-----------------------------------------------------|-----------------|
| placeholderPrefix | The prefix for the placeholders set by the snippet. | twofactorx      |
| userid            | The ID of the user the QR code is created for.      | Current user ID |

The following placeholders are set by the snippet (with the default prefix):

| Placeholder       | Description                                                                                               |
|-------------------|-----------------------------------------------------------------------------------------------------------|
| twofactorx.secret | The secret used to create a time-based one-time password (TOTP) in an authentication application.         |
| twofactorx.uri    | The URI used to create the QR code for an authentication application.                                     |
| twofactorx.qrsvg  | The HTML code of an SVG that displays the QR code that can be scanned with an authentication application. |

## System Settings

TwoFactorX uses the following system settings in the namespace `twofactorx`.

| Key                        | Name                             | Description                                                                                                            | Default     |
|----------------------------|----------------------------------|------------------------------------------------------------------------------------------------------------------------|-------------|
| twofactorx.debug           | Debug                            | Log debug information in the MODX error log.                                                                           | No          |
| twofactorx.enable_2fa      | Enable Two-Factor Authentication | If you enable the two-factor authentication, the manager login is secured with an additional TOTP authentication code. | No          |
| twofactorx.encryption_key  | Encryption Key                   | Encryption key that is used for the encryption of the 2FA data. Do not change.                                         | -           |
| twofactorx.issuer          | QR Code Issuer                   | Specify the value of the issuer in the QR code.                                                                        | `site_name` |
| twofactorx.show_in_profile | Show Secret In User Profile      | Allow manager users to see the QR code and the secret for two-factor authentication in their user profile.             | No          |

### Encryption key

The encryption key is generated during the installation. Don't change it,
because the stored secrets can't be decrypted with another key.

If the encryption key is missing or invalid while users have stored secrets,
TwoFactorX does not generate a new key. Instead, it refuses all logins that need
an authentication code and logs an error. Restore the previous key in the system
setting to fix this, or disable the system setting `twofactorx.enable_2fa`
meanwhile to allow manager logins without an authentication code.

## Permissions

TwoFactorX has the following permissions for manager users:

| Permission      | Description                                                       |
|-----------------|-------------------------------------------------------------------|
| twofactorx_edit | Allow a user to manage the TwoFactorX data in the user edit page. |

The permission is part of the `TwoFactorX` access policy. Assign this policy to
a user group in the `mgr` context to grant the permission. The permission check
is not executed for sudo users.
