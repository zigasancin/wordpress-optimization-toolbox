/*
In some cases where your options table grows really big (like 10.000 rows or more) and you're using the Cachify plugin or some other plugin that manipulates a lot with the autoload column, it may be neccesary to add an index to the autoload column in your wp_options table. The easiest way to encounter such issues is to simply look for slow queries in MySQL's slow query log.
There is already an open ticket in WordPress' Trac with a patch attached, but there is still an ongoing discussion about it. -> https://core.trac.wordpress.org/ticket/24044
The following example uses the default WordPress database prefix “wp”.
*/

ALTER TABLE wp_options ADD INDEX (`autoload`);