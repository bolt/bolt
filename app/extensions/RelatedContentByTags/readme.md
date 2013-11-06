RelatedContentByTags
====================

Retrieves a sorted array of similar content based on taxonomies that _behave-like_ tags, see `app/config/taxonomy.yml`.


Usage
-----

Default usage:

    {{ relatedcontentbytype(record) }}

Add options for more flexibility:

    {{ relatedcontentbytags(record, { 'limit' : 5, 'pointsTag' : 5, 'pointsType': 100 }) }}

Default values are defined in `config.yml`. Use these options to override these settings.

By default, this extensions searches through all available contenttypes. Use `contenttypes` in `options` to filter specific contenttypes:

    {{ relatedcontentbytags(record, { 'contenttypes' : ['kitchensinks', 'snippets', '' ] }) }}

Non-existing contenttypes will be ignored.


Example
-------

The results from this extension are similar to how listings are handled in Bolt.
Add the following in your template for a simple example.

    {% for item in relatedcontentbytags(record) %}
        <p><a href="{{ item.link }}">{{ item.title|e }}</a></p>
    {% endfor %}


Options
-------

See `config.yml` for more information. Options include:

* `limit`        : the maximum number of results returned
* `pointsTag`    : points per equal tag
* `pointsType`   : points if contenttypes are equal
* `contenttypes` : an array of contenttypes to search for.


Notes
-----

Currently, this extension checks all tags-taxonomies and treats them as equal.
A possible feature could be to add weight per contenttype and per taxonomy-type;
or perhaps per tag (value). Or to ignore certain taxonomies. The desired
behaviour would be dependant on the application, so no assumptions have been
made (yet).