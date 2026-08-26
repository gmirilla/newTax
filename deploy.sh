# ~/deploy.sh
#!/bin/bash
cd ~/newTax
git pull origin main
# index.php is excluded: public_html's copy has been manually edited to point at
# ~/newTax/vendor and ~/newTax/bootstrap (the app lives outside public_html here),
# so the stock index.php from git would break the site if synced over it.
rsync -a --exclude='storage' --exclude='index.php' public/ ~/public_html/
cp -r storage/app/public ~/public_html/storage
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Deploy complete."