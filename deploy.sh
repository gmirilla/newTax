# ~/deploy.sh
#!/bin/bash
cd ~/newTax
git pull origin main
cp -r public/images ~/public_html/images
cp -r storage/app/public ~/public_html/storage
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Deploy complete."php