FROM node:22-bookworm-slim AS web
WORKDIR /app
COPY web/package.json web/package-lock.json ./
RUN npm ci
COPY web/ ./
RUN npm run build
FROM nginx:1.28-alpine
COPY --from=web /app/dist /usr/share/nginx/html
COPY backend/public/index.php /var/www/hrm/backend/public/index.php
COPY infrastructure/nginx.conf /etc/nginx/conf.d/default.conf
