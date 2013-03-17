# Rest4 : Rest Full of Rest Framework

Rest4 is a PHP framework meant to build resource oriented architectures.
It's under GNU/GPL v2 licence. More informations on http://rest4.org

## Development config

Clone the project in a folder.

Create a folder for your development files :

    mkdir /var/www/publicproject
    git init
    ...

Than, create a folder to contain your own datas and configuration files

    mkdir /var/www/owndatas
    mkdir /var/www/owndatas/www
    mkdir /var/www/owndatas/log
    echo "<?
    require 'restfor.php';" > /var/www/website/www/index.php

The vhost to be created is the same than for production except that you can
use a .htaccess file and the fact that you'll have to add one more directory
to the open_basedir and include_path directories.

## Production config

Rest4 has currently no stable version, use it at your own risk ! Many APIs
may change in the near future.

To use Rest4 for production, copy the Rest4 sources to a folder. By example :

    mkdir /var/www/rest4
    cd /var/www/rest4
    wget rest4.tar.gz
    tar -xvzf rest4.tar.gz .

You can use it's newly created folder or use your own folder for your site :

    mkdir /var/www/website
    mkdir /var/www/website/www
    mkdir /var/www/website/log
    echo "<?
    require 'restfor.php';" > /var/www/website/www/index.php

Finally, create a vhost like this one :

	<VirtualHost *:80>
		ServerName app.example.com
		ServerAlias app.example.fr

		DocumentRoot /var/www/webapp/www/

		CustomLog /var/www/webapp/log/access.log combined
	</VirtualHost>

	<Directory "/var/www/webapp/www/">
		<IfModule mod_php5.c>
		        php_admin_value open_basedir "/tmp/:/var/www/rest4/:/var/www/webapp/"
		        php_admin_value include_path "/var/www/webapp/www/:/var/www/rest4/www/"
		</IfModule>
		AllowOverride None
		Options FollowSymLinks
		Order allow,deny
		Allow from all
		RewriteEngine on
		# HOST is always the server name
		RewriteCond %{HTTP_HOST} !^app.example.com$
		RewriteRule (.*) http://app.example.com/$1 [R=301,L]
		# Rest4 rewrite rules
		RewriteRule ^(.*)$ index.php?path=$1 [L]
	</Directory>

Note : Rest4 requires URL Rewriting to be activated.

## More performances
We strongly recommend the use of xcache with Rest4 since it is the only
fully supported cache system. Feel free to maintain your own.

In addition to xcache, you could tell Apache to serve static files himself
by adding those lines before the Rest4 rewrite rules :

	# Static rewrite rule : Works only with Apache 2.3.9+
	RewriteCond %{REQUEST_METHOD} ^(HEAD|GET)$
	RewriteRule ^fs/public/(.+)\.([0-9a-z]+)$ /public/$1.$2 [END]

You may want to prevent PHP filling the $_GET, $_POST and $_COOKIE since
Rest4 doesn't use them :
variables_order=ES
request_order=ES

## Working code
The Rest4.org website is made with Rest4, may take a quick look to understand
how it run : https://github.com/nfroidure/Rest4.org

## Unit testing
I'm not a unit test fan, so i decided to test Rest4 code at the resource level. It's less test,
but keeps code strong. It test REST behavior, instead of each line of code. To test, use the
unit driver :
http://app.example.com/unit.dat?verbose=(0|1)&multiple=(0|1)
Verbose means you get informations, even if the test pass, multiple executes tests for each test
found in the ini_path (0 yours, 1 yours & mine).
Many tests will fail if you didn't set associated libraries or privileges (db, feed).

## Documentation
Each Rest4 copy comes with it's own documentation. Go to :
http://app.example.com/doc

## License
This program excluding it's sounds is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>
