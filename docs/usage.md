## Snippets

There are two snippets included in the package.

### TwoFactorXLogin

This pre-hook can be used ass two-factor authentication pre-hook to secure the
login snippet. It uses the following properties:

| Property           | Description                                                           | Default |
|--------------------|-----------------------------------------------------------------------|---------|
| twofactorxErrorMsg | Alternative error message, if the authentication code does not match. | -       |

The authentication code for the two-factor authentication is requested in the
field with the name `code`. If you add the attribute
`autocomplete="one-time-code"` to the input, tools like 1Password will fill the
field automatically.

### UserQRcode

This snippet creates a two-factor authentication QR code. It uses the following properties:

| Property          | Description                                         | Default         |
|-------------------|-----------------------------------------------------|-----------------|
| placeholderPrefix | The prefix for the placeholders set by the snippet. | twofactorx      |
| userid            | The id of the user the QR code is created for.      | Current user id |

The following additional placeholders are set by the snippet:

| Placeholder | Description                                                                                               | 
|-------------|-----------------------------------------------------------------------------------------------------------|
| secret      | The secret used to create a time-based one-time password (TOTP) in an authentication application.         |
| uri         | The URI used to create the QR code for an authentication application.                                     |
| qrsvg       | The HTML code of an SVG that displays the QR code that can be scanned with an authentication application. |

## System Settings

TwoFactorX uses the following system settings in the namespace `twofactorx`.

| Key                        | Name                             | Description                                                                                                            | Default |
|----------------------------|----------------------------------|------------------------------------------------------------------------------------------------------------------------|---------|
| twofactorx.debug           | Debug                            | Log debug information in the MODX error log.                                                                           | No      |
| twofactorx.enable_2fa      | Enable Two-Factor Authentication | If you enable the two-factor authentication, the manager login is secured with an additional TOTP authentication code. | No      |
| twofactorx.enable_onetime  | Enable One-Time Login            | If you enable one-time login, users are allowed to log in once to retrieve their secret.                               | Yes     |
| twofactorx.encryption_key  | Encryption Key                   | Encryption key that is used for the encryption of the 2FA data. Do not change.                                         | -       |
| twofactorx.issuer          | QR Code Issuer                   | Specify the value of the issuer in the QR code. The default value is the system setting site_name.                     | -       |
| twofactorx.show_in_profile | Show Secret In User Profile      | Allow manager users to see the QR code and the secret for two-factor authentication in their user profile.             | No      |

## Permissions

TwoFactorX has the following permissions for manager users:

| Permission      | Description                                                    |                                                                                                                   
|-----------------|----------------------------------------------------------------|
| twofactorx_edit | Allow a user to manage the TwoFactorX data the user edit page. |

The permission check is not executed for sudo users.
