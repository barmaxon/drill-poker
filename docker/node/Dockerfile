FROM node:20-alpine

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apk add --no-cache shadow \
    && (groupmod -g ${GROUP_ID} node || addgroup -g ${GROUP_ID} appgroup) \
    && (usermod -u ${USER_ID} node || adduser -u ${USER_ID} -G appgroup -s /bin/sh -D appuser)

WORKDIR /var/www/html

USER node
