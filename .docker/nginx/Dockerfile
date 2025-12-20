FROM nginx:1.27-alpine AS base

# Remove default config
RUN rm /etc/nginx/conf.d/default.conf

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]


FROM base AS dev

# Copy SSL certificates for local development
COPY .docker/nginx/app.wardianlabs.test.crt /etc/nginx/ssl/app.wardianlabs.test.crt
COPY .docker/nginx/app.wardianlabs.test.key /etc/nginx/ssl/app.wardianlabs.test.key

# Copy dev nginx config
COPY .docker/nginx/dev/default.conf /etc/nginx/conf.d/default.conf


FROM base AS prod

# Copy production nginx config
COPY .docker/nginx/prod/default.conf /etc/nginx/conf.d/default.conf

# Note: Static assets are mounted via shared volume in K8s (init container copies them)
# or via volume mount in docker-compose
