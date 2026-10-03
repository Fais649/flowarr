<?php

it('preserves the public request port instead of advertising the internal nginx port', function () {
    $config = file_get_contents(base_path('docker/prod/nginx.conf'));

    expect($config)->toContain('fastcgi_param HTTP_HOST $http_host;')
        ->not->toContain('fastcgi_param HTTP_X_FORWARDED_PORT $server_port;');
});
