docker stop zeiterfassung_db
docker rm zeiterfassung_db

docker stop zeiterfassung_web
docker rm zeiterfassung_web

#docker build -t zeiterfassung .
#sudo docker run -d -p 80:80 --name zeiterfassung_web zeiterfassung
docker run --name zeiterfassung_db -e MYSQL_ROOT_PASSWORD=zeiterfassung -v /loehers/zeiterfassung/database:/var/lib/mysql -d mysql

#docker run --name zeiterfassung_web -p 80:80 -v /loehers/zeiterfassung/src:/var/www/html --link zeiterfassung_db:mysql -d tommylau/php-5.2

docker run --name zeiterfassung_web -p 80:80 -v /loehers/zeiterfassung/src/:/var/www/app/html --link zeiterfassung_db:mysql -d yappabe/apache-php:5.2
