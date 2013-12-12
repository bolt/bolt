PasswordProtect extension
=========================

The "PasswordProtect extension" is a small extension that allows you to password
protect one or more of your pages with a password. Use it by simply placing the
following in your template:

    {{ passwordprotect() }}

You can put this either in your template, to protect an entire contenttype, or just
place it in the body text of a record.

People who do not yet have access will automatically be redirected to a
page, where they will be asked to provide the password.

See `config.yml` for the options.

**Note:** This 'protection' should not be considered 'secure'. The password will be sent
over the internet in plain text, so it can be intercepted if people use it on a
public WiFi network, or something like that.

The 'password' page
-------------------
The page you've set as the `redirect:` option in the `config.yml` can be any Bolt
page you want. It can be a normal page, where you briefly describe why the user was
suddenly redirected here. And, perhaps you can give instructions on how to acquire
the password, if they don't have it. When the user provides the correct password,
they will automatically be redirected back to the page they came from.

To insert the actual form with the password field, simply use:

    {{ passwordform() }}

Like above, you can do this either in the template, or simply in the content of
the page somewhere.

**Tip:** do not be a dumbass and require a login on the page you're redirecting to!
Your visitors will get stuck in an endless loop, if you do.

