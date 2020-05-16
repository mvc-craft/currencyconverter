# Currency Convertor:

>composer install

>define db configuration within .env file.

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exchangeratesonline
DB_USERNAME=root
DB_PASSWORD=
```

> run migrate command to create cache table within database 

```
php artisan migrate
```

> run local server with artisan command

```
php artisan serve
```

> if you are running the local server on different port then please change the ulr in index blade file accordingly.
#### /resources/views/index.blade.php

```
<script>
    var api_url = 'http://localhost:8000'; <-- change the url accordingly.
</script>
```

> hit the URL in browser: http://localhost:8000

### App should work based on the requirements.
