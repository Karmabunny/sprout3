The configuration is split into two files, to make maintenance easier.

The file "nginx.conf" is designed to sit somewhere in your user directory,
typically as a sibling to your www directory. It could go inside the
www dir, but don't forget to block access in that case.

That file contains all the the SproutCMS-specific rules required to make
the routing, etc work as expected.

The other file, "server.conf", contains an example PHP-FPM install, which
also includes our nginx.conf file. It's a fairly trim install, but that's
okay.
