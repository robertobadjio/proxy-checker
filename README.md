###Proxy checker

####Install
1. `composer install`
2. Download GeoLite2-City.mmdb from https://dev.maxmind.com/geoip/geoip2/geolite2/
3. Create .env.local file

Run the command: `php application.php proxy:check 104.236.123.137:8080`

Output info:
```
Info 104.236.123.137:8080
Speed: 397.000000
City: United States/Clifton
Real IP: 10.0.10.22:44736
Check time: 0.551576
```