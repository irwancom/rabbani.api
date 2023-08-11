<?php
/**
 * Supported Redis Queue:
 * wablas
 * member_digital_card
 **/
shell_exec("QUEUE='member_digital_card' REDIS_BACKEND=128.199.77.34:6379 APP_INCLUDE=index.php COUNT=5 php vendor/bin/resque");