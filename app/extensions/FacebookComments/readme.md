Disqus
======

The "Disqus" extension inserts a Disqus comment thread
in your templates. Use it by simply placing the following in your template:

    {{ disqus() }}

To include the current page's title, pass it as a parameter:

    {{ disqus( record.title ) }}

