FROM ubuntu


ENV DEBIAN_FRONTEND=noninteractive

RUN apt update 
RUN apt upgrade -y
RUN apt install -y  redis-tools php php-curl php-redis
WORKDIR /var/www/html
# RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
# RUN service apache2 restart
ENTRYPOINT ["php", "/var/www/html/init.php"]
CMD ["apachectl", "-D", "FOREGROUND"]