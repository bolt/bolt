RelatedContentByTags
====================

Retrieves a sorted array of similar content based on taxonomies that have tag-like-behavior.

#### Usage

Default usage:

    {{ relatedcontentbytype(record) }}

Add options for more flexibility:

    {{ relatedcontentbytags(record, { 'limit' : 5, 'pointsTag' : 5, 'pointsType': 100 }) }}

#### Example 

    {% for item in relatedcontentbytags(record) %}
        <p><a href="{{ item.link }}">{{ item.title|e }}</a></p>
    {% endfor %}

#### Options

See <code>config.yml</code> for more information. Options include:

* `limit`: the number of results returned
* `pointsTag`: points per equal tag
* `pointsType`: points if contenttype are equal
* more ...