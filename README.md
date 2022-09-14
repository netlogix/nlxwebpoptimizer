# nlxWebPOptimizer

## About nlxWebPOptimizer

Plugin to automatically generate webp variants of the media assets


## Usage

Generate webp variants for existing media with this command:

    bin/console nlx:webpoptimizer:optimize

Add following snippet to media/.htaccess to serve webp images

    <IfModule mod_rewrite.c>
    RewriteEngine on
    
    # Check if browser support WebP images
    # Check if WebP replacement image exists
    # Serve WebP image instead
    RewriteCond %{HTTP_ACCEPT} image/webp
    RewriteCond %{DOCUMENT_ROOT}/media/$0.webp -f
    RewriteRule (.+)\.(jpe?g|png)$ $0.webp [T=image/webp,E=accept:1]
    
    # Tell proxy to cache this file based on "accept" header
    RewriteRule (.+)\.(jpe?g|png|webp)$ - [env=POTENTIAL_WEBP_IMAGE:1]
    Header merge vary accept env=POTENTIAL_WEBP_IMAGE
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ ../shopware.php?controller=Media&action=fallback [PT,L,QSA]
    </IfModule>

Add following snippet to root .htaccess to ensure proper MIME type for webp

    # Ensure proper MIME type for webp
    <IfModule mod_mime.c>
        AddType image/webp webp
    </IfModule>

## Running Tests

### phpunit - functional

    Not working at the moment because phpunit is functional testing and there is no running shopware installation.

    $ vendor/bin/phpunit
    
### phpunit - unit

    $ vendor/bin/phpunit -c phpunit_unit.xml.dist
    
### phpspec

    $ vendor/bin/phpspec-standalone.php7.2.phar

## License

Please see [License File](LICENSE) for more information.
