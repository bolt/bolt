# JSONAccess

Provides simple JSON access to Bolt's internal data structures.

## What it does

Enabling this extension adds routes to the application that serves content as
JSON rather than HTML. This route defaults to `/json`, so if, for example, you
have a content type named 'entries', then the entry with ID 1 is available
under `/json/entries/1`.

## Configuration

In order to enable JSON serving for any content type, it has to be added to
the extension's configuration file (`app/extensions/JSONAccess/config.yml`).

See the provided `config.yml.dist` for an example configuration and
explanations.

Note in particular that content types that you don't mention in the
configuration file won't be served by the JSONAccess extension.

## Calls

JSONAccess implements RESTful semantics for all calls, mapping resources as
follows:

- `/json/{contenttype}/` returns a list of records of the specified
  contenttype. The default is to return all items in the same order that the
  front-end uses. The listing contains the 'id' of every record, as well as a
  configurable selection of fields (defaulting to all configurable fields, but
  none of the 'meta' fields Bolt introduces itself). The list of fields can be
  overridden in the JSONAccess extension's `config.yml` file.
- `/json/{contenttype}/{id}` returns one record of the specified contenttype,
  including *all* fields.

## Advanced listing

The list call accepts some extra parameters (in the form of query string
parameters appended to the URL):

- `?order={fieldname}` and `?order={fieldname} {ordering}` - Order the list by
  the specified field. Only one field is currently supported. The `ordering`
  must be one of `ASC` or `DESC`.
- `?limit={limit}` - specify the number of items to return.
- `?page={page}` - to be combined with `limit`: get the n-th page (1-based, so
  `1` designates the first page.

So for example `/json/pages/?order=title&limit=10&page=2` sorts all Pages by
title and returns entries 11 through 20 (inclusive).
