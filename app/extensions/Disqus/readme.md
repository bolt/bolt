Disqus
======

The "Disqus" extension inserts a Disqus comment thread
in your templates. Use it by simply placing the following in your template:

    {{ disqus() }}

To include the current page's title, pass it as a parameter:

    {{ disqus( record.title ) }}

In your overview and listing pages, you can include a link to the comments, where the 'Comment'
text will be replaced with the actual amount of comments.

    <a href="{{ disquslink(record.link) }}">Comment</a>

This is assuming `record` is the record. If not, replace it with the appropriate variable name.
