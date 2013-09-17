TitleCase filter extension
==========================

This extension adds a `titlecase` filter to your templates. Example:

    Normal: "{{ record.title }}"<br>
    Titlecase'd: "{{ record.title|titlecase }}"

Output:

    Normal: "this is the title of the page."
    Titlecase'd: "This is the Title of the Page."
