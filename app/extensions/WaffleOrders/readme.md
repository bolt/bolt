# WaffleOrders

A slightly useless extension to test/demonstrate how to make use of Bolt's
automatic table updates for an extension's own tables.

This extension implements a CRUD cycle for "waffle orders", exposed
on the "/waffle" endpoint. Users can provide a name and a number of waffles,
and these orders will be stored and displayed in descending order.

When you activate this extension, the database check will add a new table to
your database.

The WaffleOrders extension is not intended for production use; it is merely an
example that demonstrates custom per-extension database tables.
