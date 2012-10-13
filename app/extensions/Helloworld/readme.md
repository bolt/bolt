Hello, World!
=============

"Hello, World!" is a small sample extension to display a short greeting in your templates. Use it by simply placing the following in your template:

    {{ helloworld() }}

You can customize the output by including a name or variable:

    {{ helloworld("Bob") }}

    {{ helloworld(record.user.displayname)}}

The default greeting is "world". You can customize it by editing the (very simple) `config.yml` file.

    name: Stranger

