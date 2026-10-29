HEAD
# Basic PHP Authentication

A small user-authentication project built with core PHP, HTML, CSS, MySQL, and PDO. It has no PHP framework or JavaScript dependency.

## Requirements

- PHP 8.1 or later with `pdo_mysql` and `mbstring` enabled
- MySQL or MariaDB
- Apache (XAMPP is suitable)

## Install with XAMPP

1. Copy the `basic-auth-project` folder into XAMPP's `htdocs` directory. The default local URL is `http://localhost/basic-auth-project/`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, choose **Import**, select `database.sql`, and run the import. The SQL creates `basic_auth_project`, `users`, and `password_resets`.
4. Open `config/database.php`. Its defaults are for a local XAMPP MySQL instance (`127.0.0.1`, database `basic_auth_project`, user `root`, empty password). For another setup, set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in the Apache/PHP environment. Do not commit real credentials; use environment variables and a least-privilege database account outside local development.
5. Keep `APP_ENV` set to `development` and `APP_BASE_URL` set to the URL where this project is served. Apache denies HTTP access to `storage/`, and `.gitignore` excludes the reset-link log.

To import from a terminal instead, run `mysql -u root -p < database.sql` from this project directory (omit `-p` if the local MySQL root account has no password).

## Run and test

1. Visit `http://localhost/basic-auth-project/` and create an account. Passwords must be at least 12 characters.
2. Log in with that account. The profile shows the name, email, and creation time.
3. Use **Edit profile** to change the name or email. Try an email already used by another account to confirm it is rejected.
4. Log out, then try opening `profile.php` directly; it should send you to the login page.
5. On the login page, open **Forgot password?** and submit the registered email. The response is the same for registered and unregistered addresses. In development, the generated one-hour reset link is appended to `storage/reset-links.log`; open it locally to complete the reset. The log contains a usable secret link, so never publish or share it.
6. Submit a new password and confirmation. Reusing the same link should fail. Log in with the new password.

The reset-link log is only written when `APP_ENV === 'development'`. For production, set `APP_ENV` to `production` and connect a mail-delivery service in the forgot-password flow; do not expose reset links in a web response or log them to a publicly accessible location. The project intentionally does not send email by itself.

## Security notes

- Every SQL query uses PDO prepared statements; account passwords use `password_hash()` and `password_verify()`.
- Reset tokens are generated with `random_bytes()`. Only SHA-256 token hashes are stored in MySQL; links expire after one hour and are single-use.
- Session cookies use `HttpOnly`, `SameSite=Lax`, strict session mode, and `Secure` on HTTPS. Successful login regenerates the session ID.
- Forms that change state use CSRF tokens. Output is escaped with `htmlspecialchars()`.
- Profile updates use the logged-in session's user ID; ID, password, and creation date are not editable in that form.
- Use HTTPS, production database credentials, error logging, and an actual email provider before deploying publicly. Disable development reset-link logging in production.

