<?php
// config.php
// Put this outside web root in production. This example is simple and for local dev.

define('FIREBASE_DB_URL', 'https://siralexassignmentmit-default-rtdb.firebaseio.com/'); // no trailing slash
// For quick dev you can supply a database secret (legacy) or an OAuth token.
// For production use service account + SDK (explained below).

define('FIREBASE_DB_AUTH', '2GlNoYfnrWgKVKRCmgJXssV1YGZ0pYmwEhYYheAO');