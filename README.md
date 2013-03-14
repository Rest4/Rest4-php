# Rest4 : Rest Full of Rest Framework

Rest4 is a PHP framework meant to build resource oriented architectures.
More informations on http://rest4.org

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

To use Rest4 for production, copy the Rest4 sources to a folder. By exemple :

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
        RewriteCond %{HTTP_HOST} !^app.example.com.ewk$
        RewriteRule (.*) http://app.example.com.ewk/$1 [R=301,L]
        # Rest rewrite rules
        RewriteRule ^(.*)$ index.php?path=$1 [L]
</Directory>

Note : Rest4 requires URL Rewriting to be activated.

## More performances

We strongly recommend the use of xcache with Rest4 since it is the only
fully supported cache system. Feel free to maintain your own.

## Working code
The Rest4.org website is made with Rest4, may take a quick look to understand
how it run : https://github.com/nfroidure/Rest4.org
