<?php

// Root front controller: lets the app be served without /public in the URL.
// Static files are rewritten to public/ by the root .htaccess.
require __DIR__.'/public/index.php';
