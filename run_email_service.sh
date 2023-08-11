#!/bin/sh

QUEUE='email' REDIS_BACKEND=redis://user:wasalam@128.199.77.34:6379 APP_INCLUDE=index.php php vendor/bin/resque