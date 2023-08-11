# sim

## Digital Ocean Note
Max requirement Guzzle ^6.5
Library used is https://github.com/SociallyDev/Spaces-API (until commit ad0726e41e7a3a10c367917421c7b327256a358e)


## REDIS WABLAS NOTE

RUN MANUAL FOR DEBUG
```
QUEUE='wablas' REDIS_BACKEND=redis://user:wasalam@128.199.77.34:6379 APP_INCLUDE=index.php php vendor/bin/resque
```

RUN WITH forever
```
forever start -c php run_wablas_service.php
```

RUN WITH nohup
```
nohup sh run_wablas_service.sh > /var/log/run_wablas_service.log &
nohup sh run_member_digital_birthday_card_service.sh > /var/log/run_member_digital_birthday_card_service.log &
nohup sh run_email_service.sh > /var/log/run_email_service.log &
```