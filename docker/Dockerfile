FROM docker.io/bitnami/opencart:4

COPY --chown=1001:root ./tawkto/tawkto.ocmod.zip /bitnami/opencart_storage/marketplace/

USER 0

ARG SECOND_STORE

RUN echo "Alias /$SECOND_STORE /opt/bitnami/opencart/" >> /opt/bitnami/apache/conf/httpd.conf