Simple Forms
============

The "Simple Forms" extension allows you to insert forms into your templates. Use it by simply placing the following in
your template, with the name of the form to insert:

    {{ simpleform('formname') }}

With SimpleForms you create forms, by defining them in the `config.yml`-file. The file has some general settings, plus
a section with fields for each different form.

General settings
----------------

 - `stylesheet: assets/simpleforms.css` - The CSS to display the forms with. If you want to modify the CSS, you should
    copy the file to your `theme/` folder, and modify it there. Any changes in the file in the distribution might be
    overwritten after an update to the extension.
 - `template: assets/simpleforms_form.twig` - The Twig template to display the forms with. If you want to modify the
    HTML, you should copy the file to your `theme/` folder, and modify it there. Any changes in the file in the
    distribution might be overwritten after an update to the extension. This file uses the
    [Symfony Forms component](http://symfony.com/doc/current/book/forms.html#form-theming).
 - `mail_template: assets/simpleforms_mail.twig` - The Twig template to format the emails with. If you want to modify
    the HTML, you should copy the file to your `theme/` folder, and modify it there. Any changes in the file in the
    distribution might be overwritten after an update to the extension.
 - `message_ok: ...` - The message to display when the form is correctly filled out and was sent to the recipient.
 - `message_error: ...` - The message to display when there's an error in one of the form fields.
 - `message_technical: ...` - The message to display when there's a technical error preventing the sending of the email.
    Most likely this is caused because Swiftmailer can't send the email. Check the Swiftmailer settings in the global
    `config.yml` if this message is shown.
 - `redirect_on_ok: ...` - Instead of simply displaying a message when the form is 'OK', you can also redirect to a different page on the site, for a more extended message after submitting the form. The value should be a `contenttype/id` or `contenttype/slug` pair. For example: `entry/1` or `page/thank-you`.   
 - `button_text: Send` - Default text on the 'send' button in the forms.
 - `recipient_cc_email: info@example.com` - Use this value to set a global cc email address, this email address will receive a copy of
    all emails sent with simpleforms.
 - `recipient_cc_name: Info` - Use this as the display name for the cc email address.
 - `recipient_bcc_email: info@example.com` - Use this value to set a global bcc email address, this email address will receive a blind copy of
    all emails sent with simpleforms - this value does not have a display name.
 - `testmode: true` - Sets a global testmode, you can use this to for development if you do not want other people to be bothered by
    endless testing emails. If you set this value to `true` all email will be sent to the `testmode_recipient` and all other
    recipient and cc addresses will be ignored. The default value is false.
 - `testmode_recipient: info@example.com` - The email where all test emails should go.

**Tip**: If you want to copy one of the template files, you should remember to leave out the `assets/` part. For
instance, if you copy `simpleforms_form.twig` to `theme/base-2013/my_form.twig`, the corresponding line in `config.yml`
should be:

 <pre>template: my_form.twig</pre>

Configuring forms
-----------------
You can define multiple forms, where each form has its own section in the `config.yml` file.
The default file has two forms defined, namely 'contact' and 'demo'. The structure of a form definition is as follows:

<pre>
myformname:
  recipient_email: info@example.org
  recipient_name: Info
  mail_subject: "[Simpleforms] Contact from site"
  fields:
    fieldname:
      type: ..
      ..
    fieldname:
      type: ..
      ..
  button_text: Send the Demo form!
</pre>

Each form has a name, which is used to insert the correct form in your templates. For example, if you've named your
form `myformname`, as in the example above, you can insert the form in your templates using
`{{ simpleform('myformname') }}`. Use the `recipient_email` and `recipient_info` fields to set the recipients of the
emails. Use the `mail_subject` value to set the subject of the confirmation emails. The optional `button_text` can be
used to override the global setting for the text on the 'send' button.

Each of the 'General settings' mentioned above can be overridden for a specific form. So, you can create forms that use
different templates and different messages.

The fields of the form are defined in the `fields`-array. Every field is defined by its name, with its options. For example:

<pre>
    subject:
      type: text
      class: wide
      required: true
      label: Subject of your message
      placeholder: Just a quick message ..
</pre>

Make sure the name (in this example `subject`) is unique to the form. Each of the different fieldtypes has a few options
to modify the functionality or appearance:

  - `class` - The class is passed in the rendered HTML, so it can be used to style the form elements.
  - `required` - Whether or not the field is required to be filled in. If omitted, defaults to `false`.
  - `label` - The textual description of the field on the website, for when the name of the field isn't descriptive enough.
  - `placeholder` - An optional placeholder for the field.
  - `value` - The default value of the fiels in the form.
  - `allow_override` - If set to `true`, it's possible to override the value in this field using a `GET` parameter in
    the URL. For example, if your form has a field `title`, you can override it using `/page/contact/?title=This+is+the+title`.
    The form takes care of escaping the input, to prevent XSS.
  - `read_only` - Set the field to `readonly` in the generated HTML. (yes, we're aware of the inconsistency between
    'read_only' and 'readonly'. 'read_only' is the name of the option in Symfony's Form component, while 'readonly' is
    the name of the attribute in the generated HTML)
  - `prefix` - Add a snippet of HTML to output _before_ the `<div>` with the field's row.
  - `postfix` - Add a snippet of HTML to output _after_ the `<div>` with the field's row.
    You can use these attributes to insert labels, headings or to divide the form in `<fieldset>`'s.
  - `use_as` - Only for email fields, you can use `to_email`, `from_email`, `cc_email` or `bcc_email`
    to use the entered email as an extra address.
  - `use_with: fieldname` - An optional name for an email field. Use this to reference another field, that will be used
    to display the name of the person, used in the `use_as`. Doing this, you can make emails with proper recipients, that
    will be shown as `Example person <info@example.org>`. See the 'Email input with extra recipient' example below.
  - `minlength` - Add HTML5 form validation minimum length input attribute. Browsers that recognize HTML5 form validation
    will not except any input shorter than your entered value. Example: `<input type="text" minlength="5"`
  - `maxlength` - Add HTML5 form validation maxlength to your input attribute. Browsers that recognize HTML5 form
    validation will not except any input longer than your entered value. Example: `<input type="text" maxlength="25"`
  - `autofocus` HTML5 autofocus attribute. On page render the input with `autofocus="on"` will be highlighted. Options
    are `autofocus: on` or `autofocus: off`. Only one form element can have the autofocus attribute. It cannot be
    applied if the type is `hidden`.
  - `expanded: true` Attribute for choice elements, use this in combination with multiple and required to make
    select boxes, radio groups or checkbox groups
  - `multiple: true` Attribute for choice elements, use this in combination with expanded and required to make
    select boxes, radio groups or checkbox groups
  - `autocomplete` HTML5 form attribute that turns the in browser autocomplete function on or off. This is ignored if the
    input type is set to `hidden`.
      * `off`: must explicitly enter a value into this field for every use. the browser does not automatically complete the entry.
      * `on`: The browser can automatically complete the value based on values that the user has entered during previous uses.
  - `pattern` A JavaScript regular expression to check the input field against. This attribute applies to fields with a type
   of `text`, `search`, `tel`, `url` or `email`.
    Example alphanumeric: `<input type="text" pattern="^[a-zA-Z0-9]+" />`


The different fieldtypes are as follows, with a short example outlining the specific options for that field.
Remember you can also use the basic options as well.

**Standard text input:**

    name:
      type: text

**Email input:**

    email:
      type: email

**Text area (multi line input):**

    message:
      type: textarea

**Select box (pulldown):**

    favorite:
      type: choice
      multiple: false
      choices: [ Kittens, Puppies, Penguins, Koala bears, "I don't like animals" ]

**Radio buttons:**

    favorite:
      type: choice
      expanded: true
      required: true
      multiple: false
      choices: [ Kittens, Puppies, Penguins, Koala bears, "I don't like animals" ]

**Checkboxes:**

    favorite:
      type: choice
      expanded: true
      required: false
      multiple: true
      choices: [ Kittens, Puppies, Penguins, Koala bears, "I don't like animals" ]

**Checkbox:**

    option1:
      type: checkbox

**Date input:**

    some_date:
      type: date
      format: "Y-m-d"

The `format` option is used for formatting the date in the emails. You can use the options that are available in
PHP's `date()` function. See the [documentation for details](http://php.net/date).

Using ReCaptcha field:
----------------------

To protect your forms from spam-bots, you can enable the ReCaptcha service. This lets the visitors type out two words or
numbers from a picture, to prove that they're human. To enable ReCaptcha, simply enable/fill all the <code>recaptcha_</code>
fields in <code>config.yml</code>. If you don't have a private/public keypair yet, go to
[this URL](https://www.google.com/recaptcha/admin/create) to create them.

Overriding values
-----------------

Override values from the template

    {{ simpleform('formname', { 'somefield' : somevalue } ) }}

This will prefill the field named `somefield` with `somevalue`. This is always available but it will be overridden by `$_GET['somefield']` if `allow_override: true` is set for `somefield`.


Email input with extra recipient:
---------------------------------

If you want to send a copy of the an email address the visitor entered, you can use the `use_as` and
`use_with` options for email and text fields.

You can define as many email fields as you like and the addresses will be used, you need to add the
`use_with` option for each field if you want nice display names.

    recipient:
      type: email
      use_as: to_email|from_email|cc_email|bcc_email
      use_with: another_fieldname
    another_fieldname:
      type: text
      label: "The name of the person this email is sent to"

Upload:
-------

Uploads are special, complicated and unsafe.

<pre>
myformname:
  recipient_email: info@example.org
  recipient_name: Info
  fields:
    upload:
      type: file
      storage_location: directoryname
      filetype:
        ext1: txt
        ext2: zip
        ext3: docx
</pre>

The storage_location must be set for a field definition, and a writable directory with the corresponding directoryname
must be created inside your files directory.

You can also set the storage location to 'false' and the files will be temporarily stored in your cache folder.
Use this if you want to attach the files to your emails but do not weant to keep a long term storage for them.

If you set the `attach_files` option in the form definition, uploaded files will be attached to the email.
If you do not set `attach_files` you will only get a link in your email.

<pre>
myformname:
  recipient_email: info@example.org
  recipient_name: Info
  attach_files: true
  fields:
    fieldname:
      type: ..
      ..
    upload:
      type: file
      storage_location: false
      filetype:
        ext1: txt
        ext2: zip
        ext3: docx
      ..
  button_text: Send the Demo form!
</pre>


Save to database:
-----------------

There is an option to keep a logfile in the database of all form submissions.
For this log you need to make a table with columns named after the fieldnames in the form and set the `insert_into_table`
for the form to the tablename. This extension will not automatically create the table, and it will produce an error if
the table isn't present or the columns don't line up.

<pre>
myformname:
  recipient_email: info@example.org
  recipient_name: Info
  insert_into_table: tablename
  fields:
    fieldname:
      type: ..
      ..
    fieldname:
      type: ..
      ..
  button_text: Send the Demo form!
</pre>

**Tip:** If you want to include the current date and time into the database, set the 'DEFAULT' of the field to 'CURRENT_TIMESTAMP':

<pre>
CREATE TABLE `notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  ..
  ..
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
</pre>


special log fields
------------------

There are a few field types for logging ip-addresses and other user values

**Remote address**

    log_ip:
      type: ip

This will fetch the remote IP address and even try to look through proxies for it.

**Remote host**

    log_remotehost:
      type: remotehost

This will attempt to lookup the remote hostname, or just give you an empty string.

**Remote user agent**

    log_useragent:
      type: useragent

This will return the browser's user agent string.

**Timestamp**

    log_timestamp:
      type: timestamp

This returns the current timestamp.

Sequences
---------

By popular demand (Hello Peter) there is also a `sequence` option for your field. With this foption you can tell the visitor his or her number in an email.

It has some dependencies.

  - The results must be saved to a table (because there needs to be a record somewhere)
  - There must be a numerical field in the table (you want this)
  - The field must probably be hidden in the form
  - The role must be set to 'sequence' in the `config.yml`

The code to make a sequence field in a form would be:

<pre>
myformname:
  recipient_email: info@example.org
  recipient_name: Info
  insert_into_table: tablename
  fields:    
    recipient:
      type: email
      use_as: to_email
      label: "Your email address"
    sequence:
      label: "Next number"
      type: hidden
      role: sequence
</pre>

This form would count every submission and save the number in the database.