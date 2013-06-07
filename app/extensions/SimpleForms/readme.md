Simple Forms
============

The "Simple Forms" extension allows you to insert forms into your templates. Use it by simply placing the following in your template, with the name of the form to insert:

    {{ simpleform('formname') }}

With SimpleForms you create forms, by defining them in the `config.yml`-file. The file has some general settings, plus a section with fields for each different form.

General settings
----------------

 - `stylesheet: assets/simpleforms.css` - The CSS to display the forms with. If you want to modify the CSS, you should copy the file to your `theme/` folder, and modify it there. Any changes in the file in the distribution might be overwritten after an update to the extension.
 - `template: assets/simpleforms_form.twig` - The Twig template to display the forms with. If you want to modify the HTML, you should copy the file to your `theme/` folder, and modify it there. Any changes in the file in the distribution might be overwritten after an update to the extension. This file uses the [Symfony Forms component](http://symfony.com/doc/current/book/forms.html#form-theming).
 - `mail_template: assets/simpleforms_mail.twig` - The Twig template to format the emails with. If you want to modify the HTML, you should copy the file to your `theme/` folder, and modify it there. Any changes in the file in the distribution might be overwritten after an update to the extension.
 - `message_ok: ...` - The message to display when the form is correctly filled out and was sent to the recipient.
 - `message_error: ...` - The message to display when there's an error in one of the form fields.
 - `message_technical: ...` - The message to display when there's a technical error preventing the sending of the email. Most likely this is caused because Swiftmailer can't send the email. Check the Swiftmailer settings in the global `config.yml` if this message is shown.
 - `button_text: Send` - Default text on the 'send' button in the forms.

**Tip**: If you want to copy one of the template files, you should remember to leave out the `assets/` part. For instance, if you copy `simpleforms_form.twig` to `theme/base-2013/my_form.twig`, the corresponding line in `config.yml` should be:

 <pre>template: my_form.twig</pre>

Configuring forms
-----------------
You can define multiple forms, where each form has its own section in the `config.yml` file. The default file has two forms defined, namely 'contact' and 'demo'. The structure of a form definition is as follows:

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

The fields of the form are defined in the 'fields'-array. Every field is defined by its name, with its options. For example:

<pre>
    subject:
      type: text
      class: wide
      required: true
      label: Subject of your message
      placeholder: Just a quick message ..
</pre>

Make sure the name (in this example `subject`) is unique to the form. Each of the different fieldtypes has a few options to modify the functionality or appearance:

  - `class` - The class is passed in the rendered HTML, so it can be used to style the form elements.
  - `required` - Whether or not the field is required to be filled in. If omitted, defaults to `false`.
  - `label` - The textual description of the field on the website, for when the name of the field isn't descriptive enough.
  - `placeholder` - An optional placeholder for the field.

The different fieldtypes are as follows, with a short example outlining the specific options for that field. Remember you can also use the basic options as well.

**Standard text input:**

    name:
      type: text

**Email input**

    email:
      type: email

**Text area (multi line input)**

    message:
      type: textarea

**Select box (pulldown)**

    favorite:
      type: choice
      choices: [ Kittens, Puppies, Penguins, Koala bears, "I don't like animals" ]

**Checkbox**

    option1:
      type: checkbox


