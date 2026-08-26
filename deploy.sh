# ~/deploy.sh
#!/bin/bash
cd ~/newTax
git pull origin main
rsync -a --exclude='storage' public/ ~/public_html/
cp -r storage/app/public ~/public_html/storage
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Deploy complete."